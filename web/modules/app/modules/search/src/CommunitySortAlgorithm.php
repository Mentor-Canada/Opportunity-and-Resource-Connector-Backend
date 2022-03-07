<?php

namespace Drupal\app_search;

use Drupal\Core\Database\Query\SelectInterface;

class CommunitySortAlgorithm implements SortAlgorithmInterface
{
    public function sort(SelectInterface $q)
    {
        $q->orderBy("responsivenessTier");
        $q->orderBy("NQMS", "DESC");
        $q->orderBy("ADA", "DESC");
        $q->orderBy("communityDistance");
    }
}
