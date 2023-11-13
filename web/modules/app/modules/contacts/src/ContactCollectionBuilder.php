<?php

namespace Drupal\app_contacts;

class ContactCollectionBuilder
{
    private ?string $filterType;
    private ?string $filterUuid;
    private int $limit;
    private int $offset;
    private int $totalOrganizationAmount;

    public function __construct($uuid = null, $type = null)
    {
        $this->filterType = $type;
        $this->filterUuid = $uuid;
        $fromIndex = $_REQUEST['fromIndex'] ?? 1;
        $toIndex = $_REQUEST['toIndex'] ?? (intval($fromIndex) + 24);
        $this->offset = intval($fromIndex) - 1;
        $this->limit = intval($toIndex);
    }

    public function getCollection()
    {
        if ($this->filterType === 'contact') {
            return ['data' => $this->getContact()];
        }
        if ($this->filterType === 'programs') {
            return ['data' => $this->getProgram()];
        }
        if ($this->filterType === 'organization') {
            return ['data' => $this->getOrganizations()];
        }

        $organizations = $this->getOrganizations();
        $entityList['meta']['totalData'] = $this->totalOrganizationAmount;
        $entityList['meta']['fromIndex'] = $this->offset + 1;
        $entityList['meta']['toIndex'] = $this->limit;
        $entityList['data'][] = $organizations;
        $entityList['programs-without-an-organization'] = $this->getProgramsWithoutAnOrganization();
        return $entityList;
    }

    private function getContact()
    {
        $q = \Drupal::database()->select('users', 'users');
        $q->addField('users', 'uuid');
        $q->condition('users.uuid', $this->filterUuid);
        $q->leftJoin('user__field_first_name', 'firstNames', 'users.uid = firstNames.entity_id');
        $q->addField('firstNames', 'field_first_name_value', 'firstName');
        $q->leftJoin('user__field_last_name', 'lastNames', 'users.uid = lastNames.entity_id');
        $q->addField('lastNames', 'field_last_name_value', 'lastName');
        $q->leftJoin('users_field_data', 'userData', 'users.uid = userData.uid');
        $q->addField('userData', 'mail', 'email');
        $result = $q->execute()->fetchAll();

        $retrievedContact = current($result);
        $contact = [
            'uuid' => $retrievedContact->uuid,
            'type' => 'contact',
            'firstName' => $retrievedContact->firstName,
            'lastName' => $retrievedContact->lastName,
            'email' => $retrievedContact->email
        ];
        return $contact;
    }

    private function getStatus($value)
    {
        if(empty($value)) {
          return "Pending";
        }
        return t($value, [], ['langcode' => 'en']);
    }

    private function getOrganizations()
    {
        $db = \Drupal::database();
        $q = $db->select('node', 'node');
        $q->addField('node', 'uuid');
        $q->addField('node', 'nid', 'entityId');
        $q->condition('node.type', 'organization');
        $q->leftJoin('organizations', 'flatOrg', 'node.nid = flatOrg.entity_id');
        $q->addField('flatOrg', 'title');
        $q->addField('flatOrg', 'description');
        $q->addField('flatOrg', 'legal_name', 'legalName');
        $q->addField('flatOrg', 'first_name', 'firstName');
        $q->addField('flatOrg', 'last_name', 'lastName');
        $q->addField('flatOrg', 'position');
        $q->addField('flatOrg', 'other_position', 'otherPosition');
        $q->addField('flatOrg', 'phone');
        $q->addField('flatOrg', 'alt_phone', 'altPhone');
        $q->addField('flatOrg', 'email', 'email');
        $q->addField('flatOrg', 'website', 'website');
        $q->addField('flatOrg', 'feedback');
        $q->addField('flatOrg', 'type');
        $q->addField('flatOrg', 'other_type', 'typeOther');
        $q->addField('flatOrg', 'tax_status', 'taxStatus');
        $q->addField('flatOrg', 'other_tax_status', 'taxStatusOther');
        $q->addField('flatOrg', 'mentor_city_enabled', 'VirtualMentoringPlatform');
        $q->addField('flatOrg', 'has_location', 'hasPhysicalAddress');

        $q->leftJoin('node__field_physical_location', 'location', 'node.nid = location.entity_id');
        $q->addField('flatOrg', 'location', 'address');

        $q->leftJoin('node__field_administrators', 'admins', 'node.nid = admins.entity_id');
        $q->addExpression("JSON_ARRAYAGG(admins.field_administrators_target_id)", 'adminTargetIds');
        $q->addExpression("(SELECT field_standing_value FROM node__field_standing WHERE node.nid = node__field_standing.entity_id AND node__field_standing.bundle = 'organization')", "status");

        $q->groupBy('node.nid');

        if (!$this->filterType) {
            $this->totalOrganizationAmount = $q->countQuery()->execute()->fetchField();
            $q->range($this->offset, ($this->limit - $this->offset));
        }
        if ($this->filterType === 'organization') {
            $q->condition('node.uuid', $this->filterUuid);
        }
        $organizations = $q->execute()->fetchAll();

        foreach ($organizations as $organization) {
            $organization->title = json_decode($organization->title);
            $organization->description = json_decode($organization->description);
            $organization->type = t($organization->type);
            $organization->typeOther = $organization->typeOther ?? "";
            $organization->taxStatus = t($organization->taxStatus);
            $organization->taxStatusOther = $organization->taxStatusOther ?? "";
            $organization->position = $organization->position ? t($organization->position) : "";
            $organization->otherPosition = $organization->otherPosition ?? "";
            $organization->MentorConnector = 'True';
            $organization->VirtualMentoringPlatform = $organization->VirtualMentoringPlatform == '1' ? 'True' : 'False';
            $address = json_decode($organization->address, true);
            $organization->address = $address['formatted_address'];
            $organization->status = self::getStatus($organization->status);
            $organization->hasPhysicalAddress = $organization->hasPhysicalAddress == '1' ? 'True' : 'False';

            $programs = $this->getProgramsForOrganization($organization);
            $organizationContacts = $this->getOrganizationContacts($organization);
            unset($organization->adminTargetIds);
            $organization->contacts = $organizationContacts;
            $organization->programs = $programs;
            $this->translateEntityFields($organization);
        }
        return $organizations;
    }

