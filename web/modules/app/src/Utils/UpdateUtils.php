<?php

namespace Drupal\app\Utils;

use Drupal\Core\Database\Database;

class UpdateUtils
{
    public static function UpdateLocationFields()
    {
        $varchar = [
            'type' => 'varchar',
            'length' => 255,
            'not null' => false,
        ];
        $text = [
            'type' => 'text'
        ];
        $schema = Database::getConnection()->schema();
        $tables = ['field_physical_location', 'field_physical_locations'];
        $fields = ['postal_code' => $varchar, 'province' => $varchar, 'country' => $varchar, 'components' => $text];
        foreach ($tables as $table) {
            $t = "node__$table";
            foreach ($fields as $field => $type) {
                $c = "{$table}_{$field}";
                if (!$schema->fieldExists($t, $c)) {
                    $schema->addField($t, $c, $type);
                }
                $r = "node_revision__$table";
                if (!$schema->fieldExists($r, $c)) {
                    $schema->addField($r, $c, $type);
                }
            }

            $field = "{$table}_place_id";
            $q = "SELECT entity_id, $field, {$table}_name as name, {$table}_components as components FROM $t WHERE $field IS NOT NULL";
            $rows = \Drupal::database()->query($q)->fetchAll();
            foreach ($rows as $row) {
                $placeId = $row->$field;
                $components = json_decode($row->components);
                $postal_code = null;
                foreach ($components as $component) {
                    if (in_array('postal_code', $component->types)) {
                        $postal_code = $component->long_name;
                        break;
                    }
                }
                if ($postal_code) {
                    $q = "UPDATE $t SET {$table}_postal_code = :postal_code WHERE {$table}_place_id = '{$placeId}'";
                    \Drupal::database()->query($q, ['postal_code' => $postal_code]);
                }
            }
        }
    }

    public static function updateLocationData()
    {
        $varchar = [
            'type' => 'varchar',
            'length' => 255,
            'not null' => false,
        ];
        $text = [
            'type' => 'text'
        ];
        $schema = Database::getConnection()->schema();
        $tables = ['field_physical_location', 'field_physical_locations', 'field_locations'];
        $fields = ['province' => $varchar, 'country' => $varchar, 'response' => $text];
        foreach ($tables as $table) {
            foreach ($fields as $field => $type) {
                $table = "node__$table";
                $c = "{$table}_{$field}";
                if (!$schema->fieldExists($table, $c)) {
                    $schema->addField($table, $c, $type);
                }
                $r = "node_revision__$table";
                if (!$schema->fieldExists($r, $c)) {
                    $schema->addField($r, $c, $type);
                }
            }
            // save google place response
            $field = "{$table}_place_id";
            $q = "SELECT $field FROM $table WHERE $field IS NOT NULL";
            $rows = \Drupal::database()->query($q)->fetchAll();
            foreach ($rows as $row) {
                $data = GooglePlaceUtils::getWithId($row->$field);
                $response = json_encode($data->result);
                $q = "INSERT INTO $table SET {$table}_response = '$response'";
                \Drupal::database()->query($q);
            }
        }
    }
}
