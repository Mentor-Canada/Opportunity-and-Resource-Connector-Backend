<?php

namespace Drupal\app\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * @FieldType(
 *  id = "availability_item",
 *  label = "Availability",
 *  category = "App",
 *  default_widget = "availability_widget"
 * )
 */
class AvailabilityItem extends FieldItemBase implements FieldItemInterface
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
        $properties['day'] = DataDefinition::create('integer');
        $properties['from'] = DataDefinition::create('string');
        $properties['to'] = DataDefinition::create('string');
        return $properties;
    }

    public static function schema(FieldStorageDefinitionInterface $field_definition)
    {
        return [
            'columns' => [
                'day' => [
                    'type' => 'int',
                    'not null' => false,
                ],
                'from' => [
                    'type' => 'varchar',
                    'length' => 255
                ],
                'to' => [
                    'type' => 'varchar',
                    'length' => 255,
                ],
            ]
        ];
    }

    public function isEmpty()
    {
        if ($this->get("from")->getValue() == '' && $this->get("to")->getValue() == '') {
            return true;
        }
        return false;
    }
}
