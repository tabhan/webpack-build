<?php

namespace AAXIS\Bundle\WebpackBundle\EventListener;

use AAXIS\Bundle\WebpackBundle\Command\DumpWebpackConfigCommand;
use AAXIS\Bundle\WebpackBundle\Command\WebpackBuildCommand;
use Oro\Bundle\AssetBundle\Command\OroAssetsBuildCommand;
use Oro\Bundle\CacheBundle\Manager\OroDataCacheManager;
use Oro\Bundle\InstallerBundle\CommandExecutor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AssetsBuildListener
{

    /**
     * @var OroDataCacheManager
     */
    protected $cacheManager;

    /**
     * AssetsBuildListener constructor.
     * @param OroDataCacheManager $cacheManager
     */
    public function __construct(OroDataCacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }


    /**
     * @param ConsoleCommandEvent $event
     */
    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();
        if (!$command) {
            return;
        }
        switch ($command->getName()) {
            case OroAssetsBuildCommand::getDefaultName();
                $webpackCommand = WebpackBuildCommand::getDefaultName();
                break;
            case WebpackBuildCommand::getDefaultName();
                $webpackCommand = DumpWebpackConfigCommand::getDefaultName();
                break;
            default;
                return;
        }
        $input = $event->getInput();
        $output = $event->getOutput();
        /**
         * @var Command
         */
        $command = $event->getCommand();
        $env = $input->hasOption('env') ? $input->getOption('env') : null;
        $commandExecutor = new CommandExecutor($env, $output, $command->getApplication(), $this->cacheManager);
        $commandExecutor->runCommand($webpackCommand);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return CommandExecutor
     */
    protected function getCommandExecutor(InputInterface $input, OutputInterface $output)
    {
        $commandExecutor = new CommandExecutor($input->hasOption('env') ? $input->getOption('env') : null, $output, $this->inner->getApplication(), $this->cacheManager);
        $timeout = $input->getOption('timeout');
        if ($timeout >= 0) {
            $commandExecutor->setDefaultOption('process-timeout', $timeout);
        }
        if (!$input->getOption('force-debug') && (true === $input->getOption('no-debug') || $this->debug)) {
            $commandExecutor->setDefaultOption('no-debug');
        }
        return $commandExecutor;
    }
}