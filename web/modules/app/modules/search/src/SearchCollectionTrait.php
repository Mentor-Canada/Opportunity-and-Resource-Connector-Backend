<?php

namespace Drupal\app_search;

use Drupal\app\SearchFields;

trait SearchCollectionTrait
{
    private function addSearchFields()
    {
        $this->q->leftJoin('node__field_partner_entity', 'partner', 'search.nid = partner.entity_id');
        $this->q->leftJoin('node__field_display_title', 'partner_title', 'partner.field_partner_entity_target_id = partner_title.entity_id');
        $this->q->addField('partner_title', 'field_display_title_value');

        $this->q->leftJoin('node__field_role', 'role', 'search.nid = role.entity_id');
        $this->q->addField('role', 'field_role_value');

        $this->q->leftJoin('node__field_zip', 'zip', 'search.nid = zip.entity_id');
        $this->q->addField('zip', 'field_zip_value');

        $this->addField('field_email');

        $routeName = \Drupal::routeMatch()->getRouteName();
        if ($routeName == "app.search.collection.json") {
            return;
        }

        $this->q->leftJoin('node__field_how_did_you_hear_about_us', 'how', 'search.nid = how.entity_id');
        $this->q->addField('how', 'field_how_did_you_hear_about_us_value');

        $this->q->leftJoin('node__field_how_did_you_hear_other', 'how_other', 'search.nid = how_other.entity_id');
        $this->q->addField('how_other', 'field_how_did_you_hear_other_value');

        $this->addMultipleValueField(SearchFields::type_of_mentoring);
        $this->addMultipleValueField(SearchFields::youth);
        $this->addMultipleValueField(SearchFields::age);
        $this->addMultipleValueField(SearchFields::grade);
        $this->addMultipleValueField(SearchFields::focus);

        $this->addField('field_first_name');
        $this->addField('field_last_name');
    }
}
