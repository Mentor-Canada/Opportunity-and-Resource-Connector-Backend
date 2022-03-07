<?php

namespace rest\organization;

use GuzzleHttp\RequestOptions;
use rest\request_objects\GuzzleMultipartObject;
use rest\request_objects\InnerNode;
use rest\request_objects\RequestNode;
use rest\Session;

class OrganizationUtils
{
    public static function getParams($frenchOnly = false): OrganizationParams
    {
        $email = getenv('EMAIL') ?: 'organization@example.com';

        $params = new OrganizationParams();
        $params->title->en = "My Organization";
        $params->title->fr = "Mon Organisme";
        $params->contactFirstName = "John";
        $params->contactLastName = "Smith";
        $params->contactEmail = $email;
        $params->location = self::getLocation();
        $params->website = "example.com";
        $params->contactPhone = "4509999999";
        $params->contactAlternatePhone = "5149999999";
        $params->legalName = "My Organization Inc";
        $params->feedback = "Test, disregard";
        $params->type = "app-organization-type-business";
        $params->typeOther = "";
        $params->taxStatus = "app-organization-tax-status-not-for-profit";
        $params->taxStatusOther = "";
        $params->contactPosition = "app-organization-position-vice-president";
        $params->contactPositionOther = "";
        $params->description->en = "Organization Create Test";
        $params->description->fr = "Description en Francais";
        $params->hasLocation = "yes";
        $params->mtgEnabled = "0";

        if ($frenchOnly) {
            $params->title->en = "Mon Organisme";
            $params->description->en = '';
        }
        return $params;
    }

    public static function createOrganization($frenchOnly = false)
    {
        $anonymousSession = new Session();
        $innerData = self::getDataObject($frenchOnly);
        $data = $innerData->transformToDataArrayIncludingPhoto();
        $data = [RequestOptions::MULTIPART => $data];
        $response = $anonymousSession->request('POST', 'a/app/organization', $data);
        return json_decode($response->getBody());
    }

    public static function getDataObject($frenchOnly = false)
    {
        $innerData = new GuzzleMultipartObject();
        $innerData->contents->nodes = new RequestNode();
        $innerData->contents->nodes->en = new InnerNode("node--organization");
        $innerData->contents->nodes->fr = new InnerNode("node--organization");
        $innerData->contents->uilang = $frenchOnly ? 'en' : 'fr';
        $innerData->contents->additional = self::getParams($frenchOnly);
        return $innerData;
    }

    public static function getLocation()
    {
        return [
            'address_components' =>
              [
                  0 =>
                    [
                        'long_name' => '4030',
                        'short_name' => '4030',
                        'types' =>
                          [
                              0 => 'street_number',
                          ],
                    ],
                  1 =>
                    [
                        'long_name' => 'Rue Saint-Ambroise',
                        'short_name' => 'Rue Saint-Ambroise',
                        'types' =>
                          [
                              0 => 'route',
                          ],
                    ],
                  2 =>
                    [
                        'long_name' => 'Le Sud-Ouest',
                        'short_name' => 'Le Sud-Ouest',
                        'types' =>
                          [
                              0 => 'sublocality_level_1',
                              1 => 'sublocality',
                              2 => 'political',
                          ],
                    ],
                  3 =>
                    [
                        'long_name' => 'Montréal',
                        'short_name' => 'Montréal',
                        'types' =>
                          [
                              0 => 'locality',
                              1 => 'political',
                          ],
                    ],
                  4 =>
                    [
                        'long_name' => 'Communauté-Urbaine-de-Montréal',
                        'short_name' => 'Communauté-Urbaine-de-Montréal',
                        'types' =>
                          [
                              0 => 'administrative_area_level_2',
                              1 => 'political',
                          ],
                    ],
                  5 =>
                    [
                        'long_name' => 'Québec',
                        'short_name' => 'QC',
                        'types' =>
                          [
                              0 => 'administrative_area_level_1',
                              1 => 'political',
                          ],
                    ],
                  6 =>
                    [
                        'long_name' => 'Canada',
                        'short_name' => 'CA',
                        'types' =>
                          [
                              0 => 'country',
                              1 => 'political',
                          ],
                    ],
                  7 =>
                    [
                        'long_name' => 'H4C 2E1',
                        'short_name' => 'H4C 2E1',
                        'types' =>
                          [
                              0 => 'postal_code',
                          ],
                    ],
              ],
            'adr_address' => '<span class="street-address">4030 Rue Saint-Ambroise</span>, <span class="locality">Montréal</span>, <span class="region">QC</span> <span class="postal-code">H4C 2E1</span>, <span class="country-name">Canada</span>',
            'formatted_address' => '4030 Rue Saint-Ambroise, Montréal, QC H4C 2E1, Canada',
            'geometry' =>
              [
                  'location' =>
                    [
                        'lat' => 45.4750775,
                        'lng' => -73.5805699,
                    ],
                  'viewport' =>
                    [
                        'south' => 45.4738117197085,
                        'west' => -73.58205498029152,
                        'north' => 45.47650968029149,
                        'east' => -73.5793570197085,
                    ],
              ],
            'icon' => 'https://maps.gstatic.com/mapfiles/place_api/icons/v1/png_71/geocode-71.png',
            'icon_background_color' => '#7B9EB0',
            'icon_mask_base_uri' => 'https://maps.gstatic.com/mapfiles/place_api/icons/v2/generic_pinlet',
            'name' => '4030 Rue Saint-Ambroise',
            'place_id' => 'ChIJSXq7R4MQyUwRrjYsnSF5c80',
            'plus_code' =>
              [
                  'compound_code' => 'FCG9+2Q Montreal, QC, Canada',
                  'global_code' => '87Q8FCG9+2Q',
              ],
            'reference' => 'ChIJSXq7R4MQyUwRrjYsnSF5c80',
            'types' =>
              [
                  0 => 'street_address',
              ],
            'url' => 'https://maps.google.com/?q=4030+Rue+Saint-Ambroise,+Montr%C3%A9al,+QC+H4C+2E1,+Canada&ftid=0x4cc9108347bb7a49:0xcd7379219d2c36ae',
            'utc_offset' => -300,
            'vicinity' => 'Le Sud-Ouest',
            'html_attributions' =>
              [
              ],
            'utc_offset_minutes' => -300,
        ];
    }

