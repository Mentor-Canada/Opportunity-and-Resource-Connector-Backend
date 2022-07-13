<?php

namespace Drupal\app_search;

use Drupal\app\Utils\GooglePlaceUtils;

class SearchParamsAdapter implements SearchParamsInterface
{
    private $params;
    private $place;

    public function __construct($location, $params)
    {
        $this->params = $params;
        $this->place = GooglePlaceUtils::getWithAddress($location);
    }

    public function distance(): ?string
    {
        return $this->params['distance'];
    }

    public function lat(): ?string
    {
        return $this->place->geometry->location->lat;
    }

    public function lng(): ?string
    {
        return $this->place->geometry->location->lng;
    }

    public function location(): ?object
    {
        return null;
    }

    public function postalCode(): ?string
    {
        return null;
    }

    public function limit(): ?int
    {
        return $this->params['page']['limit'];
    }

    public function offset(): ?int
    {
        return $this->params['page']['offset'];
    }

    public function communityBased(): ?string
    {
        return $this->params['communityBased'] !== "false";
    }

    public function siteBased(): ?string
    {
        return $this->params['siteBased'] !== "false";
    }

    public function eMentoring(): ?string
    {
        return $this->params['eMentoring'] !==  "false";
    }

    public function age(): ?array
    {
        if (!empty($this->params['age'])) {
            return explode(',', $this->params['age']);
        }
        return null;
    }

    public function grade(): ?array
    {
        if($this->params['grade'] != 'all') {
            return [$this->params['grade']];
        }
        return null;
    }

    public function focus(): ?array
    {
        if (!empty($this->params['focus'])) {
            return explode(',', $this->params['focus']);
        }
        return null;
    }

    public function youth(): ?array
    {
        if (!empty($this->params['youth'])) {
            return explode(',', $this->params['youth']);
        }
        return null;
    }

    public function type(): ?array
    {
        if($this->params['type'] != 'all') {
            return [$this->params['type']];
        }
        return null;
    }
}
