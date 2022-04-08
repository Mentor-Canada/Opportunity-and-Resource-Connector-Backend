<?php

namespace Drupal\app_inquiry;

use Drupal\app\Collection\CollectionBuilderBase;
use Drupal\app\Factories\UserFactory;
use Drupal\app\SearchFields;
use Drupal\app\Utils\Utils;
use Drupal\app_program\ProgramCollectionBuilder;
use Drupal\node\Entity\Node;

class InquiryCollectionBuilder extends CollectionBuilderBase
{
    public $q;

    public function __construct()
    {
        $db = \Drupal::database();
        $this->q = $db->select('inquiries');

        $this->q->addField("inquiries", ApplicationFields::how_did_you_hear_about_us);
        $this->q->addField("inquiries", ApplicationFields::how_did_you_hear_about_us_other);
        $this->q->addField("inquiries", ApplicationFields::role);
        $this->q->addField("inquiries", ApplicationFields::status);
        $this->q->addField("inquiries", ApplicationFields::first_name);
        $this->q->addField("inquiries", ApplicationFields::last_name);
        $this->q->addField("inquiries", ApplicationFields::email);
        $this->q->addField("inquiries", ApplicationFields::phone);
        $this->q->addField("inquiries", ApplicationFields::call);
        $this->q->addField("inquiries", ApplicationFields::sms);
        $this->q->addField("inquiries", ApplicationFields::uuid);
        $this->q->addField("inquiries", ApplicationFields::created);
        $this->q->addField("inquiries", ApplicationFields::recipientEmail);

        // program
        $this->q->leftJoin("node", "programNode", "inquiries.programId = programNode.nid");
        $this->q->leftJoin('programs', 'programs', 'programNode.nid = programs.entity_id');
        $this->q->addField("programs", "entity_id");
        $this->q->addField("programNode", "uuid", 'program_uuid');
        $this->q->addExpression("JSON_UNQUOTE(JSON_EXTRACT(programs.title, '$.en')) COLLATE utf8mb4_unicode_ci", ApplicationFields::programTitle);

        // organization
        $this->q->leftJoin("node__field_organization_entity", "organizationEntity", "programNode.nid = organizationEntity.entity_id");
        $this->q->leftJoin("organizations", "flatOrganizations", "organizationEntity.field_organization_entity_target_id = flatOrganizations.entity_id");
        $this->q->leftJoin("node", "organizationNode", "organizationNode.nid = organizationEntity.field_organization_entity_target_id");
        $this->q->addField("organizationNode", "uuid", "organization_uuid");
        $this->q->addExpression("json_unquote(json_extract(flatOrganizations.title, '$.en')) COLLATE utf8mb4_unicode_ci", 'organization_title');

        // searches
        $this->q->leftJoin("searches", "searches", "inquiries.searchId = searches.id");
        $this->q->addField("searches", "zip", SearchFields::zip);
        $this->q->addField("searches", "city", SearchFields::city);
        $this->q->addField("searches", "state", SearchFields::state);

        // partnerTitle
        $this->q->leftJoin("node__field_display_title", "partnerDisplayTitle", "searches.partnerId = partnerDisplayTitle.entity_id");
        $this->q->addField("partnerDisplayTitle", "field_display_title_value", ApplicationFields::partnerTitle);
        // partnerId
        $this->q->leftJoin("node__field_id", "partnerId", "searches.partnerId = partnerId.entity_id");
        $this->q->addField("partnerId", "field_id_value", ApplicationFields::partnerId);
    }

    public function start($start)
    {
        if (!empty($start)) {
            $this->q->condition('inquiries.created', $start, '>=');
        }
        return $this;
    }

    public function end($end)
    {
        if (!empty($end)) {
            $this->q->condition('inquiries.created', $end + 24 * 60 * 60, '<=');
        }
        return $this;
    }


    public function organization($value)
    {
        if (!empty($value)) {
            $value = json_decode($value);
            $select = \Drupal::database()->select("node__field_organization_entity", "org");
            $select->addField("org", "entity_id");
            $select->leftJoin("node", "node", "node.nid = org.field_organization_entity_target_id");
            $select->condition("node.uuid", $value);
            $ids = $select->execute()->fetchAll();
            $ids = array_column($ids, "entity_id");
            $ids = !count($ids) ? [0] : $ids;
            $this->q->condition("inquiries.programId", $ids, "IN");
        }
        return $this;
    }

