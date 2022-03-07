<?php

namespace Drupal\app;

class DatabaseMigrator
{
    private $fromBundle;
    private $toTable;

    private $db;

    public function __construct($fromBundle, $toTable)
    {
        $this->fromBundle = $fromBundle;
        $this->toTable = $toTable;
        $this->db = \Drupal::database();
    }

    public function from($value, $fromTable, $to)
    {
        $rows = $this->db->query("SELECT entity_id, $value as value FROM $fromTable WHERE bundle = '{$this->fromBundle}'")->fetchAll();
        $this->insert($rows, $to);
//    $this->delete($fromTable);
    }

    public function fromMultilingual($value, $fromTable, $to)
    {
        $rows = $this->db->query("SELECT entity_id, JSON_OBJECTAGG(langcode, $value) as value FROM $fromTable WHERE bundle = '{$this->fromBundle}' GROUP BY entity_id")->fetchAll();
        $this->insert($rows, $to);
        //$this->delete($fromTable);
    }

    private function insert($rows, $to)
    {
        foreach ($rows as $row) {
            $this->db->query("INSERT INTO {$this->toTable}(entity_id, $to) VALUE(:entity_id, :value) ON DUPLICATE KEY UPDATE $to = :value", [
                ":value" => $row->value,
                ":entity_id" => $row->entity_id
            ]);
        }
    }

    private function delete($table)
    {
        $this->db->query("DELETE FROM $table WHERE bundle = '{$this->fromBundle}'")->execute();
    }
}