    public static function getLocationData()
    {
        return [
            "address_components" => [
                [
                    "long_name" => "02150",
                    "short_name" => "02150",
                    "types" => [
                        "postal_code"
                    ]
                ],
                [
                    "long_name" => "Chelsea",
                    "short_name" => "Chelsea",
                    "types" => [
                        "locality",
                        "political"
                    ]
                ],
                [
                    "long_name" => "Suffolk County",
                    "short_name" => "Suffolk County",
                    "types" => [
                        "administrative_area_level_2",
                        "political"
                    ]
                ],
                [
                    "long_name" => "Massachusetts",
                    "short_name" => "MA",
                    "types" => [
                        "administrative_area_level_1",
                        "political"
                    ]
                ],
                [
                    "long_name" => "United States",
                    "short_name" => "US",
                    "types" => [
                        "country",
                        "political"
                    ]
                ]
            ],
            "adr_address" => "<span class=\"locality\">Chelsea</span>, <span class=\"region\">MA</span> <span class=\"postal-code\">02150</span>, <span class=\"country-name\">USA</span>",
            "formatted_address" => "Chelsea, MA 02150, USA",
            "geometry" => [
                "location" => [
                    "lat" => 42.4000656,
                    "lng" => -71.0319478
                ],
                "viewport" => [
                    "south" => 42.38320900250412,
                    "west" => -71.05740397699788,
                    "north" => 42.41422593445861,
                    "east" => -71.00758899435277
                ]
            ],
            "icon" => "https://maps.gstatic.com/mapfiles/place_api/icons/v1/png_71/geocode-71.png",
            "icon_background_color" => "#7B9EB0",
            "icon_mask_base_uri" => "https://maps.gstatic.com/mapfiles/place_api/icons/v2/generic_pinlet",
            "name" => "02150",
            "place_id" => "ChIJBxHiZrJx44kRo0DsrmIYHAM",
            "reference" => "ChIJBxHiZrJx44kRo0DsrmIYHAM",
            "types" => [
                "postal_code"
            ],
            "url" => "https://maps.google.com/?q=02150&ftid=0x89e371b266e21107:0x31c1862aeec40a3",
            "utc_offset" => -240,
            "vicinity" => "Chelsea",
            "html_attributions" => [],
            "utc_offset_minutes" => -240
        ];
    }

    private function getCountry()
    {
        return $_ENV['COUNTRY'] == null ? 'ca' : $_ENV['COUNTRY'];
    }
}
