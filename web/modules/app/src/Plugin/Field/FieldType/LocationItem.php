<?php

namespace Drupal\app\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * @FieldType(
 *  id = "location_item",
 *  label = "Location",
 *  category = "App",
 *  default_widget = "location_widget"
 * )
 */
class LocationItem extends FieldItemBase implements FieldItemInterface
{
    /**
     * Defines field item properties.
     *
     * Properties that are required to constitute a valid, non-empty item should
     * be denoted with \Drupal\Core\TypedData\DataDefinition::setRequired().
     *
     * @return \Drupal\Core\TypedData\DataDefinitionInterface[]
     *   An array of property definitions of contained properties, keyed by
     *   property name.
     *
     * @see \Drupal\Core\Field\BaseFieldDefinition
     */
    public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition)
    {
        $properties = [];
        $properties['place_id'] = DataDefinition::create('string');
        $properties['type'] = DataDefinition::create('string');
        $properties['name'] = DataDefinition::create('string');
        $properties['lat'] = DataDefinition::create('string');
        $properties['lng'] = DataDefinition::create('string');
        $properties['postal_code'] = DataDefinition::create('string');
        $properties['country'] = DataDefinition::create('string');
        $properties['province'] = DataDefinition::create('string');
        $properties['components'] = DataDefinition::create('string');
        return $properties;
    }

    public static function schema(FieldStorageDefinitionInterface $field_definition)
    {
        return [
            'columns' => [
                'place_id' => [
                    'type' => 'text',
                ],
                'type' => [
                    'type' => 'varchar',
                    'length' => 255
                ],
                'name' => [
                    'type' => 'varchar',
                    'length' => 255
                ],
                'postal_code' => [
                    'type' => 'varchar',
                    'length' => 255
                ],
                'lat' => [
                    'type' => 'numeric',
                    'precision' => 11,
                    'scale' => 8
                ],
                'lng' => [
                    'type' => 'numeric',
                    'precision' => 11,
                    'scale' => 8
                ],
                'country' => [
                    'type' => 'varchar',
                    'length' => 255
                ],
                'province' => [
                    'type' => 'varchar',
                    'length' => 255
                ],
                'components' => [
                    'type' => 'text'
                ],
            ]
        ];
    }

    public function isEmpty()
    {
        if ($this->get("place_id")->getValue() == '' && $this->get('name')->getValue() == '') {
            return true;
        }
        return false;
    }
}
