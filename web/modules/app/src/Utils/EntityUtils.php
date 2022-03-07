<?php

namespace Drupal\app\Utils;

use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

class EntityUtils
{
    public static function administeredBy($uid, $type)
    {
        $q = "SELECT entity_id FROM node__field_administrators
            WHERE field_administrators_target_id = $uid
            AND bundle = '$type'
            AND langcode = 'en'
            ";
        $database = \Drupal::database();
        $query = $database->query($q);
        $result = $query->fetchAll();
        $ids = array_map(function ($a) {
            return $a->entity_id;
        }, $result);
        return $ids;
    }

    public static function isAdmin($node, $accountProxy)
    {
        $account = User::load($accountProxy->id());
        $globalAdmin = $account->get('field_global_administrator')->getValue()[0]['value'];
        if ($globalAdmin == '1') {
            return true;
        }
        $rootNode = $node->getTranslation('en');
        $admins = $rootNode->get('field_administrators')->getValue();
        foreach ($admins as $row) {
            if ($row['target_id'] == $accountProxy->id()) {
                return true;
            }
        }
        return false;
    }

    public static function isOrganizationAdmin($node)
    {
        if (!$node->hasField('field_organization_entity')) {
            return false;
        }
        $organizationId = $node->get('field_organization_entity')->getValue()[0]['target_id'];
        if ($organizationId) {
            $organization = Node::load($organizationId);
            if ($organization) {
                $account = \Drupal::currentUser();
                if (EntityUtils::isAdmin($organization, $account)) {
                    return true;
                }
            }
        }
        return false;
    }

    public static function isRegionalAdmin($node)
    {
        $uid = \Drupal::currentUser()->id();

        $q = "SELECT TRIM(LEADING '0' FROM postal_code) FROM programs_locations WHERE entity_id = :entity_id";
        $zips = \Drupal::database()->query($q, [":entity_id" => $node->id()])->fetchCol(0);

        if (!count($zips)) {
            return false;
        }

        $q = \Drupal::database()->select('node__field_zips', 'zips');
        $q->condition('zips.field_zips_value', $zips, 'IN');

        $q->leftJoin('node__field_administrators', 'admins', 'admins.entity_id = zips.entity_id');
        $q->condition('field_administrators_target_id', $uid);

        return $q->countQuery()->execute()->fetchField() ? true : false;
    }
}
