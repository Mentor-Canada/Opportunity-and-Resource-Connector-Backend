<?php

namespace rest\search;

use rest\Request;

class SearchBuilder
{
    public array $attributes;

    public function __construct()
    {
        $this->attributes = (array)SearchUtils::params();
    }

    public function execute()
    {
        $searchData = new SearchOuterDataObject();
        $searchData->data = new SearchInnerDataObject();
        $searchData->data->attributes = $this->attributes;
        $payload = (array)$searchData;
        $response = (new Request())
            ->uri('a/app/search')
            ->data($payload)
            ->execute();
        return $response;
    }

}
