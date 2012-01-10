<?php
namespace RtxLabs\LiquibaseBundle\Runner;
use Symfony\Component\HttpKernel\Util\Filesystem;
use Symfony\Component\Process\Process;

class LiquibaseRunner
{
    private $filesystem;
    private $dbConnection;

    public function __construct(Filesystem $filesystem, $dbConnection)
    {
        $this->filesystem = $filesystem;
        $this->dbConnection = $dbConnection;
    }

    public function runUpdate($bundle)
    {
        if ($bundle == null) {
            $changelogFile = 'app/Resources/liquibase/changelog-master.xml';
        }
        else {
            $changelogFile = $bundle->getPath().'/Resources/liquibase/changelog-master.xml';
        }

        $command = $this->getBaseCommand();
        $command .= ' --changeLogFile='.$changelogFile;
        $command .= " update";

        $this->run($command);
    }

    public function runRollback($bundle)
    {

    }

    public function runDiff($bundle)
    {

    }

    protected function run($command)
    {
        $output = "";
        exec($command, $output);

        echo $command."\n";
        print_r($output);
    }

    protected function getBaseCommand()
    {
        $dbalParams = $this->dbConnection->getParams();

        $command = 'java -jar '.__DIR__.'/../Resources/vendor/liquibase.jar '.
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

    protected function getJdbcDriverName($dbalDriver)
    {
        switch($dbalDriver) {
            case 'pdo_mysql':
            case 'mysql':   $driver = "com.mysql.jdbc.Driver"; break;
            default: throw new \RuntimeException("No JDBC-Driver found for $dbalDriver");
        }

        return $driver;
    }

    protected function getJdbcDriverClassPath($dbalDriver)
    {
        $dir = dirname(__FILE__)."/../Resources/vendor/jdbc/";

        switch($dbalDriver) {
            case 'pdo_mysql':
            case 'mysql':   $dir .= "mysql-connector-java-5.1.18-bin.jar"; break;
            default: throw new \RuntimeException("No JDBC-Driver found for $dbalDriver");
        }

        return $dir;
    }

    protected function getJdbcDsn($dbalParams)
    {
        switch($dbalParams['driver']) {
            case 'pdo_mysql': return $this->getMysqlJdbcDsn($dbalParams); break;
            default: throw new \RuntimeException("Database not supported");
        }
    }

    protected function getMysqlJdbcDsn($dbalParams)
    {
        $dsn = "jdbc:mysql://";
        if ($dbalParams['host'] != "") {
            $dsn .= $dbalParams['host'];
        }
        else {
            $dsn .= 'localhost';
        }

        if ($dbalParams['port'] != "") {
            $dsn .= ":".$dbalParams['port'];
        }

        $dsn .= "/".$dbalParams['dbname'];

        return $dsn;
    }
}