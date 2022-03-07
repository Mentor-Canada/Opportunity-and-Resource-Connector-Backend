<?php

namespace Drupal\app\lib;

use Drupal\Core\Database\Query\Select;

abstract class NodeCollectionBase
{
    protected Select $q;

    public function __construct($type)
    {
        $db = \Drupal::database();
        /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->q = $db->select('node', 'node');
        $this->q->addField('node', 'nid');

        $this->q->leftJoin('node_field_data', 'data', 'node.nid = data.nid');
        $this->q->addField('data', 'created');

        $this->q->condition('node.type', $type);
        $this->q->condition('data.langcode', 'en');
    }

    public function execute()
    {
        $result = $this->q->distinct()->execute()->fetchAll();
        return array_map(fn ($row) => $row->nid, $result);
    }
}
