<?php

namespace Battis\CRUD\Tests\Fixtures\Traits\Record\MapPropertiesTest;

use Battis\CRUD\Record;
use Battis\CRUD\Traits\Record\MapProperties;

class RecordFixture extends Record
{
    use MapProperties;

    public $prop1;
    public $field2;
    public $field3;

    protected static function definePropertyToFieldMapping(): array
    {
        return ["prop1" => "field1"];
    }
}
