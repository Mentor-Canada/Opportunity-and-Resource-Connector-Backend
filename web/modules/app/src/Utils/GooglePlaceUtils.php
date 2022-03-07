<?php

namespace Drupal\app\Utils;

class GooglePlaceUtils
{
    public static function getComponent($type, $addressComponents)
    {
        foreach ($addressComponents as $addressComponent) {
            $types = is_array($addressComponent) ? $addressComponent['types'] : $addressComponent->types;
            if (in_array($type, $types)) {
                return is_array($addressComponent) ? $addressComponent['long_name'] : $addressComponent->long_name;
            }
        }
    }

    public static function getWithId($placeId)
    {
        $result = \Drupal::database()->query("SELECT data FROM locations_cache WHERE placeId = :placeId", [
            ":placeId" => $placeId
        ])->fetchCol();
        if (count($result)) {
            $result = json_decode($result[0]);
            return $result;
        }

        $url = "https://maps.googleapis.com/maps/api/place/details/json?key={$_ENV['GOOGLE_API_KEY']}&place_id=${placeId}";
        $data = file_get_contents($url);
        $data = json_decode($data);

        \Drupal::database()->query("INSERT INTO locations_cache SET placeId = :placeId, data = :data", [
            ":placeId" => $placeId,
            ":data" => json_encode($data->result)
        ]);

        return $data->result;
    }

    public static function getWithAddress($address)
    {
        $address = urlencode($address);

        $result = \Drupal::database()->query("SELECT data FROM locations_cache WHERE address = :address", [
            ":address" => $address
        ])->fetchCol();
        if (count($result)) {
            $result = json_decode($result[0]);
            return $result;
        }
        $url = "https://maps.googleapis.com/maps/api/geocode/json?key={$_ENV['GOOGLE_API_KEY']}&address=${address}";
        $data = file_get_contents($url);
        $data = json_decode($data);
        if ($data->status == "ZERO_RESULTS") {
            return null;
        }

        $result = $data->results[0];
        \Drupal::database()->query("REPLACE INTO locations_cache SET placeId = :placeId, address = :address, data = :data", [
            ":placeId" => $result->place_id,
            ":address" => $address,
            ":data" => json_encode($result)
        ]);

        return $result;
    }
}
