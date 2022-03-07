<?php

namespace Drupal\app\Plugin\rest\resource;

use Drupal\rest\Annotation\RestResource;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * @RestResource(
 *   id = "account_stats_resource",
 *   label = @Translation("Account Stats Resource"),
 *   uri_paths = {
 *     "canonical" = "/a/utils/stats/account"
 *   }
 * )
 */
class AccountStatsResource extends ResourceBase
{
    public function get()
    {
        $database = \Drupal::database();
        $params = [];
        $where = [];
        if (isset($_REQUEST['mail'])) {
            // TODO: Escape string!
            $mail = $_REQUEST['mail'];
            $where[] = "users_field_data.mail LIKE '%$mail%'";
//      $params[':mail'] = $_REQUEST['mail'];
        }
        if (isset($_REQUEST['start'])) {
            $where[] = "users_field_data.created >= :start";
            $params[':start'] = $_REQUEST['start'];
        }
        if (isset($_REQUEST['end'])) {
            $where[] = "users_field_data.created <= :end";
            $params[':end'] = $_REQUEST['end'] + 24 * 60 * 60;
        }
        $where[] = "users_field_data.uid > 0";
        $where = implode(" AND ", $where);
        if ($where != '') {
            $where = "WHERE $where";
        }
        $q = "SELECT COUNT(*) FROM users
    LEFT JOIN users_field_data ON users.uid = users_field_data.uid
    $where";
        $result = $database->query($q, $params)->fetchAssoc();
        $searches = $result['COUNT(*)'];

        $response = ['data' => [
            'searches' => $searches
        ]];
        return new ResourceResponse($response);
    }
}
