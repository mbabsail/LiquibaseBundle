<?php
namespace RtxLabs\LiquibaseBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use RtxLabs\LiquibaseBundle\Generator\ChangelogGenerator;
use RtxLabs\LiquibaseBundle\Runner\LiquibaseRunner;

class UpdateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('liquibase:update:run')
            ->setDescription('Generating a Liquibase changelog file skeleton')
            ->addArgument('bundle', InputArgument::OPTIONAL,
                          'The name of the bundle (shortcut notation AcmeDemoBundle) for that the changelogs should run or all bundles if no one is given')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'outputs the SQL-Statements that would run')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $runner = new LiquibaseRunner(
                        $this->getContainer()->get('filesystem'),
                        $this->getContainer()->get('doctrine.dbal.default_connection'));

        $bundle = $input->getArgument('bundle');
        $kernel = $this->getContainer()->get('kernel');
        try {
            if (strlen($bundle) > 0) {
                $result = $runner->runBundleUpdate($kernel->getBundle($bundle));
            } else {
                $result = $runner->runAppUpdate($kernel);
            }

            $output->writeln('');
            $output->writeln('<info>' . $result . '</info>');
        } catch (\RuntimeException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }
    }
}