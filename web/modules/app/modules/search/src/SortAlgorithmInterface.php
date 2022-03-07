<?php

namespace Drupal\app_search;

use Drupal\Core\Database\Query\SelectInterface;

interface SortAlgorithmInterface
{
    public function sort(SelectInterface $q);
}
