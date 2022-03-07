<?php

namespace Drupal\app_search;

use stdClass;

class GoogleLocationDecorator
{
    private stdClass $googleLocation;

    public $type;
    public $name;
    public $postalCode;
    public $lat;
    public $lng;
    public $country;
    public $province;
    public $components;

    public static function createFromLatLng($lat, $lng): ?GoogleLocationDecorator
    {
        $location = SearchUtils::getLocationFromLatLng($lat, $lng);
        if ($location) {
            return new GoogleLocationDecorator($location);
        }
        return null;
    }

    public function __construct(stdClass $googleLocation)
    {
        $this->googleLocation = $googleLocation;
        $this->type = $googleLocation->types[0];
        $this->name = $googleLocation->formatted_address;
        $this->postalCode = $this->getValue('postal_code');
        $this->lat = $googleLocation->geometry->location->lat;
        $this->lng = $googleLocation->geometry->location->lng;
        $this->country = $this->getValue('country');
        $this->province = $this->getValue('administrative_area_level_1');
        $this->components = json_encode($this->googleLocation->address_components);
    }

    public function serialize()
    {
        return [
            'type' => $this->type,
            'name' => $this->name,
            'postal_code' => $this->postalCode,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'country' => $this->country,
            'province' => $this->province,
            'components' => $this->components
        ];
    }

    private function getValue($type)
    {
        foreach ($this->googleLocation->address_components as $component) {
            if (in_array($type, $component->types)) {
                return $component->long_name;
            }
        }
    }
}
