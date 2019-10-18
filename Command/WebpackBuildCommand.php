<?php

namespace AAXIS\Bundle\WebpackBundle\Command;

use AAXIS\Bundle\WebpackBundle\Provider\WebpackConfigProvider;
use Oro\Bundle\AssetBundle\NodeProcessFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Process\Process;

/**
 * Class WebpackBuildCommand
 * @package AAXIS\Bundle\WebpackBundle\Command
 */
class WebpackBuildCommand extends Command
{
    protected static $defaultName = 'aaxis:webpack:build';

    /**
     * @var NodeProcessFactory
     */
    protected $nodeProcessFactory;

    /**
     * @var string
     */
    protected $npmPath;

    /**
     * @var int|float|null
     */
    protected $buildTimeout;

    /**
     * @var int|float|null
     */
    protected $npmInstallTimeout;

    /**
     * @var WebpackConfigProvider
     */
    protected $configProvidor;

    /**
     * @var string
     */
    protected $buildDir;

    /**
     * @var string
     */
    protected $projectDir;

    /**
     * AAXISWebpackBuildCommand constructor.
     * @param WebpackConfigProvider $configProvider
     * @param NodeProcessFactory $nodeProcessFactory
     * @param string $npmPath
     * @param float|int|null $buildTimeout
     * @param float|int|null $npmInstallTimeout
     * @param string $buildDir
     */
    public function __construct(
        WebpackConfigProvider $configProvider,
        NodeProcessFactory $nodeProcessFactory,
        string $npmPath,
        $buildTimeout,
        $npmInstallTimeout,
        $buildDir)
    {
        $this->configProvidor = $configProvider;
        $this->nodeProcessFactory = $nodeProcessFactory;
        $this->npmPath = $npmPath;
        $this->buildTimeout = $buildTimeout;
        $this->npmInstallTimeout = $npmInstallTimeout;
        $this->buildDir = $buildDir;
        parent::__construct();
    }


    protected function configure()
    {
        $this->setDescription(
            'The command runs webpack to build assets.'
        )->addArgument(
            'theme',
            InputArgument::OPTIONAL,
            'Theme name to build.'
        )->addOption(
            'watch',
            'w',
            InputOption::VALUE_NONE,
            'Turn on watch mode. This means that after the initial build, 
            webpack continues to watch the changes in any of the resolved files.'
        )
        ->addOption(
            'npm-install',
            'i',
            InputOption::VALUE_NONE,
            'Reinstall npm dependencies to '.$this->buildDir.' folder, to be used by webpack.'.
            'Required when "node_modules" folder is corrupted.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $output->writeln('<info>Building assets.</info>');


        $this->generateConfigFiles($input, $output);


        $nodeModulesDir = $this->getProjectDir().'/'.$this->buildDir.'/node_modules';
        if (!file_exists($nodeModulesDir) || $input->getOption('npm-install')) {
            $output->writeln('<info>Installing npm dependencies.</info>');
            $this->npmInstall($output);
        }


        $this->buildAssets($input, $output);
        $io->success('All assets were successfully build.');
    }

    /**
     * @param OutputInterface $output
     */
    protected function npmInstall(OutputInterface $output): void
    {
        $command = [$this->npmPath, '--no-audit', 'install'];
        $output->writeln($command);
        $path = $this->getProjectDir() . '/' . $this->buildDir;
        $process = new Process($command, $path);
        $process->setTimeout($this->npmInstallTimeout);

        $process->run();

        if ($process->isSuccessful()) {
            $output->writeln('Done.');
        } else {
            throw new \RuntimeException($process->getErrorOutput());
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function buildAssets(InputInterface $input, OutputInterface $output): void
    {
        $command = ['node_modules/webpack/bin/webpack.js'];
        if (true === $input->getOption('no-debug') || 'prod' === $input->getOption('env')) {
            $command[] = '--mode=production';
        }
        if ($input->getOption('watch')) {
            $command[] = '--watch';
        }
        $command[] = '--env.symfony=' . $input->getOption('env');
        $command[] = '--colors';
        $process = $this->nodeProcessFactory->create(
            $command,
            $this->getProjectDir() . '/' . $this->buildDir,
            $this->buildTimeout);
        $output->writeln($process->getCommandLine());
        $process->run(function ($type, $buffer) use ($output) {
            $output->write($buffer);
        });
        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }
    }

    protected function getProjectDir(){
        if(!isset($this->projectDir)){
            $this->projectDir = $this->getApplication()->getKernel()->getProjectDir();
        }
        return $this->projectDir;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    private function generateConfigFiles(InputInterface $input, OutputInterface $output)
    {
        $theme = $input->getArgument('theme');
        $config = $this->configProvidor->getConfig($theme);
        file_put_contents(
            $this->getProjectDir() . '/' . $this->buildDir . '/webpack.config.json',
            json_encode([
                'entry' => $config,
                'output' => [
                    'path' => $this->getProjectDir().'/public/bundles'
                ]],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_LINE_TERMINATORS));
    }
}