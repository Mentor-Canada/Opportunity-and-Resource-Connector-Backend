<?php

namespace Drupal\app_inquiry;

use Symfony\Component\HttpFoundation\JsonResponse;

class InquiryFilterController
{
    public function collection(): JsonResponse
    {
        $db = \Drupal::database();
        $q = $db->select('node', 'node');
        $this->addField($q, FilterFields::type);
        $q->condition(FilterFields::type . "_value", "inquiry");

        $q->addField('node', 'uuid', 'id');
        $q->condition('node.type', 'filter');

        $q->leftJoin('node_field_data', 'data', 'node.nid = data.nid');
        $q->addField('data', 'created');
        $account = \Drupal::currentUser();
        $q->condition('data.uid', $account->id());

        $q->leftJoin('node__field_display_title', 'title', 'node.nid = title.entity_id');
        $q->addField('title', 'field_display_title_value', FilterFields::title);

        $q->leftJoin('node__field_start_time', 'start', 'node.nid = start.entity_id');
        $q->addField('start', 'field_start_time_value', FilterFields::start_time);

        $q->leftJoin('node__field_end_time', 'end', 'node.nid = end.entity_id');
        $q->addField('end', 'field_end_time_value', FilterFields::end_time);

        $q->leftJoin('node__field_filter_entity', 'filter_entity', 'node.nid = filter_entity.entity_id');
        $q->addField('filter_entity', 'field_filter_entity_target_id', FilterFields::filter_entity);

        $q->leftJoin('node__field_date_mode', 'date_mode', 'node.nid = date_mode.entity_id');
        $q->addField('date_mode', 'field_date_mode_value', FilterFields::date_mode);

        $q->orderBy('field_display_title_value');

        $collection = $q->execute()->fetchAll();
//    $includedFilterIds = array_map(fn($row) => $row->{FilterFields::filter_entity}, $collection);

//    if(count($includedFilterIds)) {
//      $includedFilters = (new InquiryCollectionBuilder())
//        ->ids($includedFilterIds)
//        ->execute();
//
//      foreach($collection as &$row) {
//        $filter = array_filter($includedFilters, function($a) use($row) {
//          return $a->nid == $row->{FilterFields::filter_entity};
//        });
//        $entity = array_values($filter)[0];
//        $row->entity = $entity;
//      }
//    }

        return new JsonResponse(["data" => $collection]);
    }

    private function addField($q, $fieldName, $langcode = null, $join = true)
    {
        $name = str_replace("field_", '', $fieldName);
        if ($join) {
            $q->leftJoin("node__field_$name", "$name", "node.nid = $name.entity_id");
        }
        $q->addField($name, "field_{$name}_value", "field_$name");
        if (!empty($langcode)) {
            $q->condition("$name.langcode", $langcode);
        }
    }
}
