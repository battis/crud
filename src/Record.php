<?php

namespace Battis\CRUD;

use Battis\CRUD\Exceptions\RecordException;
use Battis\CRUD\Utilities\Types;
use PDO;
use PDOException;

// TODO should Record be using Eloquent queries too?
abstract class Record
{
    /** @var Spec|null */
    protected static $spec;

    /**
     * Construct a new instance of the object from an associative array of data properties.
     *
     * @param array $data Associative array of properties (keys that do not exactly match a declared property will be ignored)
     */
    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->assign($data);
        }
    }

    protected static function getSpec(): Spec
    {
        // TODO: sort out late static binding and static properties and why this isn't working
        //if (empty(static::$spec)) {
        static::$spec = static::defineSpec();
        //}
        return static::$spec;
    }

    protected static function defineSpec(): Spec
    {
        return new Spec(static::class);
    }

    /**
     * Insert a new record into the database and return on an instance of this object representing that record, if successfully inserted.
     *
     * @param array $data Associative array of properties (keys that do not exactly match a declared property will be ignored)
     *
     * @return static|null
     */
    public static function create(array $data)
    {
        $s = static::getSpec();
        $pdo = Connection::getInstance()->getPDO();

        $_data = Types::toDatabaseValues(static::objectToDatabaseHook($data));
        $table = $s->getTableName();
        $fields = join(',', array_keys($_data));
        $parameters = join(
            ',',
            array_map(fn($key) => ":$key", array_keys($_data))
        );
        try {
            $statement = $pdo->prepare(
                "INSERT INTO $table ($fields) VALUES ($parameters)"
            );

            if ($statement->execute($_data)) {
                return static::read($pdo->lastInsertId());
            }
        } catch (PDOException $e) {
            $e; // ignored
        }
        return null;
    }

    /**
     * Read a record from the database and return an instance of this object representing that record, if present
     *
     * @param mixed $id Primary key value
     *
     * @return static|null
     */
    public static function read($id)
    {
        $s = static::getSpec();
        $pdo = Connection::getInstance()->getPDO();

        $table = $s->getTableName();
        $primaryKey = $s->getPrimaryKey();

        $statement = $pdo->prepare(
            "SELECT * FROM $table WHERE $primaryKey = ? LIMIT 1"
        );
        if ($statement->execute([$id])) {
            if ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                return new static(static::databaseToObjectHook($row));
            }
        }
        return null;
    }

    /**
     * Retrieve all records matching an optional array of properties as an array of objects representing those records
     *
     * @param array $data Optional associative array of properties to match (keys that do not exactly match a declared property will be ignored)
     *
     * @return static[]
     */
    public static function retrieve(array $data = []): array
    {
        $s = static::getSpec();
        $pdo = Connection::getInstance()->getPDO();

        $table = $s->getTableName();
        $response = false;
        if (empty($data)) {
            $statement = $pdo->prepare("SELECT * FROM $table");
            $response = $statement->execute();
        } else {
            $_data = Types::toDatabaseValues(
                static::objectToDatabaseHook($data)
            );
            $condition = join(
                ' AND ',
                array_map(fn($key) => "$key = :$key", array_keys($_data))
            );
            $statement = $pdo->prepare("SELECT * FROM $table WHERE $condition");
            $response = $statement->execute($_data);
        }

        $result = [];
        if ($response) {
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                array_push(
                    $result,
                    new static(static::databaseToObjectHook($row))
                );
            }
        }
        return $result;
    }

    /**
     * Update a record in the database and return an instance of this object representing that record, if successful
     *
     * @param array $data Associative array of properties (keys that do not exactly match a declared property will be ignored). *Must* include a primary key property to identify the record to be updated.
     *
     * @return static|null
     */
    public static function update(array $data)
    {
        $s = static::getSpec();
        if (key_exists($s->getPrimaryKey(), $data)) {
            $result = self::read($data[$s->getPrimaryKey()]);
            $result->save($data);
            return $result;
        }
        return null;
    }

    /**
     * Delete a record from the database, returning an object representing the deleted record, if successful
     *
     * @param mixed $id Primary key value
     *
     * @return static|null
     */
    public static function delete($id)
    {
        $s = static::getSpec();
        $pdo = Connection::getInstance()->getPDO();

        $result = static::read($id);

        $table = $s->getTableName();
        $primaryKey = $s->getPrimaryKey();

        $statement = $pdo->prepare("DELETE FROM $table WHERE $primaryKey = ?");
        if ($statement->execute([$id])) {
            return $result;
        }
    }

    private function assign(array $data)
    {
        $s = static::getSpec();
        foreach ($data as $property => $value) {
            if ($setter = $s->getSetter($property)) {
                $this->$setter(
                    Types::toExpectedArgumentType($this, $setter, $value)
                );
            } else {
                // TODO better solution to PHP 8.2 deprecation of dynamic property declaration
                @$this->$property = $value;
            }
        }
    }

    private function cloneIntoSelf(self $other)
    {
        $this->assign((array) $other);
    }

    private function getPrimaryKey()
    {
        $property = static::getSpec()->getPrimaryKey();
        return $this->$property;
    }

    public function save(array $data = []): void
    {
        $s = static::getSpec();
        $pdo = Connection::getInstance()->getPDO();

        $table = $s->getTableName();
        $primaryKey = $s->getPrimaryKey();
        $id = $this->getPrimaryKey();
        $_data = Types::toDatabaseValues(
            static::objectToDatabaseHook(array_merge((array) $this, $data))
        );
        $identifier = Types::toDatabaseValues(
            static::objectToDatabaseHook([$primaryKey => $id])
        );
        $__data = array_diff($_data, $identifier);
        $values = join(
            ',',
            array_map(fn($key) => "$key = :$key", array_keys($__data))
        );

        $statement = $pdo->prepare(
            "UPDATE $table SET $values WHERE $primaryKey = :$primaryKey"
        );
        if ($statement->execute($_data)) {
            $updated = static::read($id);
            if ($updated) {
                $this->cloneIntoSelf($updated);
                unset($updated);
                return;
            }
        }
        throw new RecordException(
            'Record no longer available',
            RecordException::SAVE_ERROR
        );
    }

    protected static function objectToDatabaseHook(array $data): array
    {
        return $data;
    }

    protected static function databaseToObjectHook(array $data): array
    {
        return $data;
    }
}
