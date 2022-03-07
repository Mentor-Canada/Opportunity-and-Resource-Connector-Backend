<?php

namespace Drupal\app\Affiliates\Zip;

class ZipCollection
{
    public array $data;
    public array $links = [];

    public function __construct($data, $total, $limit, $offset)
    {
        $this->data = $data;

        $pagination = new Pagination();
        $pagination->total = $total;
        $pagination->per_page = $limit;
        $pagination->current_page = floor($offset / $limit) + 1;
        $pagination->last_page = floor($total / $limit) + 1;
        $pagination->from = $offset + 1;
        if ($pagination->from > $pagination->total) {
            $pagination->from = $pagination->total;
        }
        $pagination->to = $offset + $limit;
        if ($pagination->to > $pagination->total) {
            $pagination->to = $pagination->total;
        }

        $this->links['pagination'] = $pagination;
    }
}
