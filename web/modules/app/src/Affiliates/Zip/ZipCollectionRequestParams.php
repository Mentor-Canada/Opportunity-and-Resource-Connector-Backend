<?php

namespace Drupal\app\Affiliates\Zip;

class ZipCollectionRequestParams
{
    public $offset;
    public $limit;
    public $filter;

    public function __construct()
    {
        $page = \Drupal::request()->get('page');
        $this->offset = $page['offset'] ?? 0;
        $this->limit = $page['limit'] ?? 50;
        $this->filter = \Drupal::request()->get('filter');
    }
}