    public function filter($key, $value)
    {
        $nullValues = ["app-un-contacted"];
        $value = json_decode($value);
        if (in_array($value, $nullValues)) {
            $this->q->isNull($key);
            return;
        }

        if (is_array($value)) {
            $this->q->condition($key, $value, "IN");
            return;
        }

        if ($key == 'inquiries.programId') {
            $this->q->condition($key, $value);
            return;
        }

        $this->q->condition($key, "%$value%", "LIKE");
    }

    public function ids($ids): InquiryCollectionBuilder
    {
        $this->q->condition('inquiries.id', $ids, 'IN');
        return $this;
    }

    public function programFilter($uuid): InquiryCollectionBuilder
    {
        if ($uuid) {
            $node = Utils::loadNodeByUUid($uuid);
            if (!$node) {
                $node = Node::load($uuid);
            }
            $type = $node->getType();
            $ids = [];
            if ($type == 'programs') {
                $ids = [$node->id()];
            } elseif ($type == 'filter') {
                $mode = $node->get('field_date_mode')->getValue()[0]['value'];
                $dateRange = new DateRangeAdapter($mode);
                $builder = (new ProgramCollectionBuilder())
          ->start($dateRange->start)
          ->end($dateRange->end)
          ->whereUserIsStaff(UserFactory::currentUser())
        ;
                $programs = $builder->execute();
                $ids = array_map(fn ($a) => $a->nid, $programs);
            }
            if (count($ids)) {
                $this->q->condition('program.field_program_target_id', $ids, "IN");
            } else {
                $this->q->isNull('program.field_program_target_id');
            }
        }
        return $this;
    }

    public function roleFilter($role): InquiryCollectionBuilder
    {
        if ($role) {
            $this->q->condition('role.field_role_value', $role);
        }
        return $this;
    }

    public function statusFilter($status): InquiryCollectionBuilder
    {
        if ($status) {
            if ($status == 'app-contacted') {
                $this->q->condition('status.field_status_value', 'app-contacted');
            } else {
                $this->q->isNull('status.field_status_value');
            }
        }
        return $this;
    }

    public function leadFilter($lead): InquiryCollectionBuilder
    {
        if ($lead) {
            $this->q->condition('how.field_how_did_you_hear_about_us_value', $lead);
        }
        return $this;
    }

    public function leadOtherFilter($leadOther): InquiryCollectionBuilder
    {
        if ($leadOther) {
            $this->q->condition('how_other.field_how_did_you_hear_other_value', "%$leadOther%", "LIKE");
        }
        return $this;
    }

    public function firstNameFilter($firstName): InquiryCollectionBuilder
    {
        if ($firstName) {
            $this->q->condition('first_name.field_first_name_value', "%$firstName%", "LIKE");
        }
        return $this;
    }

    public function lastNameFilter($lastName): InquiryCollectionBuilder
    {
        if ($lastName) {
            $this->q->condition('last_name.field_last_name_value', "%$lastName%", "LIKE");
        }
        return $this;
    }

    public function emailFilter($email): InquiryCollectionBuilder
    {
        if ($email) {
            $this->q->condition('email.field_email_value', "%$email%", "LIKE");
            $this->q->condition('status.field_status_value', 'app-contacted');
        }
        return $this;
    }

    public function phoneFilter($leadOther): InquiryCollectionBuilder
    {
        if ($leadOther) {
            $this->q->condition('phone.field_phone_value', "%$leadOther%", "LIKE");
            $this->q->condition('status.field_status_value', 'app-contacted');
        }
        return $this;
    }

    public function smsFilter($sms): InquiryCollectionBuilder
    {
        if ($sms) {
            if ($sms == 'app-yes') {
                $this->q->condition('sms.field_sms_value', true);
            } else {
                $this->q->isNull('sms.field_sms_value');
            }
        }
        return $this;
    }

    public function voiceFilter($voice): InquiryCollectionBuilder
    {
        if ($voice) {
            if ($voice == 'app-yes') {
                $this->q->condition('call.field_call_value', true);
            } else {
                $this->q->isNull('call.field_call_value');
            }
        }
        return $this;
    }

    public function range($offset, $limit): InquiryCollectionBuilder
    {
        $this->q->range($offset, $limit);
        return $this;
    }

    public function execute()
    {
        $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
        $rows = $this->q->execute()->fetchAll();
        return $rows;
    }

    public function executeCount()
    {
        return $this->q->countQuery()->execute()->fetchField();
    }
}
