<?php
namespace RtxLabs\LiquibaseBundle\Runner;

use Symfony\Component\Process\Process;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class LiquibaseRunner
{
    private $filesystem;
    private $dbConnection;

    /**
     * @param Filesystem $filesystem
     * @param $dbConnection
     */
    public function __construct(Filesystem $filesystem, $dbConnection)
    {
        $this->filesystem   = $filesystem;
        $this->dbConnection = $dbConnection;
    }

    /**
     * @param KernelInterface $kernel
     * @return string
     */
    public function runAppUpdate(KernelInterface $kernel)
    {
        return $this->runUpdate($kernel->getRootDir() . '/Resources/liquibase/changelog-master.xml');
    }

    /**
     * @param BundleInterface $bundle
     * @return string
     */
    public function runBundleUpdate(BundleInterface $bundle)
    {
        return $this->runUpdate($bundle->getPath() . '/Resources/liquibase/changelog-master.xml');
    }

    /**
     * @param KernelInterface $kernel
     * @param $tag
     * @return string
     */
    public function runAppRollbackTag(KernelInterface $kernel, $tag)
    {
        return $this->runRollbackTag(
            $kernel->getRootDir() . '/Resources/liquibase/changelog-master.xml',
            $tag
        );
    }

    /**
     * @param BundleInterface $bundle
     * @param $tag
     * @return string
     */
    public function runBundleRollbackTag(BundleInterface $bundle, $tag)
    {
        return $this->runRollbackTag(
            $bundle->getPath() . '/Resources/liquibase/changelog-master.xml',
            $tag
        );
    }

    /**
     * @param KernelInterface $kernel
     * @param $tag
     * @return string
     */
    public function runAppRollbackCount(KernelInterface $kernel, $count)
    {
        return $this->runRollbackCount(
            $kernel->getRootDir() . '/Resources/liquibase/changelog-master.xml',
            $count
        );
    }

    /**
     * @param BundleInterface $bundle
     * @param $tag
     * @return string
     */
    public function runBundleRollbackCount(BundleInterface $bundle, $count)
    {
        return $this->runRollbackCount(
            $bundle->getPath() . '/Resources/liquibase/changelog-master.xml',
            $count
        );
    }

    /**
     * @param $changelogFile
     * @return string
     */
    private function runUpdate($changelogFile)
    {
        $command = $this->getBaseCommand()
            . ' --changeLogFile=' . $changelogFile
            . ' --logLevel=debug '
            . ' update';

        return $this->run($command);
    }

    /**
     * @param $changelogFile
     * @param $tag
     * @return string
     */
    private function runRollbackTag($changelogFile, $tag)
    {
        $command = $this->getBaseCommand()
            . ' --changeLogFile=' . $changelogFile
            . ' --logLevel=debug'
            . ' rollback ' . $tag;

        return $this->run($command);
    }

    /**
     * @param $changelogFile
     * @param $tag
     * @return string
     */
    private function runRollbackCount($changelogFile, $count)
    {
        $command = $this->getBaseCommand()
            . ' --changeLogFile=' . $changelogFile
            . ' --logLevel=debug'
            . ' rollbackCount ' . $count;

        return $this->run($command);
    }

    /**
     * @param $bundle
     */
    public function runDiff($bundle)
    {

    }

    /**
    * Execute liquibase
    */
    protected function run($command)
    {
        echo $command;
        $exec = new Process($command);
        $exec->enableOutput();
        $exec->getCommandLine();
        $exec->run();
        if (false === $exec->isSuccessful()) {
            throw new \RuntimeException($exec->getErrorOutput());
        }

        return $exec->getOutput();
    }

    /**
     * @return string
     */
    protected function getBaseCommand()
    {
        $dbalParams = $this->dbConnection->getParams();

        $command = 'java -jar '.__DIR__.'/../Resources/vendor/liquibase.jar'.
                    ' --driver='.$this->getJdbcDriverName($dbalParams['driver']).
                    ' --url='.$this->getJdbcDsn($dbalParams);

        if ($dbalParams['user'] != "") {
            $command .= ' --username='.$dbalParams['user'];
        }

        if ($dbalParams['password'] != "") {
            $command .= ' --password='.$dbalParams['password'];
        }

        $command .= ' --classpath='.$this->getJdbcDriverClassPath($dbalParams['driver']);

        return $command;
    }

    /**
     * @param $dbalDriver
     * @return string
     */
    protected function getJdbcDriverName($dbalDriver)
    {
        switch($dbalDriver) {
            case 'pdo_pgsql':
                $driver = "org.postgresql.Driver";
                break;
            case 'pdo_mysql':
            case 'mysql':
                $driver = "com.mysql.jdbc.Driver";
                break;
            default:
                throw new \RuntimeException("No JDBC-Driver found for $dbalDriver");
        }

        return $driver;
    }

    /**
     * @param $dbalDriver
     * @return string
     */
    protected function getJdbcDriverClassPath($dbalDriver)
    {
        $dir = dirname(__FILE__)."/../Resources/vendor/jdbc/";

        switch($dbalDriver) {
            case 'pdo_pgsql':
                $dir .= "postgresql-9.3.jdbc4-20131010.203348-4.jar";
                break;
            case 'pdo_mysql':
            case 'mysql':
                $dir .= "mysql-connector-java-5.1.18-bin.jar";
                break;
            default:
                throw new \RuntimeException("No JDBC-Driver found for $dbalDriver");
        }

        return $dir;
    }

    /**
     * @param $dbalParams
     * @return string
     */
    protected function getJdbcDsn($dbalParams)
    {
        switch($dbalParams['driver']) {
            case 'pdo_pgsql': return $this->generateDsn($dbalParams); break;
            case 'pdo_mysql': return $this->generateDsn($dbalParams); break;
            default: throw new \RuntimeException("Database not supported");
        }
    }

    /**
     * @param $dbalParams
     * @return string
     */
    protected function generateDsn($dbalParams)
    {
        $db = 'mysql';
        if ($dbalParams['driver'] == 'pdo_pgsql') {
            $db = 'postgresql';
        }

        return sprintf("jdbc:%s://%s/%s",
            $db,
            $dbalParams['host'],
            $dbalParams['dbname']);
    }
}
