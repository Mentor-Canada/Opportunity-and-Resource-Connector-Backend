<?php

namespace Drupal\app_search;

use Drupal\app\RequestAdapterBase;
use Drupal\app\Utils\Utils;
use Symfony\Component\HttpFoundation\Request;

class SearchRequestAdapter extends RequestAdapterBase
{
    public $partnerNid;
    public $notify;

    public function __construct(Request $request)
    {
        parent::__construct($request);

        if ($this->filter['field_partner_entity.id']) {
            $partnerNode = Utils::loadNodeByUUid($this->filter['field_partner_entity.id']);
            $this->partnerNid = $partnerNode->id();
        }

        if ($this->filter['notify-filter']) {
            $this->notify = $this->filter['notify-filter']['condition']['value'];
        }
    }
}
