<?php

namespace Drupal\app_program;

use Drupal\app\Collection\CollectionBuilderBase;
use Drupal\app\Decorators\UserDecorator;
use Drupal\app\Utils\ProgramUtils;

class ProgramCollectionBuilder extends CollectionBuilderBase
{
    public $q;
    protected ProgramRequestAdapter $adapter;

    private $programsWithInquiriesQ;

    public function __construct($adapter)
    {
        $this->adapter = $adapter;
        $this->alias = "node";

        $db = \Drupal::database();
        /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->q = $db->select('node', 'node');
        $this->q->addField('node', 'nid');

        $this->q->leftJoin('node_field_data', 'data', 'node.nid = data.nid');
        $this->q->addField('data', 'created');
        $this->q->condition('node.type', 'programs');
        $this->q->condition('data.langcode', 'en');

        $this->q->addField('node', 'uuid', 'id');

        $this->q->leftJoin("programs", "programs", "node.nid = programs.entity_id");
        $this->q->addExpression("json_unquote(json_extract(programs.title, '$.en'))", ProgramFields::displayTitle);

        if ($adapter->view == 'inquiry') {
            $this->programsWithInquiriesQ = $db->select('inquiries');
            $this->programsWithInquiriesQ->addField('inquiries', 'programId');
            $this->q->condition('node.nid', $this->programsWithInquiriesQ, 'IN');

            $this->q->leftJoin($this->contactedQuery(true), 'contacted', 'node.nid = contacted.programId');
            $this->q->addField('contacted', 'total', 'contacted');

            $this->q->leftJoin($this->contactedQuery(false), 'uncontacted', 'node.nid = uncontacted.programId');
            $this->q->addField('uncontacted', 'total', 'uncontacted');

            return;
        }

        $this->addOrganizationTitle();
        $this->addOrganizationUUID();

        $this->q->addField("programs", "first_name", ProgramFields::firstName);
        $this->q->addField("programs", "last_name", ProgramFields::lastName);
        $this->q->addField("programs", "email", ProgramFields::email);
        $this->q->addField("programs", "position", ProgramFields::position);
        $this->q->addField("programs", "phone", ProgramFields::phone);
        $this->q->addField("programs", "altPhone", ProgramFields::altPhone);
        $this->q->addField("programs", "responsivenessTier");
        $this->q->addField("programs", "source");

        $this->addField(ProgramFields::standing);

        $routeName = \Drupal::routeMatch()->getRouteName();
        if ($routeName != "app.program.collection.csv") {
            return;
        }

        $this->q->addExpression("json_unquote(json_extract(programs.programDescription, '$.en'))", ProgramFields::description);
        $this->q->addExpression("json_unquote(json_extract(programs.mentorDescription, '$.en'))", ProgramFields::mentorDescription);

        $this->addMultipleValueField(ProgramFields::accepting);
        $this->addField(ProgramFields::facebook);
        $this->addField(ProgramFields::focusArea);
        $this->addField(ProgramFields::focusAreaOther);
        $this->addField(ProgramFields::instagram);
        $this->addMultipleValueField(ProgramFields::physicalLocations, "name");
        $this->addField(ProgramFields::primaryMeetingLocation);
        $this->addField(ProgramFields::primaryMeetingLocationOther);
        $this->addMultipleValueField(ProgramFields::agesServed);
        $this->addField(ProgramFields::agesOther);
        $this->addField(ProgramFields::delivery);
        $this->addField(ProgramFields::eMentoringServiceArea);
//    $this->addField(ProgramFields::matchesExplain);
        $this->addMultipleValueField(ProgramFields::familyServed);
        $this->addField(ProgramFields::familyOther);
        $this->addMultipleValueField(ProgramFields::gendersServed);
        $this->addField(ProgramFields::gendersOther);
        $this->addMultipleValueField(ProgramFields::gradesServed);

        $this->addField(ProgramFields::genderMentorOther);
        $this->addMultipleValueField(ProgramFields::howAreMeetingsScheduled);
        $this->addField(ProgramFields::menteesWaitingList);
        $this->addField(ProgramFields::howOther);
        $this->addField(ProgramFields::trainsMentors);
        $this->addField(ProgramFields::ageMentorOther);

        $this->addField(ProgramFields::mustTrainMentors);
        $this->addMultipleValueField(ProgramFields::operatedThrough);
        $this->addField(ProgramFields::operatedOther);

        $this->addField(ProgramFields::typesOfMentoringOther);

        $this->addField(ProgramFields::twitter);
        $this->addMultipleValueField(ProgramFields::typesOfMentoring);
        $this->addField(ProgramFields::website);

        /** Program Details */
        $this->addMultipleValueField(ProgramFields::agesMentorTarget);
        $this->addMultipleValueField(ProgramFields::genderMentorTarget);
        $this->addField(ProgramFields::genderMentorOther);
        $this->addField(ProgramFields::youthPerYear);
        $this->addMultipleValueField(ProgramFields::youthServed);
        $this->addField(ProgramFields::youthOther);

//    /** CA National Standards **/
//    $this->addField(ProgramFields::screensMentors);
//    $this->addField(ProgramFields::screensMentees);
//    $this->addMultipleValueField(ProgramFields::screensMentorsHow);
//    $this->addMultipleValueField(ProgramFields::screensMenteesHow);
//    $this->addMultipleValueField(ProgramFields::matchesHow);
//    $this->addField(ProgramFields::ongoingSupport);
//    $this->addField(ProgramFields::beginningAndEnd);
//    $this->addField(ProgramFields::hasSpecificGoals);
//    $this->addField(ProgramFields::trainsMentees); // unused?
//    $this->addField(ProgramFields::trainsMenteesHow); // unused?
//    $this->addField(ProgramFields::whichGoals); // unused?
//    $this->addField(ProgramFields::whichGoalsOther); //unused?

        /** USA National Standards */
        $this->addField(ProgramFields::nsBgCheck);
        $this->addMultipleValueField(ProgramFields::nsBgCheckTypes);
        $this->addField(ProgramFields::nsBgFingerprintType);
        $this->addField(ProgramFields::nsBgNameType);
        $this->addField(ProgramFields::nsPeerType);
        $this->addMultipleValueField(ProgramFields::nsBgOtherTypes);
        $this->addField(ProgramFields::nsTraining);
//    $this->addField(ProgramFields::nsTrainingDescription);
        $this->q->addExpression("json_unquote(json_extract(programs.trainingDescription, '$.en'))", ProgramFields::nsTrainingDescription);

        /** Shared National Standards */
        $this->addField(ProgramFields::mentorMonthCommit);
        $this->addField(ProgramFields::mentorMonthOther);
        $this->addField(ProgramFields::mentorHourCommit);
        $this->addField(ProgramFields::mentorHourOther);
        $this->addField(ProgramFields::mentorFreqCommit);
        $this->addField(ProgramFields::mentorFreqOther);

        /** Administrative */
        $this->addAdministrators();
    }

