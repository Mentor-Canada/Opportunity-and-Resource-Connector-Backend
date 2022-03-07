<?php

namespace Drupal\app_filter;

class FilterCollectionBuilder
{
    private $q;

    public function __construct()
    {
        $db = \Drupal::database();
        $this->q = $db->select('filter');
        $this->q->addField('filter', 'id');
        $this->q->addField('filter', 'title');
        $this->q->addField('filter', 'type');
        $this->q->addField('filter', 'data');
    }

    public function uid($uid): FilterCollectionBuilder
    {
        $this->q->condition('uid', $uid);
        return $this;
    }

    public function type($type): FilterCollectionBuilder
    {
        $this->q->condition('type', $type);
        return $this;
    }

    public function ids($ids): FilterCollectionBuilder
    {
        $this->q->condition('id', $ids, 'IN');
        return $this;
    }

    public function execute()
    {
        return $this->q->execute()->fetchAll();
    }
}
