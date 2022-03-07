<?php

namespace Drupal\app_search;

class ResultSearchAdapter
{
    public $distance;
    public $age;
    public $grade;
    public $focus;
    public $youth;
    public $type;
    public $lat;
    public $lng;
    public $location;
    public $postalCode;
    public $limit;
    public $offset;

    public $communityBased;
    public $siteBased;
    public $eMentoring;

    public function __construct($searchParams, $params)
    {
        $this->distance = $searchParams->distance;
        $this->age = $this->all(json_decode($searchParams->age));
        $this->grade = $this->all(json_decode($searchParams->grade));
        $this->focus = $this->all(json_decode($searchParams->focus));
        $this->youth = $this->all(json_decode($searchParams->youth));
        $this->type = $this->all(json_decode($searchParams->typeOfMentoring));
        $this->location = json_decode($searchParams->location);
        $this->communityBased = $searchParams->communityBasedDelivery == "1";
        $this->siteBased = $searchParams->siteBasedDelivery == "1";
        $this->eMentoring = $searchParams->eMentoringDelivery == "1";

        $this->lat = $searchParams->lat;
        $this->lng = $searchParams->lng;

        if ($this->location) {
            $googlePlace = new GoogleLocationDecorator($this->location);
            $this->postalCode = $googlePlace->postalCode;
        }

        $this->limit = $params['page']['limit'];
        $this->offset = $params['page']['offset'];
    }

    private function all($array)
    {
        if (in_array("all", $array)) {
            return null;
        }
        return $array;
    }
}
