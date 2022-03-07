<?php

namespace Drupal\app_search;

use Drupal\app\Collection\CollectionBuilderBase;
use Drupal\app\SearchFields;

class SearchCollectionBuilder extends CollectionBuilderBase
{
    public $q;

    public function __construct()
    {
        $db = \Drupal::database();
        $this->q = $db->select("searches");
        $this->q->addField("searches", "zip");
        $this->q->addField("searches", "email");
        $this->q->addField("searches", "role");
        $this->q->addField("searches", "created");
        $this->q->leftJoin("node__field_display_title", "partnerDisplayTitle", "searches.partnerId = partnerDisplayTitle.entity_id");
        $this->q->addField("partnerDisplayTitle", "field_display_title_value", "partnerTitle");

        $routeName = \Drupal::routeMatch()->getRouteName();
        if ($routeName == "app.search.collection.csv") {
            $this->q->addField("searches", SearchFields::first_name);
            $this->q->addField("searches", SearchFields::last_name);
            $this->q->addField("searches", SearchFields::email);
            $this->q->addField("searches", SearchFields::focus);
            $this->q->addField("searches", SearchFields::age);
            $this->q->addField("searches", SearchFields::grade);
            $this->q->addField("searches", SearchFields::youth);
            $this->q->addField("searches", SearchFields::type_of_mentoring);
            $this->q->addField("searches", SearchFields::how_did_you_hear_about_us);
            $this->q->addField("searches", SearchFields::how_did_you_hear_about_us_other);
            $this->q->addField("searches", SearchFields::city);
            $this->q->addField("searches", SearchFields::state);
        }
    }

    public function partner($nid): SearchCollectionBuilder
    {
        if ($nid) {
            $this->q->condition('partnerId', $nid);
        }
        return $this;
    }

    public function execute()
    {
        return $this->q->execute()->fetchAll();
    }

    public function executeCount()
    {
        return $this->q->countQuery()->execute()->fetchField();
    }
}
