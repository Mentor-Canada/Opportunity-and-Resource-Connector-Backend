<?php

namespace Drupal\app_search;

use Drupal\Core\Database\Query\SelectInterface;

class SiteBasedSortAlgorithm implements SortAlgorithmInterface
{
    public function sort(SelectInterface $q)
    {
        $q->orderBy("postalCodeMatch", "DESC");
        $q->orderBy("responsivenessTier");
        $q->orderBy("distance");
        $q->orderBy("NQMS", "DESC");
        $q->orderBy("ADA", "DESC");
    }
}
