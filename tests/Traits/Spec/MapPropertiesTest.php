<?php

namespace Battis\CRUD\Tests\Traits\Spec;

use Battis\CRUD\Exceptions\SpecException;
use Battis\CRUD\Tests\Fixtures\Traits\Spec\MapPropertiesTest\MappedRecordFixture;
use Battis\CRUD\Tests\Fixtures\Traits\Spec\MapPropertiesTest\SpecFixture;
use Battis\CRUD\Tests\Fixtures\Traits\Spec\MapPropertiesTest\UnmappedRecordFixture;
use PHPUnit\Framework\TestCase;

/**
 * @backupStaticAttributes enabled
 */
class MapPropertiesTest extends TestCase
{
    private $mapping = [
        'mapped_field_1' => 'mappedProp1',
        'mapped_field_2' => 'mappedProp2',
        'unmapped_field' => 'unmapped_field',
        'unmapped_prop' => 'unmapped_prop',
    ];

    public function testMapFieldsToProperties()
    {
        $spec = new SpecFixture(UnmappedRecordFixture::class);
        $this->assertEquals(
            $this->mapping,
            $spec->mapFieldsToProperties($this->mapping)
        );

        $record = new MappedRecordFixture();
        $spec = $record->fixtureGetSpec();
        foreach ($this->mapping as $field => $property) {
            $this->assertEquals($property, $spec->mapFieldToProperty($field));
        }
        $this->assertEquals(
            // ['mappedProp1' => 'mappedProp1', ...]
            array_combine(
                array_values($this->mapping),
                array_values($this->mapping)
            ),
            $spec->mapFieldsToProperties($this->mapping)
        );

        $this->expectException(SpecException::class);
        $spec->mapFieldsToProperties(
            array_merge(['mappedProp1' => 'duplicate key'], $this->mapping)
        );
    }

    public function testMapPropertiesToFields()
    {
        // ['mappedProp1' => 'mapped_field_1', ...]
        $data = array_combine(
            array_values($this->mapping),
            array_keys($this->mapping)
        );

        $spec = new SpecFixture(UnmappedRecordFixture::class);
        $this->assertEquals($data, $spec->mapPropertiesToFields($data));

        $record = new MappedRecordFixture();
        $spec = $record->fixtureGetSpec();
        foreach ($data as $property => $field) {
            $this->assertEquals($field, $spec->mapPropertyToField($property));
        }
        $this->assertEquals(
            // ['mapped_field_1' => 'mapped_field_1', ...]
            array_combine(array_values($data), array_values($data)),
            $spec->mapPropertiesToFields($data)
        );

        $this->expectException(SpecException::class);
        $spec->mapPropertiesToFields(
            array_merge(['mapped_field_1' => 'duplicate key'], $data)
        );
    }
}
