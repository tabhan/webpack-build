<?php

namespace AAXIS\Bundle\WebpackBundle\Command;

use AAXIS\Bundle\WebpackBundle\Provider\WebpackConfigProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class DumpWebpackConfigCommand
 * The command outputs webpack config. @see WebpackConfigProvider
 * @package AAXIS\Bundle\WebpackBundle\Command
 */
class DumpWebpackConfigCommand extends Command
{
    protected static $defaultName = 'aaxis:webpack:dump';

    /**
     * @var WebpackConfigProvider
     */
    protected $configProvider;

    /**
     * DumpWebpackConfigCommand constructor.
     * @param WebpackConfigProvider $configProvider
     */
    public function __construct(WebpackConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
        parent::__construct();
    }


    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setDescription('The command outputs webpack config.')->addArgument('theme', InputArgument::OPTIONAL, 'Theme name to build.');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $theme = $input->getArgument('theme');
        $config = $this->configProvider->getConfig($theme);
        $io = new SymfonyStyle($input, $output);
        $io->writeln(json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_LINE_TERMINATORS));
    }
}