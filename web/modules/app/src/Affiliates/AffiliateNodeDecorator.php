<?php

namespace Drupal\app\Affiliates;

use Drupal\app\Utils\Utils;
use Drupal\node\Entity\Node;

class AffiliateNodeDecorator
{
    public Node $node;
    public $title;
    public $created;

    private function __construct()
    {
    }

    public static function withUUID($uuid): AffiliateNodeDecorator
    {
        $affiliate = new AffiliateNodeDecorator();
        $node = Utils::loadNodeByUUid($uuid);
        if ($node->getType() != 'region') {
            throw new \Exception("Invalid entity type");
        }
        $affiliate->node = $node;
        return $affiliate;
    }

    public static function createWithImportId($importId): AffiliateNodeDecorator
    {
        $q = \Drupal::entityQuery('node');
        $q->condition(AffiliateFields::importId, $importId);
        $q->condition('type', 'region');
        $ids = $q->execute();

        $affiliate = new AffiliateNodeDecorator();

        if (!count($ids)) {
            $affiliate->node = Node::create([
                'type' => 'region',
                AffiliateFields::importId => $importId
            ]);
        } else {
            $id = current($ids);
            $affiliate->node = Node::load($id);
        }

        return $affiliate;
    }

    public function addZip($zip)
    {
        /* @var $list \Drupal\Core\Field\FieldItemList */
        $list = $this->node->get('field_zips');
        $values = $list->getValue();
        array_push($values, ["value" => $zip]);
        $values = $this->unique($values);
        $list->setValue($values);
        $this->node->save();
    }

    public function addZips($zips)
    {
        /* @var $list \Drupal\Core\Field\FieldItemList */
        $list = $this->node->get('field_zips');
        $values = $list->getValue();
        $mapped = array_map(function ($zip) {
            return ["value" => $zip];
        }, $zips);
        $values = array_merge($values, $mapped);
        $values = $this->unique($values);
        $list->setValue($values);
        $this->node->save();
    }

    public function removeZips($zips)
    {
        set_time_limit(600);
        /* @var $list \Drupal\Core\Field\FieldItemList */
        $list = $this->node->get('field_zips');
        $values = $list->getValue();
        $filtered = [];
        foreach ($list as $value) {
            $zip = $value->getValue()['value'];
            if (in_array($zip, $zips)) {
                continue;
            }
            $filtered[] = $value->getValue()['value'];
        }
        $list->setValue($filtered);
        $this->node->save();
    }

    public function removeZip($zip)
    {
        /* @var $list \Drupal\Core\Field\FieldItemList */
        $list = $this->node->get('field_zips');
        $values = $list->getValue();
        foreach ($values as $key => $value) {
            if ($value['value'] == $zip) {
                unset($values[$key]);
            }
        }
        $list->setValue($values);
        $this->node->save();
    }

    private function unique($values): array
    {
        $result = [];
        foreach ($values as $value) {
            if (!in_array($result, $value)) {
                array_push($result, $value);
            }
        }
        return $result;
    }

    public function save()
    {
        if ($this->title) {
            $this->node->set('title', $this->title);
        }
        if ($this->created) {
            $this->node->set('created', $this->created);
        }
        $this->node->save();
    }

    public function addAdmin($uid)
    {
        $administrators = $this->node->get('field_administrators');
        $administrators->appendItem($uid);
        $this->node->set('field_administrators', $uid);
        $this->node->save();
    }
}
