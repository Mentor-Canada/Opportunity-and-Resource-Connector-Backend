<?php

namespace Drupal\app\Affiliates;

use Drupal\app\RequestAdapterBase;
use Symfony\Component\HttpFoundation\Request;

class AffiliateRequestAdapter extends RequestAdapterBase
{
    public $title;

    public function __construct(Request $request)
    {
        parent::__construct($request);
        if (isset($this->filter['title'])) {
            $this->title = $this->filter['title'];
        }
    }
}
