<?php

namespace Drupal\app_program;

use Drupal\app\RequestAdapterBase;
use Symfony\Component\HttpFoundation\Request;

class ProgramRequestAdapter extends RequestAdapterBase
{
    public $filter;

    public $view;

    public function __construct(Request $request = null)
    {
        if ($request) {
            parent::__construct($request);
            $this->view = $request->query->get('view');

            if (isset($this->filter['delivery'])) {
                $delivery = json_decode($this->filter['delivery']);
                unset($this->filter['delivery']);
                $this->filter[$delivery] = 1;
            }
        }
    }

    protected function setSort()
    {
        parent::setSort();
        if ($this->sortField == "standing") {
            $this->sortField = 'standing.field_standing_value';
        }
    }

    public static function createFromRequest(Request $request): ProgramRequestAdapter
    {
        $adapter = new ProgramRequestAdapter($request);
        $adapter->view = $request->query->get('view');
        $adapter->filter = $request->query->get('filter');
        return $adapter;
    }

    public function getFilter($field)
    {
        $value = $this->filter[$field];
        if ($value) {
            $decoded = json_decode($value);
            $error = json_last_error();
        }
        return $error ? $value : $decoded;
    }
}
