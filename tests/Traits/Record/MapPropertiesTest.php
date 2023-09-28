<?php

namespace Battis\CRUD\Tests\Traits\Record;

use Battis\CRUD\Connection;
use Battis\CRUD\Tests\Fixtures\Traits\Record\MapPropertiesTest\RecordFixture;
use Battis\CRUD\Tests\TestCase;

class MapPropertiesTest extends TestCase
{
    public function testMapping()
    {
        $data = [
            [
                [
                    'id' => 1,
                    'prop1' => 'testRow1',
                    'field2' => 123,
                    'field3' => null,
                ],
                [
                    ['prop1' => 'new value'],
                    ['field2' => 789],
                    ['prop1' => 'another new value', 'field2' => 321],
                ],
            ],
            [
                [
                    'id' => 2,
                    'prop1' => 'test row 2',
                    'field2' => 456,
                    'field3' => null,
                ],
                [],
            ],
        ];

        Connection::setPDO($this->getPDO());
        foreach ($data as list($expected, $updates)) {
            $record = RecordFixture::read($expected['id']);
            $this->assertTrue(property_exists($record, 'field1'));
            foreach ($expected as $key => $value) {
                $this->assertEquals($value, $record->$key);
            }

            foreach ($updates as $update) {
                $record->save($update);
                $row = array_merge($expected, $update);
                array_walk($row, function (&$value) {
                    $value = (string) $value;
                });
                var_dump($row);
                $this->assertTableContains(
                    $row,
                    $this->getDataset()->getTable('record_fixtures')
                );
                break;
            }
        }
    }
}
