<?php

namespace Drupal\app\Utils;

use Drupal\Core\Access\AccessResult;

class RegionUtils
{
    public static function access($node, $op, $account)
    {
        return AccessResult::allowed();
    }

    public static function getList($uid)
    {
        $q = \Drupal::entityQuery('node');
        $q->condition('type', 'region');
        $q->condition('field_administrators.target_id', $uid);
        $ids = $q->execute();
        return array_values($ids);
    }
}
