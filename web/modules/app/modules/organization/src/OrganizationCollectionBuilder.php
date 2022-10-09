<?php

namespace Drupal\app_organization;

use Drupal\app\Collection\CollectionBuilderBase;
use Drupal\app\Decorators\UserDecorator;
use Drupal\app_program\ProgramRequestAdapter;

class OrganizationCollectionBuilder extends CollectionBuilderBase
{
    protected ProgramRequestAdapter $adapter;
    private $programsWithInquiriesQ;

    public function __construct($adapter)
    {
        parent::__construct();
        $this->adapter = $adapter;

        $this->q->condition('node.type', 'organization');

//    $this->addField(OrganizationFields::displayTitle, 'en');
        $this->q->leftJoin('organizations', 'organizations', 'node.nid = organizations.entity_id');
        $this->q->addField('organizations', 'location');
        $this->q->addField('organizations', 'website');
        $this->q->addField('organizations', 'phone');
        $this->q->addField('organizations', 'alt_phone');
        $this->q->addField('organizations', 'first_name');
        $this->q->addField('organizations', 'last_name');
        $this->q->addField('organizations', 'legal_name');
        $this->q->addField('organizations', 'feedback');
        $this->q->addField('organizations', 'type');
        $this->q->addField('organizations', 'other_type');
        $this->q->addField('organizations', 'tax_status');
        $this->q->addField('organizations', 'other_tax_status');
        $this->q->addField('organizations', 'position');
        $this->q->addField('organizations', 'other_position');
        $this->q->addField('organizations', 'mentor_city_enabled');
        $this->q->addField('organizations', 'bbbsc_enabled');
        $this->q->addExpression("json_unquote(json_extract(organizations.title, '$.en')) COLLATE utf8mb4_unicode_ci", 'title');
        $this->q->addExpression("json_unquote(json_extract(organizations.title, '$.en')) COLLATE utf8mb4_unicode_ci", 'title_en');
        $this->q->addExpression("json_unquote(json_extract(organizations.title, '$.fr')) COLLATE utf8mb4_unicode_ci", 'title_fr');
        $this->q->addField('organizations', 'has_location');
        $this->q->addField('organizations', 'description');
        $this->q->addExpression("json_unquote(json_extract(organizations.description, '$.en')) COLLATE utf8mb4_unicode_ci", 'description_en');
        $this->q->addExpression("json_unquote(json_extract(organizations.description, '$.fr')) COLLATE utf8mb4_unicode_ci", 'description_fr');
        $this->q->addField('organizations', 'mtg_enabled');
        $this->q->addField('organizations', 'email');

        if (\Drupal::currentUser()->isAnonymous()) {
            $this->q->leftJoin("node__field_standing", "standing", "node.nid = standing.entity_id");
            if (empty($_REQUEST['show'])) {
                $this->q->condition('field_standing_value', 'app-allowed');
            } else {
                $group = $this->q
          ->orConditionGroup()
          ->condition('field_standing_value', 'app-allowed')
          ->condition('uuid', $_REQUEST['show']);
                $this->q->condition($group);
            }
            $this->q->orderBy(OrganizationFields::displayTitle);
            return;
        }

        if ($this->adapter->view == 'inquiry') {
            $db = \Drupal::database();
            $this->programsWithInquiriesQ = $db->select('inquiries');
            $this->programsWithInquiriesQ->leftJoin("node__field_organization_entity", "organization", "organization.entity_id = inquiries.programId");
            $this->programsWithInquiriesQ->addField('organization', 'field_organization_entity_target_id', 'organizationId');
            $this->q->condition('node.nid', $this->programsWithInquiriesQ, 'IN');

            $this->q->leftJoin($this->contactedQuery(true), 'contacted', 'node.nid = contacted.organizationId');
            $this->q->addField('contacted', 'total', 'contacted');

            $this->q->leftJoin($this->contactedQuery(false), 'uncontacted', 'node.nid = uncontacted.organizationId');
            $this->q->addField('uncontacted', 'total', 'uncontacted');

            return;
        }
        $this->q->addField('organizations', 'location');


        $this->addField(OrganizationFields::standing);
    }

    public function whereUserIsStaff(UserDecorator $user): OrganizationCollectionBuilder
    {
        if (!$user->isGlobalAdministrator()) {
            $this->q->leftJoin('node__field_administrators', 'administrators', 'node.nid = administrators.entity_id');
            $this->q->condition('administrators.field_administrators_target_id', $user->entity->id());
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

    public function start($start)
    {
        if (!empty($start)) {
            if ($this->adapter->view == 'inquiry') {
                $this->programsWithInquiriesQ->condition('inquiries.created', $start, '>=');
            } else {
                $this->q->condition('data.created', $start, '>=');
            }
        }
        return $this;
    }

    public function end($end)
    {
        if (!empty($end)) {
            if ($this->adapter->view == 'inquiry') {
                $this->programsWithInquiriesQ->condition('inquiries.created', $end + 24 * 60 * 60, '<=');
            } else {
                $this->q->condition('data.created', $end + 24 * 60 * 60, '<=');
            }
        }
        return $this;
    }

    private function contactedQuery($contacted)
    {
        $q = \Drupal::database()->select('inquiries');
        $q->leftJoin("node__field_organization_entity", "organization", "organization.entity_id = inquiries.programId");
        $q->addField('organization', 'field_organization_entity_target_id', 'organizationId');
        if ($contacted) {
            $q->isNotNull('status');
        } else {
            $q->isNull('status');
        }
        if (!empty($this->adapter->filter['start_date'])) {
            $q->condition('inquiries.created', $this->adapter->filter['start_date'], '>=');
        }
        if (!empty($this->adapter->filter['end_date'])) {
            $q->condition('inquiries.created', $this->adapter->filter['end_date'] + 24 * 60 * 60, '<=');
        }
        $q->addExpression('COUNT(*)', 'total');
        $q->groupBy('organization.field_organization_entity_target_id');
        return $q;
    }

    public function flatFilter($field, $value)
    {
        $q = \Drupal::database()->select("organizations", $field);
        $q->addField($field, "entity_id");
        $value = json_decode($value);
        $value = "%$value%";
        if (in_array($field, ['title', 'location', 'description'])) {
            $q->where("$field COLLATE utf8mb4_unicode_ci LIKE :value", [':value' => $value]);
        } else {
            $q->condition($field, $value, "LIKE");
        }
        $result = $q->execute()->fetchCol();
        if (count($result)) {
            $this->q->condition('node.nid', $result, 'IN');
        } else {
            $this->q->isNull('node.nid');
        }
    }
}
