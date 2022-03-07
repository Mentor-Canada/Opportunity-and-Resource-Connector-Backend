<?php

namespace Drupal\app_feedback;

use Drupal\app\Collection\CollectionBuilderBase;
use Drupal\app_search\SearchCollectionTrait;

class FeedbackCollectionBuilder extends CollectionBuilderBase
{
    use SearchCollectionTrait;

    public $q;

    public function __construct()
    {
        $alias = 'node';
        parent::__construct($alias);
        $this->q->condition("$alias.type", 'feedback');
        $this->addField(FeedbackFields::email);
        $this->addField(FeedbackFields::text);
        $this->addField(FeedbackFields::url);
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
