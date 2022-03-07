<?php

namespace Drupal\app_search;

use Drupal\Core\Database\Query\SelectInterface;

class DefaultSortAlgorithm implements SortAlgorithmInterface
{
    public function sort(SelectInterface $q)
    {
        $q->orderBy("priority", "DESC");
        $q->orderBy("responsivenessTier");
        $q->orderBy("NQMS", "DESC");
        $q->orderBy("ADA", "DESC");
    }
}
