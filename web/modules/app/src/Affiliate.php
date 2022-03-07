<?php

namespace Drupal\app;

use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

class Affiliate
{
    public $id;
    public $name;

    public static function createWithProgramId($id): ?Affiliate
    {
        $sql = "SELECT postal_code FROM programs_locations WHERE entity_id = :entity_id";
        $rows = \Drupal::database()->query($sql, [':entity_id' => $id])->fetchCol(0);
        $zips = [];
        foreach ($rows as $row) {
            $zips[] = intval($row);
        }
        if (!count($zips)) {
            return null;
        }

        $q = \Drupal::database()->select('node__field_zips', 'zips');
        $q->addField('zips', 'entity_id');
        $q->condition('field_zips_value', $zips, 'IN');
        $result = $q->execute()->fetchAll();
        if (count($result)) {
            return self::createWithAffiliateId($result[0]->entity_id);
        }
        return null;
    }

    public static function createWithAffiliateId($id): ?Affiliate
    {
        $node = Node::load($id);
        if ($node) {
            $affiliate = new Affiliate();
            $affiliate->name = $node->get('title')->getValue()[0]['value'];
            $affiliate->id = $node->id();
            return $affiliate;
        }
        return null;
    }

    public function getAdministrators()
    {
        $sql = "SELECT field_administrators_target_id FROM node__field_administrators WHERE entity_id = :entity_id";
        $uids = \Drupal::database()->query($sql, [":entity_id" => $this->id])->fetchCol();
        return User::loadMultiple($uids);
    }
}
