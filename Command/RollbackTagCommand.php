<?php
namespace RtxLabs\LiquibaseBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use RtxLabs\LiquibaseBundle\Generator\ChangelogGenerator;
use RtxLabs\LiquibaseBundle\Runner\LiquibaseRunner;

class RollbackTagCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('liquibase:rollback:tag')
            ->setDescription('Rollback to specific tag')
            ->addArgument('bundle', InputArgument::OPTIONAL,
                          'The name of the bundle (shortcut notation AcmeDemoBundle) for that the changelogs should run or all bundles if no one is given')
            ->addArgument('tag', InputArgument::OPTIONAL,
                'Specifying a tag to rollback will roll back all change-sets that were executed against the target database after the given tag was applied')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'outputs the SQL-Statements that would run');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $runner = new LiquibaseRunner(
                        $this->getContainer()->get('filesystem'),
                        $this->getContainer()->get('doctrine.dbal.default_connection'));

        $bundle = $input->getArgument('bundle');
        $kernel = $this->getContainer()->get('kernel');
        $tag    = $input->getArgument('tag');
        try {
            if (strlen($bundle) > 0) {
                $result = $runner->runBundleRollbackTag($kernel->getBundle($bundle), $tag);
            } else {
                $result = $runner->runAppRollbackTag($kernel, $tag);
            }

            $output->writeln('');
            $output->writeln('<info>' . $result . '</info>');
        } catch (\RuntimeException $e) {
            $output->writeln('');
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }
    }
}