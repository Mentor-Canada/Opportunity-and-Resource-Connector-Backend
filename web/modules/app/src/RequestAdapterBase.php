<?php

namespace Drupal\app;

use Symfony\Component\HttpFoundation\Request;

class RequestAdapterBase
{
    public $request;
    protected $filter;

    public $sortField;
    public $sortDirection;
    public $createdStartDate;
    public $createdEndDate;
    public $limit;
    public $offset;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->filter = $this->request->query->get('filter');
        $this->setSort();
        $this->setDate();
        $this->limit = $this->request->query->get('page')['limit'];
        $this->offset = $this->request->query->get('page')['offset'];
    }

    protected function setSort()
    {
        $field = $this->request->query->get('sort');
        $direction = 'ASC';
        if (mb_substr($field, 0, 1) == '-') {
            $field = substr($field, 1);
            $direction = 'DESC';
        }
        $this->sortField = $field;
        $this->sortDirection = $direction;
    }

    protected function setDate()
    {
        if ($startDateFilter = $this->filter['start-date-filter']) {
            $this->createdStartDate = $startDateFilter['condition']['value'];
        }
        if ($endDateFilter = $this->filter['end-date-filter']) {
            $this->createdEndDate = $endDateFilter['condition']['value'];
        }
    }
}
