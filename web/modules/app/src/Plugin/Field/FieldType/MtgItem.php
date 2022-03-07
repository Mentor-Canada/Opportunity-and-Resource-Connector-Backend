<?php

namespace Drupal\app\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * @FieldType(
 *  id = "mtg_item",
 *  label = "MTG",
 *  category = "App",
 *  default_widget = "mtg_widget"
 * )
 */
class MtgItem extends FieldItemBase implements FieldItemInterface
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
        $properties['invite_id'] = DataDefinition::create('string');
        $properties['invite_first_name'] = DataDefinition::create('string');
        $properties['invite_last_name'] = DataDefinition::create('string');
        $properties['invite_tenant_name'] = DataDefinition::create('string');
        $properties['invite_email'] = DataDefinition::create('string');
        $properties['invite_date_sent'] = DataDefinition::create('integer');
        $properties['invite_date_closed'] = DataDefinition::create('integer');
        $properties['sent_by_first_name'] = DataDefinition::create('string');
        $properties['sent_by_last_name'] = DataDefinition::create('string');
        $properties['sent_by_email'] = DataDefinition::create('string');
        $properties['sent_by_uuid'] = DataDefinition::create('string');
        return $properties;
    }

    public static function columns()
    {
        return [
            'invite_id' => ['type' => 'varchar', 'length' => 255],
            'invite_first_name' => ['type' => 'varchar', 'length' => 255],
            'invite_last_name' => ['type' => 'varchar', 'length' => 255],
            'invite_tenant_name' => ['type' => 'varchar', 'length' => 255],
            'invite_email' => ['type' => 'varchar', 'length' => 255],
            'invite_date_sent' => ['type' => 'int'],
            'invite_date_closed' => ['type' => 'int'],
            'sent_by_first_name' => ['type' => 'varchar', 'length' => 255],
            'sent_by_last_name' => ['type' => 'varchar', 'length' => 255],
            'sent_by_email' => ['type' => 'varchar', 'length' => 255],
            'sent_by_uuid' => ['type' => 'varchar', 'length' => 255]
        ];
    }

    public static function schema(FieldStorageDefinitionInterface $field_definition)
    {
        return [
            'columns' => self::columns()
        ];
    }

    public function isEmpty()
    {
        $columns = self::columns();
        foreach ($columns as $key => $column) {
            if ($this->get($key)->getValue() != '') {
                return false;
            }
        }
        return false;
    }
}
