<?php

namespace Battis\CRUD\Tests;

use Battis\DataUtilities\PHPUnit\FixturePath;
use PDO;
use PHPUnit\DbUnit\Database\DefaultConnection;
use PHPUnit\DbUnit\DataSet\YamlDataSet;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use PHPUnit\DbUnit\TestCaseTrait;

abstract class TestCase extends PHPUnitTestCase
{
    use TestCaseTrait, FixturePath;

    private static ?PDO $pdo = null;

    private ?DefaultConnection $connection = null;

    final public function getPDO(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = new PDO('sqlite::memory:');
        }
        return self::$pdo;
    }

    final public function getConnection()
    {
        if ($this->connection === null) {
            $this->connection = $this->createDefaultDBConnection(
                $this->getPDO()
            );
        }
        return $this->connection;
    }

    final public function getDataset()
    {
        return $this->getYamlDataSet('record_fixtures');
    }

    final protected function getYamlDataSet($datasetName)
    {
        return new YamlDataSet($this->getPathToFixture("$datasetName.yaml"));
    }

    protected function setUp(): void
    {
        self::getPDO()->query(
            file_get_contents(self::getPathToFixture('record_fixtures.sql'))
        );
        parent::setUp();
    }

    protected function tearDown(): void
    {
        static::getPDO()->query('DROP TABLE record_fixtures');
    }

    final protected function assertTablesEqualYaml(
        $expectedDatasetName,
        $tableName
    ) {
        $this->assertTablesEqual(
            (new YamlDataSet(
                $this->getPathToFixture("$expectedDatasetName.yaml")
            ))->getTable($tableName),
            $this->getConnection()->createQueryTable(
                $tableName,
                "SELECT * FROM `$tableName`"
            )
        );
    }
}
