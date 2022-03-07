<?php

namespace Drupal\app_search;

use Drupal\app\Collection\CollectionBuilderBase;
use Drupal\app_program\ProgramFields;

class ResultCollectionBuilder extends CollectionBuilderBase
{
    public $q;

    private $adapter;
    private $view;
    protected $alias = "n";
    private $_priority;
    private ?SortAlgorithmInterface $_sortAlgorithm = null;

    public function __construct($adapter = null, $view = null)
    {
        $this->adapter = $adapter;
        $this->view = $view;

        $this->q = \Drupal::database()->select('node', 'n');
        $this->q->condition('type', 'programs');
        $this->q->addField('n', 'UUID');
        $this->q->addField('n', 'nid');

        $this->q->leftJoin("programs", "programs", "n.nid = programs.entity_id");
        $this->q->addField("programs", "communityBased");
        $this->q->addField("programs", "siteBased");
        $this->q->addField("programs", "eMentoring");
        $this->q->addField("programs", "responsivenessTier");
        $this->q->addField("programs", "NQMS");
        $this->q->addField("programs", "ADA");
        $this->q->addExpression("json_unquote(json_extract(programs.title, '$.en'))", ProgramFields::displayTitle);
        $this->q->addExpression("UPPER(title)", 'sortTitle');

        $this->q->condition('standing.field_standing_value', 'app-allowed');
        $this->organizationApproval();
        $this->q->leftJoin('node__field_standing', 'standing', "n.nid = standing.entity_id");

        $notRecruitingIds = array_column(\Drupal::database()->query("SELECT entity_id
        FROM node__field_program_accepting
        WHERE field_program_accepting_value = 'app-program-currently-not-recruiting'
        OR field_program_accepting_value = 'app-program-no-public-recruitment'
           ")->fetchAll(), 'entity_id');
        if (count($notRecruitingIds)) {
            $this->q->condition('nid', $notRecruitingIds, 'NOT IN');
        }

        if ($view == 'neonone') {
            $this->addMultipleValueField(ProgramFields::typesOfMentoring);
            $this->q->addExpression("json_unquote(json_extract(programs.programDescription, '$.en'))", ProgramFields::description);
            $this->q->addExpression("json_unquote(json_extract(programs.mentorDescription, '$.en'))", ProgramFields::mentorDescription);
            $this->addField(ProgramFields::mentorMonthCommit);
            $this->addMultipleValueField(ProgramFields::agesServed);
            $this->addMultipleValueField(ProgramFields::youthServed);
            $this->addField(ProgramFields::primaryMeetingLocation);
            return;
        }

        $this->q->leftJoin('node__field_program_ages_served', 'ages_served', "n.nid = ages_served.entity_id");
        $this->q->leftJoin('node__field_focus_area', 'focus_area', "n.nid = focus_area.entity_id");
        $this->q->leftJoin('node__field_program_youth_served', 'youth_served', "n.nid = youth_served.entity_id");
        $this->q->leftJoin('node__field_types_of_mentoring', 'types_of_mentoring', "n.nid = types_of_mentoring.entity_id");

        // logo
        $this->q->leftJoin('node__field_logo', 'logo', 'n.nid = logo.entity_id');
        $this->q->leftJoin('file_managed', 'file', 'logo.field_logo_target_id = file.fid');
        $this->q->addField('file', 'uri');

        // organization
        $this->q->leftJoin('node__field_organization_entity', 'organization', "n.nid = organization.entity_id");
        $this->q->leftJoin(
            'node__field_display_title',
            'organization_display_title_en',
            "organization.field_organization_entity_target_id = organization_display_title_en.entity_id AND organization_display_title_en.langcode = 'en'"
        );
        $this->q->leftJoin(
            'node__field_display_title',
            'organization_display_title_fr',
            "organization.field_organization_entity_target_id = organization_display_title_fr.entity_id AND organization_display_title_fr.langcode = 'fr'"
        );
        $this->q->addField('organization_display_title_en', 'field_display_title_value', 'organization_title_en');
        $this->q->addField('organization_display_title_fr', 'field_display_title_value', 'organization_title_fr');

        $this->organizationLogo();
    }

    private function organizationLogo()
    {
        $this->q->leftJoin('node__field_logo', 'organization_logo', 'organization.field_organization_entity_target_id = organization_logo.entity_id');
        $this->q->addField('organization_logo', 'field_logo_target_id');
        $this->q->leftJoin('file_managed', 'organization_file', 'organization_logo.field_logo_target_id = organization_file.fid');
        $this->q->addField('organization_file', 'uri', 'organization_uri');
    }

    private function organizationApproval()
    {
        $this->q->leftJoin('node__field_organization_entity', 'organization_entity', "n.nid = organization_entity.entity_id");
        $this->q->leftJoin('node__field_standing', 'organization_standing_entity', "organization_entity.field_organization_entity_target_id = organization_standing_entity.entity_id");
        $this->q->addField('organization_entity', 'entity_id', 'organization_id');
        $this->q->addField('organization_standing_entity', 'field_standing_value', 'organization_standing');

        $orGroup = $this->q->orConditionGroup()
      ->isNull('organization_entity.entity_id')
      ->condition('organization_standing_entity.field_standing_value', 'app-allowed')
    ;
        $this->q->condition($orGroup);
    }

    public function communityBased()
    {
        $this->q->leftJoin("communityDistances", "communityDistances", "communityDistances.entity_id = n.nid");
        $this->q->addField("communityDistances", "distance", "communityDistance");
        return $this;
    }

    public function siteBased()
    {
        $this->q->leftJoin("resultDistances", "distances", "distances.entity_id = n.nid");
        $this->q->addExpression("postal_code = '{$this->adapter->postalCode}' AND responsivenessTier <= 2", "postalCodeMatch");
        $this->q->addField("distances", "distance");
        $this->q->addField("distances", "url", "googleMapUrl");
        return $this;
    }

    public function physical()
    {
        $this->q->addExpression("
CASE
  WHEN communityDistances.distance IS NULL THEN distances.distance
  WHEN distances.distance IS NULL THEN communityDistances.distance
  WHEN communityDistances.distance < distances.distance THEN communityDistances.distance
  ELSE distances.distance
END
    ", "physicalDistance");
        $this->q->addExpression("
CASE
  WHEN distances.postal_code = '{$this->adapter->postalCode}' AND responsivenessTier <= 2 THEN 1
  ELSE 0
END
    ", "physicalPostalCodeMatch");
        return $this;
    }

    public function eMentoring()
    {
        return $this;
    }

    public function sortAlgorithm(?SortAlgorithmInterface $sortAlgorithm): ResultCollectionBuilder
    {
        if ($sortAlgorithm) {
            $this->_sortAlgorithm = $sortAlgorithm;
        }
        return $this;
    }

    public function age($age): ResultCollectionBuilder
    {
        if ($age) {
            $this->q->condition('field_program_ages_served_value', $age, "IN");
        }
        return $this;
    }

    public function grade($grade): ResultCollectionBuilder
    {
        if ($grade) {
            $this->q->leftJoin('node__field_program_grades_served', 'grade', "n.nid = grade.entity_id");
            $this->q->condition('field_program_grades_served_value', $grade, "IN");
        }
        return $this;
    }

    public function focus($focus): ResultCollectionBuilder
    {
        if ($focus) {
            $this->q->condition('field_focus_area_value', $focus, "IN");
        }
        return $this;
    }

    public function youth($youth): ResultCollectionBuilder
    {
        if ($youth) {
            $this->q->condition('field_program_youth_served_value', $youth, "IN");
        }
        return $this;
    }

    public function type($type): ResultCollectionBuilder
    {
        if ($type) {
            $this->q->condition('field_types_of_mentoring_value', $type, "IN");
        }
        return $this;
    }

    public function postalCode($postalCode): ResultCollectionBuilder
    {
        if ($postalCode == 'app-national') {
            $this->q->condition('field_e_mentoring_service_area_value', 'app-nationwide');
        }
        return $this;
    }

    public function range($limit, $offset = 0): ResultCollectionBuilder
    {
        if ($limit) {
            $start = $limit * $offset;
            $this->q->range($start, $limit);
        }
        return $this;
    }

    public function ids($ids)
    {
        if (count($ids)) {
            $this->q->condition('nid', $ids, 'IN');
        } else {
            $this->q->isNull('nid');
        }
        return $this;
    }

    public function priority($ids)
    {
        if (count($ids)) {
            $rows = [];
            foreach ($ids as $key => $id) {
                $value = count($ids) - $key;
                $rows[] = "WHEN n.nid = $id THEN $value";
            }
            $expression = "CASE " . implode(" ", $rows) . " END";
            $this->q->addExpression($expression, "priority");
        } else {
            $this->q->addExpression("0", "priority");
        }
        return $this;
    }

    public function execCount()
    {
        return $this->q->distinct()->countQuery()->execute()->fetchField();
    }

    public function exec(): array
    {
        if ($this->_sortAlgorithm) {
            $this->_sortAlgorithm->sort($this->q);
        }

        $lang = \Drupal::languageManager()->getCurrentLanguage()->getId();
        $rows = $this->q->distinct()->execute()->fetchAll();
        foreach ($rows as $row) {
            if (!empty($row->uri)) {
                $row->url = file_create_url($row->uri);
            }
            if (!empty($row->organization_uri)) {
                $row->organization_logo_url = file_create_url($row->organization_uri);
            }

            $titleKey = "organization_title_{$lang}";
            $row->organization_title = $row->$titleKey;
            if ($lang != 'en' && empty($row->organization_title)) {
                $row->organization_title = $row->$titleKey;
            }
        }
        return $rows;
    }

    protected function addField($fieldName, $langcode = null, $join = true, $column = 'value', $joinTable = 'n')
    {
        parent::addField($fieldName, null, true, 'value', $joinTable);
    }
}
