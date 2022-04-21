<?php

namespace Drupal\app_search;

class ResultSearchAdapter implements SearchParamsInterface
{
    private $distance;
    private $age;
    private $grade;
    private $focus;
    private $youth;
    private $type;
    private $lat;
    private $lng;
    private $location;
    private $postalCode;
    private $limit;
    private $offset;
    private $communityBased;
    private $siteBased;
    private $eMentoring;

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

    public function distance(): ?string
    {
        return $this->distance;
    }

    public function age(): ?array
    {
        return $this->age;
    }

    public function grade(): ?array
    {
        return $this->grade;
    }

    public function focus(): ?array
    {
        return $this->focus;
    }

    public function youth(): ?array
    {
        return $this->youth;
    }

    public function type(): ?array
    {
        return $this->type;
    }

    public function lat(): ?string
    {
        return $this->lat;
    }

    public function lng(): ?string
    {
        return $this->lng;
    }

    public function location(): ?object
    {
        return $this->location;
    }

    public function postalCode(): ?string
    {
        return $this->postalCode;
    }

    public function limit(): ?int
    {
        return intval($this->limit);
    }

    public function offset(): ?int
    {
        return intval($this->offset);
    }

    public function communityBased(): ?string
    {
        return $this->communityBased;
    }

    public function siteBased(): ?string
    {
        return $this->siteBased;
    }

    public function eMentoring(): ?string
    {
        return $this->eMentoring;
    }
}
