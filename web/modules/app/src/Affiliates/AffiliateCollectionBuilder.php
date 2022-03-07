<?php

namespace Drupal\app\Affiliates;

use Drupal\app\Collection\CollectionBuilderBase;

class AffiliateCollectionBuilder extends CollectionBuilderBase
{
    public function __construct()
    {
        parent::__construct();
        $this->q->condition('node.type', 'region');
        $this->q->addField('data', 'title');
    }

    public function title($value): AffiliateCollectionBuilder
    {
        if ($value) {
            $this->q->condition('title', "%$value%", "LIKE");
        }
        return $this;
    }

    public function sort($value, $direction): CollectionBuilderBase
    {
        if ($value) {
            $this->q->sort($value, $direction);
        }
        return $this;
    }

    public function execute(): array
    {
        $rows = $this->q->execute()->fetchAll();
        return $rows;
    }

    public function executeCount(): int
    {
        return $this->q->countQuery()->execute()->fetchCol()[0];
    }
}
