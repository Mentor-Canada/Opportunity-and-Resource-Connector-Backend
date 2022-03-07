<?php

namespace Drupal\app_search;

use Drupal\app\Utils\Utils;
use Drupal\node\Entity\Node;

class SearchUtils
{
    public static function getLocationFromLatLng($lat, $lng)
    {
        $result = file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?latlng={$lat},{$lng}&key={$_ENV['GOOGLE_API_KEY']}&types=street_address");
        $result = json_decode($result);
        return $result->results[0];
    }

    public static function presaveLocation(&$entity)
    {
        $zip = $entity->get('field_zip')->getValue()[0]['value'];
        $address = Utils::geocode($zip);
        $t = $address->result->types[0];
        $entity->set('field_physical_location', [
            'place_id' => $address['place_id'],
            'name' => $address['formatted_address'],
            'lat' => $address['geometry']['location']['lat'],
            'lng' => $address['geometry']['location']['lng'],
            'components' => json_encode($address['address_components']),
            'type' => $t
        ]);
    }

    public static function getPartnerId($partnerId)
    {
        if ($partnerId) {
            $database = \Drupal::database();
            $q = "SELECT entity_id FROM node__field_id WHERE field_id_value = :partnerId";
            $rows = $database->query($q, [':partnerId' => $partnerId])->fetchAssoc();
            if ($rows === false) {
                $partner = Node::create([
                    'type' => 'partner',
                    'title' => 'Partner',
                    'field_id' => $partnerId,
                    'field_display_title' => $partnerId
                ]);
                $partner->save();
                return $partner->id();
            } else {
                $row = current($rows);
                return $row;
            }
        }
    }
}
