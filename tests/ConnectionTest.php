<?php

namespace Battis\CRUD\Tests;

use Battis\CRUD\Connection;
use Envms\FluentPDO\Query;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * @backupStaticAttributes enabled
 */
class ConnectionTest extends TestCase
{
    private $pdo;

    private function getPDO(): PDO
    {
        if (empty($this->pdo)) {
            $this->pdo = new PDO('sqlite:memory');
        }
        return $this->pdo;
    }

    public function testSingleton()
    {
        $c = Connection::getInstance($this->getPDO());
        $this->assertEquals($c->getPDO(), $this->getPDO());
        $d = Connection::getInstance();
        $this->assertEquals($d, $c);
        $this->assertEquals($d->getPDO(), $this->getPDO());
    }

    public function testCreateQuery()
    {
        Connection::setPDO($this->getPDO());
        $this->assertInstanceOf(Query::class, Connection::createQuery());
    }
}
