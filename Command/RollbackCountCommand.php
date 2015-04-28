<?php
namespace RtxLabs\LiquibaseBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use RtxLabs\LiquibaseBundle\Generator\ChangelogGenerator;
use RtxLabs\LiquibaseBundle\Runner\LiquibaseRunner;

class RollbackCountCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('liquibase:rollback:count')
            ->setDescription('Rollback to number of change sets')
            ->addArgument('bundle', InputArgument::OPTIONAL,
                          'The name of the bundle (shortcut notation AcmeDemoBundle) for that the changelogs should run or all bundles if no one is given')
            ->addArgument('count', InputArgument::OPTIONAL,
                'You can specify the number of change-sets to rollback')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'outputs the SQL-Statements that would run');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $runner = new LiquibaseRunner(
                        $this->getContainer()->get('filesystem'),
                        $this->getContainer()->get('doctrine.dbal.default_connection'));

        $bundle = $input->getArgument('bundle');
        $kernel = $this->getContainer()->get('kernel');
        $count  = $input->getArgument('count');
        try {
            if (strlen($bundle) > 0) {
                $result = $runner->runBundleRollbackCount($kernel->getBundle($bundle), $count);
            } else {
                $result = $runner->runAppRollbackCount($kernel, $count);
            }

            $output->writeln('');
            $output->writeln('<info>' . $result . '</info>');
        } catch (\RuntimeException $e) {
            $output->writeln('');
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }
    }
}