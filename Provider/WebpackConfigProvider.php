<?php

namespace AAXIS\Bundle\WebpackBundle\Provider;

use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use ReflectionClass;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Yaml\Yaml;

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
    protected $config;

    /**
     * @var array
     */
    protected $themeNames;

    /**
     * @var Theme
     */
    protected $currentTheme;

    /**
     * @var Bundle
     */
    protected $currentBundle;

    /**
     * WebpackConfigProvider constructor.
     * @param ThemeManager $themeManager
     * @param array $bundles
     */
    public function __construct(ThemeManager $themeManager, array $bundles)
    {
        $this->themeManager = $themeManager;
        $this->bundles = $bundles;
    }

    /**
     * @param $themeName
     * @return array
     */
    public function getConfig($themeName)
    {
        $this->themeNames = [];
        $this->config = [];
        if(isset($themeName)){
            $this->currentTheme = $this->themeManager->getTheme($themeName);
            $this->collectConfigForTheme();
        }else{
            foreach($this->themeManager->getAllThemes() as $theme){
                $this->currentTheme = $theme;
                $this->collectConfigForTheme();
            }
        }
        return $this->config;
    }

    /**
     * Get config files of current theme
     */
    protected function collectConfigForTheme(){
        $theme = $this->currentTheme;
        $themeName = $theme->getName();
        if(in_array($themeName, $this->themeNames)){
            return;
        }
        if ($theme->getParentTheme()) {
            $this->currentTheme = $this->themeManager->getTheme($theme->getParentTheme());
            $this->collectConfigForTheme();
            $this->currentTheme = $theme;
        }
        foreach ($this->bundles as $name => $bundle) {
            try {
                $reflection = new ReflectionClass($bundle);
                $this->collectConfiguration($this->currentTheme, strtolower($name), dirname($reflection->getFileName()));
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
    protected function collectConfiguration($theme, $bundleName, $bundleDir)
    {
        $file = sprintf('%s/Resources/views/layouts/%s/config/entry-points.yml', $bundleDir, $theme->getDirectory());
        $publicDir = preg_replace('/bundle$/', '', strtolower($bundleName)) . '/' . $theme->getDirectory() . '/';
        if (is_file($file)) {
            $config = Yaml::parse(file_get_contents(realpath($file)));
            if (isset($config['entry'])) {
                foreach ($config['entry'] as $name => $value) {
                    if (isset($value)) {
                        if (is_array($value)) {
                            $this->config[$publicDir . $name] = [];
                            foreach ($value as $subValue) {
                                $this->config[$publicDir . $name][] = sprintf(self::ENTRY_FORMAT, $bundleDir, $theme->getDirectory(), $subValue);
                            }
                        } else {
                            $this->config[$publicDir . $name] = sprintf(self::ENTRY_FORMAT, $bundleDir, $theme->getDirectory(), $value);
                        }
                    } else {
                        $this->config[$publicDir . $name] = sprintf(self::ENTRY_FORMAT, $bundleDir, $theme->getDirectory(), $name);
                    }
                }
            }
        }
    }
}