    public function flatFilter($field, $value)
    {
        $q = \Drupal::database()->select("programs", $field);
        $q->addField($field, "entity_id");
        $value = json_decode($value);
        $value = "%$value%";
        if ($field === 'trainingDescription') {
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

    public function localizedFilter($field, $value)
    {
        $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
        $value = json_decode($value);
        $value = "%$value%";

        $result = \Drupal::database()->query("SELECT entity_id FROM programs WHERE LOWER(JSON_EXTRACT($field, '$.{$language}')) LIKE LOWER(:value)", [
            ":value" => $value
        ])->fetchAll();
        $result = array_map(fn ($row) => $row->entity_id, $result);

        if (count($result)) {
            $this->q->condition('node.nid', $result, 'IN');
        } else {
            $this->q->isNull('node.nid');
        }
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
        $q->addField('inquiries', 'programId');
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
        $q->groupBy('programId');
        return $q;
    }

    private function addAdministrators()
    {
        \Drupal::database()->query("CREATE TEMPORARY TABLE administrators
        SELECT DISTINCT entity_id, JSON_ARRAYAGG(users_field_data.mail) AS emails
        FROM node__field_administrators
        LEFT JOIN users_field_data ON field_administrators_target_id = users_field_data.uid
        GROUP BY entity_id
    ");
        $this->q->leftJoin("administrators", "administrators", "node.nid = administrators.entity_id");
        $this->q->addField("administrators", "emails", ProgramFields::administrators);
    }

    private function addOrganizationTitle()
    {
        $this->q->leftJoin("node__field_organization_entity", "organizationEntity", "node.nid = organizationEntity.entity_id");
        $this->q->leftJoin("organizations", "flatOrganizations", "organizationEntity.field_organization_entity_target_id = flatOrganizations.entity_id");
        $this->q->addExpression("json_unquote(json_extract(flatOrganizations.title, '$.en')) COLLATE utf8mb4_unicode_ci", 'organization_title');
    }

    private function addOrganizationUUID()
    {
        $this->q->leftJoin("node__field_organization_entity", "organizationEntity", "node.nid = organizationEntity.entity_id");
        $this->q->leftJoin("node", "nodes", "organizationEntity.field_organization_entity_target_id = nodes.nid");
        $this->q->addField("nodes", "uuid", "organization_uuid");
    }

    public function title($title): ProgramCollectionBuilder
    {
        if (!empty($title)) {
//      $this->q->leftJoin('node__field_display_title', 'title', 'node.nid = title.entity_id');
            $this->q->condition('information.field_display_title_value', "%{$title}%", 'LIKE');
//      $this->q->condition('information.langcode', 'en');
        }
        return $this;
    }

    //  function description($value): ProgramCollectionBuilder {
//    if(!empty($value)) {
//      $field = ProgramFields::description;
//      $this->q->condition("{$field}_value", "%{$value}%", 'LIKE');
//    }
//    return $this;
    //  }

    public function filterLocation($value)
    {
        $q = \Drupal::database()->select("programs_locations");
        $q->addField("programs_locations", "entity_id");

        $value = json_decode($value);
        $value = "%$value%";
        $q->where('programs_locations.location COLLATE utf8mb4_unicode_ci LIKE :value', [':value' => $value]);
        $result = $q->execute()->fetchCol();
        if (count($result)) {
            $this->q->condition('node.nid', $result, 'IN');
        } else {
            $this->q->isNull('node.nid');
        }
    }

    public function whereUserIsStaff(UserDecorator $user): ProgramCollectionBuilder
    {
        if (!$user->isGlobalAdministrator()) {
            $programs = ProgramUtils::programsForUser();
            if (count($programs)) {
                $this->q->condition('node.nid', $programs, 'IN');
            } else {
                $this->q->isNull('node.nid');
            }
        }
        return $this;
    }

    public function execute()
    {
        return $this->q->distinct()->execute()->fetchAll();
    }

    public function executeCount()
    {
        return $this->q->distinct()->countQuery()->execute()->fetchField();
    }
}
