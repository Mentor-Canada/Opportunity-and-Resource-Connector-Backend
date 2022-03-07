<?php

namespace Drupal\app_notify;

use Drupal\app\Collection\CollectionBuilderBase;
use Drupal\app_search\SearchCollectionTrait;

class NotifyCollectionBuilder extends CollectionBuilderBase
{
    use SearchCollectionTrait;

    public $q;

    public function __construct()
    {
        $db = \Drupal::database();
        /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->q = $db->select('node', 'node');
        $this->q->condition('node.type', 'notify');
        $this->addCreated();
        $this->addEmail();
        $this->addName();

        $this->q->leftJoin('node__field_search', 'field_search', 'node.nid = field_search.entity_id');
        $this->q->leftJoin('node', 'search', 'search.nid = field_search.field_search_target_id');
        $this->addSearchFields();
    }

    private function addCreated()
    {
        $this->q->leftJoin('node_field_data', 'data', 'node.nid = data.nid');
        $this->q->addField('data', 'created');
    }

    private function addEmail()
    {
        $this->q->leftJoin('node__field_email', 'email', 'node.nid = email.entity_id');
        $this->q->addField('email', 'field_email_value');
    }

    private function addName()
    {
        $this->q->leftJoin('node__field_first_name', 'first_name', 'node.nid = first_name.entity_id');
        $this->q->addField('first_name', 'field_first_name_value');
        $this->q->leftJoin('node__field_last_name', 'last_name', 'node.nid = last_name.entity_id');
        $this->q->addField('last_name', 'field_last_name_value');
    }

    public function range($offset, $limit): NotifyCollectionBuilder
    {
        $this->q->range($offset, $limit);
        return $this;
    }

    public function execute()
    {
        return $this->q->execute()->fetchAll();
    }

    public function executeCount()
    {
        return $this->q->countQuery()->execute()->fetchField();
    }
}
