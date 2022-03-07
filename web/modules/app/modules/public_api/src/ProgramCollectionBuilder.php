<?php

namespace Drupal\app_public_api;

class ProgramCollectionBuilder
{
    public $q;

    public function __construct()
    {
        $db = \Drupal::database();

        /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->q = $db->select('node', 'node');
        $this->q->condition('node.type', 'programs');
        $this->q->addField('node', 'uuid', 'id');

        $this->q->leftJoin("programs", "programs", "node.nid = programs.entity_id");
        $this->q->addField("programs", "title", 'name');
        $this->q->addField("programs", "programDescription", "description");

        $sql = "CREATE TEMPORARY TABLE locations SELECT entity_id, JSON_ARRAYAGG(JSON_OBJECT('name', JSON_EXTRACT(location, '$.formatted_address'), 'lat', JSON_EXTRACT(location, '$.geometry.location.lat'), 'lng', JSON_EXTRACT(location, '$.geometry.location.lng'))) as locations FROM programs_locations GROUP BY entity_id";
        \Drupal::database()->query($sql);
        $this->q->leftJoin("locations", "locations", "node.nid = locations.entity_id");
        $this->q->addField("locations", "locations");

        $this->q->addField("programs", "email");
        $this->q->addField("programs", "phone");
        $this->q->leftJoin("node__field_standing", "standing", "node.nid = standing.entity_id");
        $this->q->condition("standing.field_standing_value", "app-allowed");
        $this->organizationApproval();
    }

    private function organizationApproval()
    {
        $this->q->leftJoin('node__field_organization_entity', 'organization_entity', "node.nid = organization_entity.entity_id");
        $this->q->leftJoin('node__field_standing', 'organization_standing_entity', "organization_entity.field_organization_entity_target_id = organization_standing_entity.entity_id");

        $orGroup = $this->q->orConditionGroup()
      ->isNull('organization_entity.entity_id')
      ->condition('organization_standing_entity.field_standing_value', 'app-allowed')
    ;
        $this->q->condition($orGroup);
    }

    public function range($start, $length)
    {
        if ($start || $length) {
            if (!$start) {
                $start = 0;
            }
            if (!$length) {
                $length = 10;
            }
            $this->q->range($start, $length);
        }
    }

    public function execute(): array
    {
        $rows = $this->q->execute()->fetchAll();
        foreach ($rows as &$row) {
            $row->name = json_decode($row->name);
            $row->locations = json_decode($row->locations);
            $row->description = json_decode($row->description);
            $row->website = [
                "en" => "{$_ENV['CLIENT_URL']}/en/program/{$row->id}",
                "fr" => "{$_ENV['CLIENT_URL']}/fr/program/{$row->id}"
            ];
        }
        return $rows;
    }

    public function executeCount(): int
    {
        return $this->q->countQuery()->execute()->fetchCol()[0];
    }
}
