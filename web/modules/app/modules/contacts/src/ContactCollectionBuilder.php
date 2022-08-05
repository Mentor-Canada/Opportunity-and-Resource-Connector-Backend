<?php

namespace Drupal\app_contacts;

class ContactCollectionBuilder
{
    private ?string $filterType;
    private ?string $filterUuid;

    public function __construct($uuid = null, $type = null)
    {
        $this->filterType = $type;
        $this->filterUuid = $uuid;
    }

    public function getCollection()
    {
        if ($this->filterType === 'contact') {
            return $this->getContact();
        }
        if ($this->filterType === 'programs') {
            return $this->getProgram();
        }
        if ($this->filterType === 'organization') {
            return $this->getOrganizations();
        }
        $entityList[] = $this->getOrganizations();
        $entityList[] = [
            'type' => 'programs-without-an-organization',
            'programs' => $this->getProgramsWithoutAnOrganization()
        ];
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

    private function getOrganizations()
    {
        $db = \Drupal::database();
        $q = $db->select('node', 'node');
        $q->addField('node', 'uuid');
        $q->addField('node', 'type');
        $q->addField('node', 'nid', 'entityId');
        $q->condition('node.type', 'organization');
        $q->leftJoin('organizations', 'flatOrg', 'node.nid = flatOrg.entity_id');
        $q->addField('flatOrg', 'title');
        $q->addField('flatOrg', 'legal_name', 'legalName');
        $q->addField('flatOrg', 'description');
        $q->addField('flatOrg', 'type', 'typeOfOrganization');
        $q->addField('flatOrg', 'other_type', 'typeOfOrganizationOther');
        $q->addField('flatOrg', 'tax_status', 'taxStatus');
        $q->addField('flatOrg', 'other_tax_status', 'otherTaxStatus');
        $q->addField('flatOrg', 'first_name', 'firstName');
        $q->addField('flatOrg', 'last_name', 'lastName');
        $q->addField('flatOrg', 'position');
        $q->addField('flatOrg', 'other_position', 'otherPosition');
        $q->addField('flatOrg', 'phone');
        $q->addField('flatOrg', 'alt_phone', 'altPhone');
        $q->addField('flatOrg', 'email', 'email');
        $q->addField('flatOrg', 'website', 'website');
        $q->addField('flatOrg', 'has_location', 'hasLocation');
        $q->addField('flatOrg', 'location');
        $q->addField('flatOrg', 'feedback');
        $q->addField('flatOrg', 'mentor_city_enabled', 'mentorCityEnabled');
        $q->addField('flatOrg', 'bbbsc_enabled', 'bbbscEnabled');
        $q->addField('flatOrg', 'mtg_enabled', 'mtgEnabled');
        $q->leftJoin('node__field_administrators', 'admins', 'node.nid = admins.entity_id');
        $q->addExpression("JSON_ARRAYAGG(admins.field_administrators_target_id)", 'adminTargetIds');
        $q->groupBy('node.nid');
        if ($this->filterType === 'organization') {
            $q->condition('node.uuid', $this->filterUuid);
        }
        $organizations = $q->execute()->fetchAll();

        foreach ($organizations as $organization) {
            $organization->title = json_decode($organization->title);
            $organization->description = json_decode($organization->description);
            $organization->location = json_decode($organization->location);
            $programs = $this->getProgramsForOrganization($organization->entityId);
            $organizationContacts = $this->getOrganizationContacts($organization);
            unset($organization->adminTargetIds);
            $organization->contacts = $organizationContacts;
            $organization->programs = $programs;
        }
        return $organizations;
    }

    private function getOrganizationContacts($organization)
    {
        $organizationAdmins = $this->getAssociatedAccounts($organization->adminTargetIds);
        $allOrganizationContacts = [];
        $organizationPublicContact = [
            'uuid' => '',
            'type' => 'contact',
            'contactType' => 'public',
            'firstName' => $organization->firstName,
            'lastName' => $organization->lastName,
            'position' => $organization->position,
            'otherPosition' => $organization->otherPosition,
            'phone' => $organization->phone,
            'altPhone' => $organization->altPhone,
            'email' => $organization->email,
            'website' => $organization->website
        ];
        foreach ($organizationAdmins as $admin) {
            if ($admin->adminEmail === $organization->email) {
                $organizationPublicContact['uuid'] = $admin->uuid;
                continue;
            }
            $additionalOrganizationAdmin = [
                'uuid' => $admin->uuid,
                'type' => 'contact',
                'contactType' => 'organizationAdministrator',
                'firstName' => $admin->adminFirstName,
                'lastName' => $admin->adminLastName,
                'email' => $admin->adminEmail,
            ];
            $allOrganizationContacts[] = $additionalOrganizationAdmin;
        }
        $allOrganizationContacts[] = $organizationPublicContact;
        return $allOrganizationContacts;
    }

    private function getProgram()
    {
        $sql = "SELECT
            node.uuid,
            'program' as type,
            node.nid as entityId,
            programs.title,
            programs.programDescription,
            programs.mentorDescription,
            programAccepting,
            programs.first_name as  firstName,
            programs.last_name as  lastName,
            programs.position,
            programs.email,
            programs.phone,
            programs.altPhone,
            programs.communityBased,
            programs.siteBased,
            programs.eMentoring,
            programs.nationwideEMentoring,
            programs.trainingDescription,
            programs.responsivenessTier,
            programs.NQMS,
            programs.ADA,
            programs.bbbsc,
            programs.bbbscInquiryProgramOfInterest,
            programs.bbbscProgramType,
            programs.bbbscSystemUser,
            programs.source,
            programs.id211,
            programs.external_id  as externalId,
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
            typesOfMentoring,
            typesOfMentoringOtherTable.field_types_of_mentoring_other_value as typesOfMentoringOther,
            programOperatedThrough,
            programOperatedThroughOtherTable.field_program_operated_other_value as programOperatedThroughOther,
            meetingsScheduled,
            meetingsScheduledOtherTable.field_program_how_other_value as meetingsScheduledOther,
            programGendersServed,
            programGendersServedOtherTable.field_program_genders_other_value as programGendersServedOther,
            programAgesServed,
            programAgesServedOtherTable.field_program_ages_other_value as programAgesServedOther,
            programFamilyServed,
            programFamilyServedOtherTable.field_program_family_other_value as programFamilyServedOther,
            programYouthServed,
            programYouthServedOtherTable.field_program_youth_other_value as programYouthServedOther,
            programMentorGenders,
            programMentorGendersOtherTable.field_program_gender_mentor_oth_value as programMentorGendersOther,
            programMentorAges,
            programMentorAgesOtherTable.field_program_age_mentor_other_value as programMentorAgesOther,
            nsBackgroundCheckTable.field_ns_bg_check_value as nsBackgroundCheck,
            nsBackgroundCheckTypes,
            nsPeerBackgroundCheckTable.field_ns_bg_peer_type_value as nsPeerBackgroundCheck,
            nsTrainingValueTable.field_ns_training_value as nsTrainingValue,
            mentorMonthCommitmentTable.field_program_mentor_month_commi_value as mentorMonthCommitment,
            mentorFrequencyCommitmentTable.field_program_mentor_freq_commit_value as mentorFrequencyCommitment,
            mentorFrequencyCommitmentOtherTable.field_program_mentor_freq_other_value as mentorFrequencyCommitmentOther,
            mentorHourlyCommitmentTable.field_program_mentor_hour_commit_value as mentorHourlyCommitment,
            adminUid,
            communityBasedLocations,
            siteBasedLocations,
            eMentoringLocations
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
            WHERE node.uuid = :filterUuid
            ";

        $result = \Drupal::database()->query($sql, [':filterUuid' => $this->filterUuid]);
        $program = $result->fetchAll()[0];
        $this->addContactsToProgram($program);
        $this->jsonDecodeProgramFields($program);
        unset($program->adminUid);
        return $program;
    }

    private function addContactsToProgram($program)
    {
        $allProgramContacts = [];
        $programAdmins = $this->getAssociatedAccounts($program->adminUid);
        $programPublicContact = [
            'uuid' => '',
            'type' => 'contact',
            'contactType' => 'public',
            'firstName' => $program->firstName,
            'lastName' => $program->lastName,
            'position' => $program->position,
            'email' => $program->email,
            'phone' => $program->phone,
            'altPhone' => $program->altPhone
        ];
        foreach ($programAdmins as $admin) {
            if ($admin->adminEmail === $program->email) {
                $programPublicContact['uuid'] = $admin->uuid;
                continue;
            }
            $additionalProgramAdmin = [
                'uuid' => $admin->uuid,
                'type' => 'contact',
                'contactType' => 'programAdministrator',
                'firstName' => $admin->adminFirstName,
                'lastName' => $admin->adminLastName,
                'email' => $admin->adminEmail,
            ];
            $allProgramContacts[] = $additionalProgramAdmin;
        }
        $allProgramContacts[] = $programPublicContact;
        $program->contacts = $allProgramContacts;
    }

    private function getProgramsForOrganization($organizationNid)
    {
        $programs = $this->getAssociatedPrograms($organizationNid);
        foreach ($programs as $program) {
            $this->addContactsToProgram($program);
            $this->jsonDecodeProgramFields($program);
            unset($program->adminUid);
        }
        return $programs;
    }

    private function jsonDecodeProgramFields($program)
    {
        $program->title = json_decode($program->title);
        $program->programDescription = json_decode($program->programDescription);
        $program->mentorDescription = json_decode($program->mentorDescription);
        $program->programAccepting = json_decode($program->programAccepting);
        $program->trainingDescription = json_decode($program->trainingDescription);
        $program->typesOfMentoring = json_decode($program->typesOfMentoring);
        $program->programOperatedThrough = json_decode($program->programOperatedThrough);
        $program->meetingsScheduled = json_decode($program->meetingsScheduled);
        $program->programGendersServed = json_decode($program->programGendersServed);
        $program->programAgesServed = json_decode($program->programAgesServed);
        $program->programFamilyServed = json_decode($program->programFamilyServed);
        $program->programYouthServed = json_decode($program->programYouthServed);
        $program->programMentorGenders = json_decode($program->programMentorGenders);
        $program->programMentorAges = json_decode($program->programMentorAges);
        $program->nsBackgroundCheckTypes = json_decode($program->nsBackgroundCheckTypes);
        $program->communityBasedLocations = json_decode($program->communityBasedLocations);
        $program->siteBasedLocations = json_decode($program->siteBasedLocations);
        $program->eMentoringLocations = json_decode($program->eMentoringLocations);
    }

    private function getAssociatedPrograms($organizationNid)
    {
        $sql = "SELECT
            node.uuid,
            'program' as type,
            orgEntity.entity_id as entityId,
            programs.title,
            programs.programDescription,
            programs.mentorDescription,
            programAccepting,
            programs.first_name as  firstName,
            programs.last_name as  lastName,
            programs.position,
            programs.email,
            programs.phone,
            programs.altPhone,
            programs.communityBased,
            programs.siteBased,
            programs.eMentoring,
            programs.nationwideEMentoring,
            programs.trainingDescription,
            programs.responsivenessTier,
            programs.NQMS,
            programs.ADA,
            programs.bbbsc,
            programs.bbbscInquiryProgramOfInterest,
            programs.bbbscProgramType,
            programs.bbbscSystemUser,
            programs.source,
            programs.id211,
            programs.external_id as externalId,
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
            typesOfMentoring,
            typesOfMentoringOtherTable.field_types_of_mentoring_other_value as typesOfMentoringOther,
            programOperatedThrough,
            programOperatedThroughOtherTable.field_program_operated_other_value as programOperatedThroughOther,
            meetingsScheduled,
            meetingsScheduledOtherTable.field_program_how_other_value as meetingsScheduledOther,
            programGendersServed,
            programGendersServedOtherTable.field_program_genders_other_value as programGendersServedOther,
            programAgesServed,
            programAgesServedOtherTable.field_program_ages_other_value as programAgesServedOther,
            programFamilyServed,
            programFamilyServedOtherTable.field_program_family_other_value as programFamilyServedOther,
            programYouthServed,
            programYouthServedOtherTable.field_program_youth_other_value as programYouthServedOther,
            programMentorGenders,
            programMentorGendersOtherTable.field_program_gender_mentor_oth_value as programMentorGendersOther,
            programMentorAges,
            programMentorAgesOtherTable.field_program_age_mentor_other_value as programMentorAgesOther,
            nsBackgroundCheckTable.field_ns_bg_check_value as nsBackgroundCheck,
            nsBackgroundCheckTypes,
            nsPeerBackgroundCheckTable.field_ns_bg_peer_type_value as nsPeerBackgroundCheck,
            nsTrainingValueTable.field_ns_training_value as nsTrainingValue,
            mentorMonthCommitmentTable.field_program_mentor_month_commi_value as mentorMonthCommitment,
            mentorFrequencyCommitmentTable.field_program_mentor_freq_commit_value as mentorFrequencyCommitment,
            mentorFrequencyCommitmentOtherTable.field_program_mentor_freq_other_value as mentorFrequencyCommitmentOther,
            mentorHourlyCommitmentTable.field_program_mentor_hour_commit_value as mentorHourlyCommitment,
            adminUid,
            communityBasedLocations,
            siteBasedLocations,
            eMentoringLocations
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
            node.nid as entityId,
            programs.title,
            programs.programDescription,
            programs.mentorDescription,
            programAccepting,
            programs.first_name as  firstName,
            programs.last_name as  lastName,
            programs.position,
            programs.email,
            programs.phone,
            programs.altPhone,
            programs.communityBased,
            programs.siteBased,
            programs.eMentoring,
            programs.nationwideEMentoring,
            programs.trainingDescription,
            programs.responsivenessTier,
            programs.NQMS,
            programs.ADA,
            programs.bbbsc,
            programs.bbbscInquiryProgramOfInterest,
            programs.bbbscProgramType,
            programs.bbbscSystemUser,
            programs.source,
            programs.id211,
            programs.external_id  as externalId,
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
            typesOfMentoring,
            typesOfMentoringOtherTable.field_types_of_mentoring_other_value as typesOfMentoringOther,
            programOperatedThrough,
            programOperatedThroughOtherTable.field_program_operated_other_value as programOperatedThroughOther,
            meetingsScheduled,
            meetingsScheduledOtherTable.field_program_how_other_value as meetingsScheduledOther,
            programGendersServed,
            programGendersServedOtherTable.field_program_genders_other_value as programGendersServedOther,
            programAgesServed,
            programAgesServedOtherTable.field_program_ages_other_value as programAgesServedOther,
            programFamilyServed,
            programFamilyServedOtherTable.field_program_family_other_value as programFamilyServedOther,
            programYouthServed,
            programYouthServedOtherTable.field_program_youth_other_value as programYouthServedOther,
            programMentorGenders,
            programMentorGendersOtherTable.field_program_gender_mentor_oth_value as programMentorGendersOther,
            programMentorAges,
            programMentorAgesOtherTable.field_program_age_mentor_other_value as programMentorAgesOther,
            nsBackgroundCheckTable.field_ns_bg_check_value as nsBackgroundCheck,
            nsBackgroundCheckTypes,
            nsPeerBackgroundCheckTable.field_ns_bg_peer_type_value as nsPeerBackgroundCheck,
            nsTrainingValueTable.field_ns_training_value as nsTrainingValue,
            mentorMonthCommitmentTable.field_program_mentor_month_commi_value as mentorMonthCommitment,
            mentorFrequencyCommitmentTable.field_program_mentor_freq_commit_value as mentorFrequencyCommitment,
            mentorFrequencyCommitmentOtherTable.field_program_mentor_freq_other_value as mentorFrequencyCommitmentOther,
            mentorHourlyCommitmentTable.field_program_mentor_hour_commit_value as mentorHourlyCommitment,
            adminUid,
            communityBasedLocations,
            siteBasedLocations,
            eMentoringLocations
            FROM node as node  
            LEFT JOIN programs ON programs.entity_id = node.nid
            LEFT JOIN  node__field_organization_entity as orgEntity ON programs.entity_id = orgEntity.entity_id    
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
            WHERE node.type = 'programs' 
            AND orgEntity.entity_id IS NULL
            ";

        $programs = \Drupal::database()->query($sql)->fetchAll();
        foreach ($programs as $program) {
            $this->addContactsToProgram($program);
            $this->jsonDecodeProgramFields($program);
            unset($program->adminUid);
        }
        return $programs;
    }

}