    private function getOrganizationContacts($organization)
    {
        $organizationAdmins = $this->getAssociatedAccounts($organization->adminTargetIds);
        $allOrganizationContacts = [];
        foreach ($organizationAdmins as $admin) {
            $additionalOrganizationAdmin = [
                'uuid' => $admin->uuid,
                'type' => 'organizationAdministrator',
                'firstName' => $admin->adminFirstName,
                'lastName' => $admin->adminLastName,
                'email' => $admin->adminEmail,
            ];
            if (!$additionalOrganizationAdmin['lastName']) {
                $additionalOrganizationAdmin['lastName'] = 'Unavailable';
            }
            $allOrganizationContacts[] = $additionalOrganizationAdmin;
        }
        return $allOrganizationContacts;
    }

    private function getProgram()
    {
        $sql = "SELECT
            node.uuid,
            'program' as type,
            programs.title,
            programs.first_name as  firstName,
            programs.last_name as  lastName,
            programs.position,
            programs.email,
            programs.phone,
            programs.altPhone,
            programs.communityBased,
            programs.siteBased,
            programs.eMentoring,
            node__field_facebook.field_facebook_value as facebook,
            node__field_twitter.field_twitter_value as twitter,
            node__field_website.field_website_value as website,
            node__field_instagram.field_instagram_value as instagram,
            focusAreaTable.field_focus_area_value as focusArea,
            focusAreaOtherTable.field_focus_area_other_value as focusAreaOther,
            primaryMeetingLocationTable.field_primary_meeting_location_value as primaryMeetingLocation,
            primaryMeetingLocationOtherTable.field_primary_meeting_loc_other_value as primaryMeetingLocationOther,
            node__field_program_youth_per_year.field_program_youth_per_year_value as youthPerYear,
            node__field_program_mentees_waiting_li.field_program_mentees_waiting_li_value as menteesWaitingList,
            adminUid,
            'True' AS 'MentorConnector',
            CASE WHEN mentorCityInvitations.status THEN 'True' ELSE 'False' END AS 'VirtualMentoringPlatform',
            (SELECT field_standing_value FROM node__field_standing WHERE node.nid = node__field_standing.entity_id AND node__field_standing.bundle = 'programs') AS status
            FROM node as node
            LEFT JOIN programs ON programs.entity_id = node.nid
            LEFT JOIN node__field_facebook ON node__field_facebook.entity_id = node.nid
            LEFT JOIN node__field_twitter ON node__field_twitter.entity_id = node.nid
            LEFT JOIN node__field_website ON node__field_website.entity_id = node.nid
            LEFT JOIN node__field_instagram ON node__field_instagram.entity_id = node.nid
            LEFT JOIN node__field_program_youth_per_year ON node__field_program_youth_per_year.entity_id = node.nid
            LEFT JOIN node__field_program_mentees_waiting_li ON node__field_program_mentees_waiting_li.entity_id = node.nid
            LEFT JOIN node__field_focus_area as focusAreaTable ON focusAreaTable.entity_id = node.nid
            LEFT JOIN node__field_focus_area_other as focusAreaOtherTable ON focusAreaOtherTable.entity_id = node.nid
            LEFT JOIN node__field_primary_meeting_location as primaryMeetingLocationTable ON primaryMeetingLocationTable.entity_id = node.nid
            LEFT JOIN node__field_primary_meeting_loc_other as primaryMeetingLocationOtherTable ON primaryMeetingLocationOtherTable.entity_id = node.nid
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(field_program_operated_through_value) as programOperatedThrough
                FROM node__field_program_operated_through
                GROUP BY entity_id) as programOperatedThrough ON programOperatedThrough.entity_id = node.nid
            LEFT JOIN node__field_program_operated_other as programOperatedThroughOtherTable ON programOperatedThroughOtherTable.entity_id = node.nid
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(field_program_how_are_meetings_s_value) as meetingsScheduled
                FROM node__field_program_how_are_meetings_s
                GROUP BY entity_id) as meetingsScheduled ON meetingsScheduled.entity_id = node.nid
            LEFT JOIN node__field_program_how_other as meetingsScheduledOtherTable ON meetingsScheduledOtherTable.entity_id = node.nid
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(field_program_genders_served_value) as programGendersServed
                FROM node__field_program_genders_served
                GROUP BY entity_id) as programGendersServed ON programGendersServed.entity_id = node.nid
            LEFT JOIN node__field_program_genders_other as programGendersServedOtherTable ON programGendersServedOtherTable.entity_id = node.nid
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(field_program_ages_served_value) as programAgesServed
                FROM node__field_program_ages_served
                GROUP BY entity_id) as programAgesServed ON programAgesServed.entity_id = node.nid
            LEFT JOIN node__field_program_ages_other as programAgesServedOtherTable ON programAgesServedOtherTable.entity_id = node.nid
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(field_program_family_served_value) as programfamilyServed
                FROM node__field_program_family_served
                GROUP BY entity_id) as programfamilyServed ON programfamilyServed.entity_id = node.nid
            LEFT JOIN node__field_program_family_other as programFamilyServedOtherTable ON programFamilyServedOtherTable.entity_id = node.nid
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(field_program_youth_served_value) as programYouthServed
                FROM node__field_program_youth_served
                GROUP BY entity_id) as programYouthServed ON programYouthServed.entity_id = node.nid
            LEFT JOIN node__field_program_youth_other as programYouthServedOtherTable ON programYouthServedOtherTable.entity_id = node.nid
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(field_program_gender_mentor_targ_value) as programMentorGenders
                FROM node__field_program_gender_mentor_targ
                GROUP BY entity_id) as programMentorGenders ON programMentorGenders.entity_id = node.nid
            LEFT JOIN node__field_program_gender_mentor_oth as programMentorGendersOtherTable ON programMentorGendersOtherTable.entity_id = node.nid
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(field_program_ages_mentor_target_value) as programMentorAges
                FROM node__field_program_ages_mentor_target
                GROUP BY entity_id) as programMentorAges ON programMentorAges.entity_id = node.nid
            LEFT JOIN node__field_program_age_mentor_other as programMentorAgesOtherTable ON programMentorAgesOtherTable.entity_id = node.nid
            LEFT JOIN node__field_ns_bg_check as nsBackgroundCheckTable ON nsBackgroundCheckTable.entity_id = node.nid
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(field_ns_bg_check_types_value) as nsBackgroundCheckTypes
                FROM node__field_ns_bg_check_types
                GROUP BY entity_id) as nsBackgroundCheckTypes ON nsBackgroundCheckTypes.entity_id = node.nid
            LEFT JOIN node__field_ns_training as nsTrainingValueTable ON nsTrainingValueTable.entity_id = node.nid
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(field_program_accepting_value) as programAccepting
                FROM node__field_program_accepting
                GROUP BY entity_id) as accepting ON accepting.entity_id = node.nid
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(field_types_of_mentoring_value) as typesOfMentoring
                FROM node__field_types_of_mentoring GROUP BY entity_id) as typesOfMentoring ON typesOfMentoring.entity_id = node.nid
            LEFT JOIN node__field_ns_bg_peer_type as nsPeerBackgroundCheckTable ON nsPeerBackgroundCheckTable.entity_id = node.nid
            LEFT JOIN node__field_types_of_mentoring_other as typesOfMentoringOtherTable ON typesOfMentoringOtherTable.entity_id = node.nid
            LEFT JOIN node__field_program_mentor_month_commi as mentorMonthCommitmentTable ON mentorMonthCommitmentTable.entity_id = node.nid
            LEFT JOIN node__field_program_mentor_freq_commit as mentorFrequencyCommitmentTable ON mentorFrequencyCommitmentTable.entity_id = node.nid
            LEFT JOIN node__field_program_mentor_freq_other as mentorFrequencyCommitmentOtherTable ON mentorFrequencyCommitmentOtherTable.entity_id = node.nid
            LEFT JOIN node__field_program_mentor_hour_commit as mentorHourlyCommitmentTable ON mentorHourlyCommitmentTable.entity_id = node.nid
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(field_administrators_target_id) as adminUid
                FROM node__field_administrators GROUP BY entity_id) as adminUid ON adminUid.entity_id = node.nid
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(location) as siteBasedLocations
                FROM programs_locations where type = 'siteBased' GROUP BY entity_id) as siteBasedLocations ON siteBasedLocations.entity_id = node.nid
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(location) as communityBasedLocations
                FROM programs_locations where type = 'communityBased' GROUP BY entity_id) as communityBasedLocations ON communityBasedLocations.entity_id = node.nid
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(location) as eMentoringLocations
                FROM programs_locations where type = 'eMentoring' GROUP BY entity_id) as eMentoringLocations ON eMentoringLocations.entity_id = node.nid
            LEFT JOIN
                (
                  SELECT m.entity_id, status = 'active' as status
                  FROM mentorcity_invitations m
                  JOIN (
                    SELECT entity_id, MAX(created_date) AS latest_date
                    FROM mentorcity_invitations
                    GROUP BY entity_id
                  ) t ON m.entity_id = t.entity_id AND m.created_date = t.latest_date
                ) as mentorCityInvitations ON mentorCityInvitations.entity_id = node.nid
            WHERE node.uuid = :filterUuid
            ";

        $result = \Drupal::database()->query($sql, [':filterUuid' => $this->filterUuid]);
        $program = $result->fetchAll()[0];
        $this->addContactsToProgram($program);
        $program->title = json_decode($program->title);
        unset($program->adminUid);
        $this->translateEntityFields($program);
        return $program;
    }

    private function addContactsToProgram($program)
    {
        $allProgramContacts = [];
        $programAdmins = $this->getAssociatedAccounts($program->adminUid);
        foreach ($programAdmins as $admin) {
            $additionalProgramAdmin = [
                'uuid' => $admin->uuid,
                'type' => 'programAdministrator',
                'firstName' => $admin->adminFirstName,
                'lastName' => $admin->adminLastName,
                'email' => $admin->adminEmail,
            ];
            if (!$additionalProgramAdmin['lastName']) {
                $additionalProgramAdmin['lastName'] = 'Unavailable';
            }
            $allProgramContacts[] = $additionalProgramAdmin;
        }
        $program->contacts = $allProgramContacts;
    }

    private function getProgramsForOrganization($organization)
    {
        $programs = $this->getAssociatedPrograms($organization->entityId);
        foreach ($programs as $program) {
            $this->addContactsToProgram($program);
            $program->title = json_decode($program->title);
            $this->translateEntityFields($program);
            $program->status = self::getStatus($program->status);
            unset($program->adminUid);
        }
        unset($organization->entityId);
        return $programs;
    }

    private function getAssociatedPrograms($organizationNid)
    {
        $sql = "SELECT
            node.uuid,
            'program' as type,
            programs.title,
            programs.first_name as  firstName,
            programs.last_name as  lastName,
            programs.position,
            programs.email,
            programs.phone,
            programs.altPhone,
            programs.communityBased,
            programs.siteBased,
            programs.eMentoring,
            node__field_facebook.field_facebook_value as facebook,
            node__field_twitter.field_twitter_value as twitter,
            node__field_website.field_website_value as website,
            node__field_instagram.field_instagram_value as instagram,
            focusAreaTable.field_focus_area_value as focusArea,
            focusAreaOtherTable.field_focus_area_other_value as focusAreaOther,
            primaryMeetingLocationTable.field_primary_meeting_location_value as primaryMeetingLocation,
            primaryMeetingLocationOtherTable.field_primary_meeting_loc_other_value as primaryMeetingLocationOther,
            node__field_program_youth_per_year.field_program_youth_per_year_value as youthPerYear,
            node__field_program_mentees_waiting_li.field_program_mentees_waiting_li_value as menteesWaitingList,
            adminUid,
            'True' AS 'MentorConnector',
            CASE WHEN mentorCityInvitations.status THEN 'True' ELSE 'False' END AS 'VirtualMentoringPlatform',
            (SELECT field_standing_value FROM node__field_standing WHERE node.nid = node__field_standing.entity_id AND node__field_standing.bundle = 'programs') AS status
            FROM node__field_organization_entity as orgEntity
            LEFT JOIN node ON node.nid = orgEntity.entity_id
            LEFT JOIN programs ON programs.entity_id = orgEntity.entity_id
            LEFT JOIN node__field_facebook ON node__field_facebook.entity_id = orgEntity.entity_id
            LEFT JOIN node__field_twitter ON node__field_twitter.entity_id = orgEntity.entity_id
            LEFT JOIN node__field_website ON node__field_website.entity_id = orgEntity.entity_id
            LEFT JOIN node__field_instagram ON node__field_instagram.entity_id = orgEntity.entity_id
            LEFT JOIN node__field_program_youth_per_year ON node__field_program_youth_per_year.entity_id = orgEntity.entity_id
            LEFT JOIN node__field_program_mentees_waiting_li ON node__field_program_mentees_waiting_li.entity_id = orgEntity.entity_id
            LEFT JOIN node__field_focus_area as focusAreaTable ON focusAreaTable.entity_id = orgEntity.entity_id
            LEFT JOIN node__field_focus_area_other as focusAreaOtherTable ON focusAreaOtherTable.entity_id = orgEntity.entity_id
            LEFT JOIN node__field_primary_meeting_location as primaryMeetingLocationTable ON primaryMeetingLocationTable.entity_id = orgEntity.entity_id
            LEFT JOIN node__field_primary_meeting_loc_other as primaryMeetingLocationOtherTable ON primaryMeetingLocationOtherTable.entity_id = orgEntity.entity_id
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(field_program_operated_through_value) as programOperatedThrough
                FROM node__field_program_operated_through
                GROUP BY entity_id) as programOperatedThrough ON programOperatedThrough.entity_id = orgEntity.entity_id
            LEFT JOIN node__field_program_operated_other as programOperatedThroughOtherTable ON programOperatedThroughOtherTable.entity_id = orgEntity.entity_id
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(field_program_how_are_meetings_s_value) as meetingsScheduled
                FROM node__field_program_how_are_meetings_s
                GROUP BY entity_id) as meetingsScheduled ON meetingsScheduled.entity_id = orgEntity.entity_id
            LEFT JOIN node__field_program_how_other as meetingsScheduledOtherTable ON meetingsScheduledOtherTable.entity_id = orgEntity.entity_id
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(field_program_genders_served_value) as programGendersServed
                FROM node__field_program_genders_served
                GROUP BY entity_id) as programGendersServed ON programGendersServed.entity_id = orgEntity.entity_id
            LEFT JOIN node__field_program_genders_other as programGendersServedOtherTable ON programGendersServedOtherTable.entity_id = orgEntity.entity_id
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(field_program_ages_served_value) as programAgesServed
                FROM node__field_program_ages_served
                GROUP BY entity_id) as programAgesServed ON programAgesServed.entity_id = orgEntity.entity_id
            LEFT JOIN node__field_program_ages_other as programAgesServedOtherTable ON programAgesServedOtherTable.entity_id = orgEntity.entity_id
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(field_program_family_served_value) as programfamilyServed
                FROM node__field_program_family_served
                GROUP BY entity_id) as programfamilyServed ON programfamilyServed.entity_id = orgEntity.entity_id
            LEFT JOIN node__field_program_family_other as programFamilyServedOtherTable ON programFamilyServedOtherTable.entity_id = orgEntity.entity_id
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(field_program_youth_served_value) as programYouthServed
                FROM node__field_program_youth_served
                GROUP BY entity_id) as programYouthServed ON programYouthServed.entity_id = orgEntity.entity_id
            LEFT JOIN node__field_program_youth_other as programYouthServedOtherTable ON programYouthServedOtherTable.entity_id = orgEntity.entity_id
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(field_program_gender_mentor_targ_value) as programMentorGenders
                FROM node__field_program_gender_mentor_targ
                GROUP BY entity_id) as programMentorGenders ON programMentorGenders.entity_id = orgEntity.entity_id
            LEFT JOIN node__field_program_gender_mentor_oth as programMentorGendersOtherTable ON programMentorGendersOtherTable.entity_id = orgEntity.entity_id
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(field_program_ages_mentor_target_value) as programMentorAges
                FROM node__field_program_ages_mentor_target
                GROUP BY entity_id) as programMentorAges ON programMentorAges.entity_id = orgEntity.entity_id
            LEFT JOIN node__field_program_age_mentor_other as programMentorAgesOtherTable ON programMentorAgesOtherTable.entity_id = orgEntity.entity_id
            LEFT JOIN node__field_ns_bg_check as nsBackgroundCheckTable ON nsBackgroundCheckTable.entity_id = orgEntity.entity_id
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(field_ns_bg_check_types_value) as nsBackgroundCheckTypes
                FROM node__field_ns_bg_check_types
                GROUP BY entity_id) as nsBackgroundCheckTypes ON nsBackgroundCheckTypes.entity_id = orgEntity.entity_id
            LEFT JOIN node__field_ns_training as nsTrainingValueTable ON nsTrainingValueTable.entity_id = orgEntity.entity_id
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(field_program_accepting_value) as programAccepting
                FROM node__field_program_accepting
                GROUP BY entity_id) as accepting ON accepting.entity_id = orgEntity.entity_id
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(field_types_of_mentoring_value) as typesOfMentoring
                FROM node__field_types_of_mentoring GROUP BY entity_id) as typesOfMentoring ON typesOfMentoring.entity_id = orgEntity.entity_id
            LEFT JOIN node__field_ns_bg_peer_type as nsPeerBackgroundCheckTable ON nsPeerBackgroundCheckTable.entity_id = orgEntity.entity_id
            LEFT JOIN node__field_types_of_mentoring_other as typesOfMentoringOtherTable ON typesOfMentoringOtherTable.entity_id = orgEntity.entity_id
            LEFT JOIN node__field_program_mentor_month_commi as mentorMonthCommitmentTable ON mentorMonthCommitmentTable.entity_id = orgEntity.entity_id
            LEFT JOIN node__field_program_mentor_freq_commit as mentorFrequencyCommitmentTable ON mentorFrequencyCommitmentTable.entity_id = orgEntity.entity_id
            LEFT JOIN node__field_program_mentor_freq_other as mentorFrequencyCommitmentOtherTable ON mentorFrequencyCommitmentOtherTable.entity_id = orgEntity.entity_id
            LEFT JOIN node__field_program_mentor_hour_commit as mentorHourlyCommitmentTable ON mentorHourlyCommitmentTable.entity_id = orgEntity.entity_id
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(field_administrators_target_id) as adminUid
                FROM node__field_administrators GROUP BY entity_id) as adminUid ON adminUid.entity_id = orgEntity.entity_id
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(location) as siteBasedLocations
                FROM programs_locations where type = 'siteBased' GROUP BY entity_id) as siteBasedLocations ON siteBasedLocations.entity_id = node.nid
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(location) as communityBasedLocations
                FROM programs_locations where type = 'communityBased' GROUP BY entity_id) as communityBasedLocations ON communityBasedLocations.entity_id = node.nid
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(location) as eMentoringLocations
                FROM programs_locations where type = 'eMentoring' GROUP BY entity_id) as eMentoringLocations ON eMentoringLocations.entity_id = node.nid
            LEFT JOIN
                (
                  SELECT m.entity_id, status = 'active' as status
                  FROM mentorcity_invitations m
                  JOIN (
                    SELECT entity_id, MAX(created_date) AS latest_date
                    FROM mentorcity_invitations
                    GROUP BY entity_id
                  ) t ON m.entity_id = t.entity_id AND m.created_date = t.latest_date
                ) as mentorCityInvitations ON mentorCityInvitations.entity_id = node.nid
            WHERE orgEntity.field_organization_entity_target_id = :organizationNid
            AND orgEntity.bundle = 'programs'
            AND node.uuid IS NOT NULL
            ";

        $result = \Drupal::database()->query($sql, [':organizationNid' => $organizationNid]);
        $programs = $result->fetchAll();
        return $programs;
    }

    private function getAssociatedAccounts($adminUids)
    {
        $adminUids = json_decode($adminUids);
        $q = \Drupal::database()->select('users', 'users');
        $q->addField('users', 'uuid');
        $q->condition('users.uid', $adminUids, 'IN');
        $q->leftJoin('user__field_first_name', 'firstNames', 'users.uid = firstNames.entity_id');
        $q->addField('firstNames', 'field_first_name_value', 'adminFirstName');
        $q->leftJoin('user__field_last_name', 'lastNames', 'users.uid = lastNames.entity_id');
        $q->addField('lastNames', 'field_last_name_value', 'adminLastName');
        $q->leftJoin('users_field_data', 'userData', 'users.uid = userData.uid');
        $q->addField('userData', 'mail', 'adminEmail');
        $admins = $q->execute()->fetchAll();
        return $admins;
    }

    private function getProgramsWithoutAnOrganization()
    {
        $sql = "SELECT
            node.uuid,
            'program' as type,
            programs.title,
            programs.first_name as  firstName,
            programs.last_name as  lastName,
            programs.position,
            programs.email,
            programs.phone,
            programs.altPhone,
            programs.communityBased,
            programs.siteBased,
            programs.eMentoring,
            node__field_facebook.field_facebook_value as facebook,
            node__field_twitter.field_twitter_value as twitter,
            node__field_website.field_website_value as website,
            node__field_instagram.field_instagram_value as instagram,
            focusAreaTable.field_focus_area_value as focusArea,
            focusAreaOtherTable.field_focus_area_other_value as focusAreaOther,
            primaryMeetingLocationTable.field_primary_meeting_location_value as primaryMeetingLocation,
            primaryMeetingLocationOtherTable.field_primary_meeting_loc_other_value as primaryMeetingLocationOther,
            node__field_program_youth_per_year.field_program_youth_per_year_value as youthPerYear,
            node__field_program_mentees_waiting_li.field_program_mentees_waiting_li_value as menteesWaitingList,
            adminUid,
            'True' AS 'MentorConnector',
            CASE WHEN mentorCityInvitations.status THEN 'True' ELSE 'False' END AS 'VirtualMentoringPlatform',
            (SELECT field_standing_value FROM node__field_standing WHERE node.nid = node__field_standing.entity_id AND node__field_standing.bundle = 'programs') AS status
            FROM node as node
            LEFT JOIN programs ON programs.entity_id = node.nid
            LEFT JOIN node__field_organization_entity as orgEntity ON programs.entity_id = orgEntity.entity_id
            LEFT JOIN node__field_facebook ON node__field_facebook.entity_id = node.nid
            LEFT JOIN node__field_twitter ON node__field_twitter.entity_id = node.nid
            LEFT JOIN node__field_website ON node__field_website.entity_id = node.nid
            LEFT JOIN node__field_instagram ON node__field_instagram.entity_id = node.nid
            LEFT JOIN node__field_program_youth_per_year ON node__field_program_youth_per_year.entity_id = node.nid
            LEFT JOIN node__field_program_mentees_waiting_li ON node__field_program_mentees_waiting_li.entity_id = node.nid
            LEFT JOIN node__field_focus_area as focusAreaTable ON focusAreaTable.entity_id = node.nid
            LEFT JOIN node__field_focus_area_other as focusAreaOtherTable ON focusAreaOtherTable.entity_id = node.nid
            LEFT JOIN node__field_primary_meeting_location as primaryMeetingLocationTable ON primaryMeetingLocationTable.entity_id = node.nid
            LEFT JOIN node__field_primary_meeting_loc_other as primaryMeetingLocationOtherTable ON primaryMeetingLocationOtherTable.entity_id = node.nid
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(field_program_operated_through_value) as programOperatedThrough
                FROM node__field_program_operated_through
                GROUP BY entity_id) as programOperatedThrough ON programOperatedThrough.entity_id = node.nid
            LEFT JOIN node__field_program_operated_other as programOperatedThroughOtherTable ON programOperatedThroughOtherTable.entity_id = node.nid
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(field_program_how_are_meetings_s_value) as meetingsScheduled
                FROM node__field_program_how_are_meetings_s
                GROUP BY entity_id) as meetingsScheduled ON meetingsScheduled.entity_id = node.nid
            LEFT JOIN node__field_program_how_other as meetingsScheduledOtherTable ON meetingsScheduledOtherTable.entity_id = node.nid
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(field_program_genders_served_value) as programGendersServed
                FROM node__field_program_genders_served
                GROUP BY entity_id) as programGendersServed ON programGendersServed.entity_id = node.nid
            LEFT JOIN node__field_program_genders_other as programGendersServedOtherTable ON programGendersServedOtherTable.entity_id = node.nid
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(field_program_ages_served_value) as programAgesServed
                FROM node__field_program_ages_served
                GROUP BY entity_id) as programAgesServed ON programAgesServed.entity_id = node.nid
            LEFT JOIN node__field_program_ages_other as programAgesServedOtherTable ON programAgesServedOtherTable.entity_id = node.nid
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(field_program_family_served_value) as programfamilyServed
                FROM node__field_program_family_served
                GROUP BY entity_id) as programfamilyServed ON programfamilyServed.entity_id = node.nid
            LEFT JOIN node__field_program_family_other as programFamilyServedOtherTable ON programFamilyServedOtherTable.entity_id = node.nid
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(field_program_youth_served_value) as programYouthServed
                FROM node__field_program_youth_served
                GROUP BY entity_id) as programYouthServed ON programYouthServed.entity_id = node.nid
            LEFT JOIN node__field_program_youth_other as programYouthServedOtherTable ON programYouthServedOtherTable.entity_id = node.nid
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(field_program_gender_mentor_targ_value) as programMentorGenders
                FROM node__field_program_gender_mentor_targ
                GROUP BY entity_id) as programMentorGenders ON programMentorGenders.entity_id = node.nid
            LEFT JOIN node__field_program_gender_mentor_oth as programMentorGendersOtherTable ON programMentorGendersOtherTable.entity_id = node.nid
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(field_program_ages_mentor_target_value) as programMentorAges
                FROM node__field_program_ages_mentor_target
                GROUP BY entity_id) as programMentorAges ON programMentorAges.entity_id = node.nid
            LEFT JOIN node__field_program_age_mentor_other as programMentorAgesOtherTable ON programMentorAgesOtherTable.entity_id = node.nid
            LEFT JOIN node__field_ns_bg_check as nsBackgroundCheckTable ON nsBackgroundCheckTable.entity_id = node.nid
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(field_ns_bg_check_types_value) as nsBackgroundCheckTypes
                FROM node__field_ns_bg_check_types
                GROUP BY entity_id) as nsBackgroundCheckTypes ON nsBackgroundCheckTypes.entity_id = node.nid
            LEFT JOIN node__field_ns_training as nsTrainingValueTable ON nsTrainingValueTable.entity_id = node.nid
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(field_program_accepting_value) as programAccepting
                FROM node__field_program_accepting
                GROUP BY entity_id) as accepting ON accepting.entity_id = node.nid
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(field_types_of_mentoring_value) as typesOfMentoring
                FROM node__field_types_of_mentoring GROUP BY entity_id) as typesOfMentoring ON typesOfMentoring.entity_id = node.nid
            LEFT JOIN node__field_ns_bg_peer_type as nsPeerBackgroundCheckTable ON nsPeerBackgroundCheckTable.entity_id = node.nid
            LEFT JOIN node__field_types_of_mentoring_other as typesOfMentoringOtherTable ON typesOfMentoringOtherTable.entity_id = node.nid
            LEFT JOIN node__field_program_mentor_month_commi as mentorMonthCommitmentTable ON mentorMonthCommitmentTable.entity_id = node.nid
            LEFT JOIN node__field_program_mentor_freq_commit as mentorFrequencyCommitmentTable ON mentorFrequencyCommitmentTable.entity_id = node.nid
            LEFT JOIN node__field_program_mentor_freq_other as mentorFrequencyCommitmentOtherTable ON mentorFrequencyCommitmentOtherTable.entity_id = node.nid
            LEFT JOIN node__field_program_mentor_hour_commit as mentorHourlyCommitmentTable ON mentorHourlyCommitmentTable.entity_id = node.nid
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(field_administrators_target_id) as adminUid
                FROM node__field_administrators GROUP BY entity_id) as adminUid ON adminUid.entity_id = node.nid
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(location) as siteBasedLocations
                FROM programs_locations where type = 'siteBased' GROUP BY entity_id) as siteBasedLocations ON siteBasedLocations.entity_id = node.nid
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(location) as communityBasedLocations
                FROM programs_locations where type = 'communityBased' GROUP BY entity_id) as communityBasedLocations ON communityBasedLocations.entity_id = node.nid
            LEFT JOIN
                (SELECT entity_id, JSON_ARRAYAGG(location) as eMentoringLocations
                FROM programs_locations where type = 'eMentoring' GROUP BY entity_id) as eMentoringLocations ON eMentoringLocations.entity_id = node.nid
            LEFT JOIN
                (
                  SELECT m.entity_id, status = 'active' as status
                  FROM mentorcity_invitations m
                  JOIN (
                    SELECT entity_id, MAX(created_date) AS latest_date
                    FROM mentorcity_invitations
                    GROUP BY entity_id
                  ) t ON m.entity_id = t.entity_id AND m.created_date = t.latest_date
                ) as mentorCityInvitations ON mentorCityInvitations.entity_id = node.nid
            WHERE node.type = 'programs'
            AND orgEntity.entity_id IS NULL
            ";

        $programs = \Drupal::database()->query($sql)->fetchAll();
        foreach ($programs as $program) {
            $this->addContactsToProgram($program);
            $program->title = json_decode($program->title);
            $this->translateEntityFields($program);
            $program->status = self::getStatus($program->status);
            unset($program->adminUid);
        }
        return $programs;
    }

    private function translateEntityFields($entity)
    {
        $translatableFields['program'] = [
            'programAccepting',
            'focusArea',
            'primaryMeetingLocation',
            'typesOfMentoring',
            'programOperatedThrough',
            'meetingsScheduled',
            'programGendersServed',
            'programAgesServed',
            'programFamilyServed',
            'programYouthServed',
            'programMentorGenders',
            'programMentorAges',
            'nsBackgroundCheck',
            'nsBackgroundCheckTypes',
            'nsPeerBackgroundCheck',
            'nsTrainingValue',
            'mentorMonthCommitment',
            'mentorFrequencyCommitment',
            'mentorHourlyCommitment',
        ];
        $translatableFields['organization'] = [
            'typeOfOrganization',
            'taxStatus',
            'position',
        ];
        foreach ($entity as $entityKey => &$entityValue) {
            if (gettype($entityValue) === 'string' && in_array($entityKey, $translatableFields[$entity->type], true)) {
                $entity->{$entityKey} = t($entityValue)->render();
            }
            if (gettype($entityValue) === 'array' && in_array($entityKey, $translatableFields[$entity->type], true)) {
                foreach ($entityValue as $arrayIndex => $arrayValue) {
                    $entityValue[$arrayIndex] = t($arrayValue)->render();
                }
            }
        }
    }

}
