<?php

namespace AAXIS\Bundle\WebpackBundle\Provider;

use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use ReflectionClass;
use Symfony\Component\Yaml\Yaml;

/**
 * Class WebpackConfigProvider
 * @package AAXIS\Bundle\WebpackBundle\Provider
 */
class WebpackConfigProvider
{
    const ENTRY_FORMAT = '%s/Resources/public/%s/%s';

    /**
     * @var ThemeManager
     */
    protected $themeManager;

    /**
     * @var array
     */
    protected $bundles;

    /**
     * @var array
     */
    protected $entry;

    /**
     * @var array
     */
    protected $themeNames;

    /**
     * @var Theme
     */
    protected $currentTheme;

    /**
     * @var string
     */
    protected $projectDir;

    /**
     * WebpackConfigProvider constructor.
     * @param ThemeManager $themeManager
     * @param array $bundles
     * @param string $projectDir
     */
    public function __construct(ThemeManager $themeManager, array $bundles, string $projectDir)
    {
        $this->themeManager = $themeManager;
        $this->bundles = $bundles;
        $this->projectDir=$projectDir;
    }


    /**
     * @param $themeName
     * @return array
     */
    public function getEntry($themeName)
    {
        $this->themeNames = [];
        $this->entry = [];
        if (isset($themeName)) {
            $this->currentTheme = $this->themeManager->getTheme($themeName);
            $this->collectEntriesForTheme();
        } else {
            foreach ($this->themeManager->getAllThemes() as $theme) {
                $this->currentTheme = $theme;
                $this->collectEntriesForTheme();
            }
        }
        return [
            'entry' => $this->entry,
            'output' => [
                'path' => $this->projectDir . '/public/bundles'
            ]
        ];
    }

    /**
     * Get config files of current theme
     */
    protected function collectEntriesForTheme()
    {
        $theme = $this->currentTheme;
        $themeName = $theme->getName();
        if (in_array($themeName, $this->themeNames)) {
            return;
        }
        if ($theme->getParentTheme()) {
            $this->currentTheme = $this->themeManager->getTheme($theme->getParentTheme());
            $this->collectEntriesForTheme();
            $this->currentTheme = $theme;
        }
        foreach ($this->bundles as $name => $bundle) {
            try {
                $reflection = new ReflectionClass($bundle);
                $this->collectEntriesForBundle($this->currentTheme, strtolower($name), dirname($reflection->getFileName()));
            } catch (\ReflectionException $e) {
            }
        }
        $this->themeNames[] = $themeName;
    }

    /**
     * Get config files from bundle
     * @param Theme $theme
     * @param string $bundleName
     * @param string $bundleDir
     * @return array
     */
    protected function collectEntriesForBundle($theme, $bundleName, $bundleDir)
    {
        $file = sprintf('%s/Resources/views/layouts/%s/config/entry-points.yml', $bundleDir, $theme->getDirectory());
        $pubBundleThemeDir = sprintf('%s/Resources/public/%s/', $bundleDir, $theme->getDirectory());
        $publicDir = preg_replace('/bundle$/', '', strtolower($bundleName)) . '/' . $theme->getDirectory() . '/';
        if (is_file($file)) {
            $config = Yaml::parse(file_get_contents(realpath($file)));
            if (isset($config['entry'])) {
                foreach ($config['entry'] as $name => $value) {
                    $entryName = $publicDir.$name;
                    if (isset($value)) {
                        if (is_array($value)) {
                            $this->entry[$entryName] = [];
                            foreach ($value as $subValue) {
                                $this->entry[$entryName][] = $pubBundleThemeDir.$subValue;
                            }
                        } else {
                            $this->entry[$entryName] = $pubBundleThemeDir.$value;
                        }
                    } else {
                        foreach (glob($pubBundleThemeDir.$name) as $entryFile){
                            $entryInfo = pathinfo($entryFile);
                            $entryName = $publicDir.substr($entryInfo['dirname'], strlen($pubBundleThemeDir)).'/'
                                .$entryInfo['filename'];
                            $this->entry[$entryName] = $entryFile;
                        }
                    }
                }
            }
        }
    }
}