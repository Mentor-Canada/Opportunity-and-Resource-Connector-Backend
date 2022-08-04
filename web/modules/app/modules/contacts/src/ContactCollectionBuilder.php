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
        return $this->getOrganizations();
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
            focusArea,
            focusAreaOther,
            primaryMeetingLocation,
            primaryMeetingLocationOther,
            node__field_program_youth_per_year.field_program_youth_per_year_value as youthPerYear,
            node__field_program_mentees_waiting_li.field_program_mentees_waiting_li_value as menteesWaitingList,            
            typesOfMentoring,
            typesOfMentoringOther,
            programOperatedThrough,
            programOperatedThroughOther,
            meetingsScheduled,
            meetingsScheduledOther,
            programGendersServed,
            programGendersServedOther,
            programAgesServed,
            programAgesServedOther,
            programFamilyServed,
            programFamilyServedOther,
            programYouthServed,
            programYouthServedOther,
            programMentorGenders,
            programMentorGendersOther,
            programMentorAges,
            programMentorAgesOther,
            nsBackgroundCheck,
            nsBackgroundCheckTypes,
            nsPeerBackgroundCheck,
            nsTrainingValue,
            mentorMonthCommitment,
            mentorFrequencyCommitment,
            mentorFrequencyCommitmentOther,
            mentorHourlyCommitment,
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
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_focus_area_value) as focusArea 
                FROM node__field_focus_area 
                GROUP BY entity_id) as focusArea ON focusArea.entity_id = node.nid
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_focus_area_other_value) as focusAreaOther 
                FROM node__field_focus_area_other 
                GROUP BY entity_id) as focusAreaOther ON focusAreaOther.entity_id = node.nid            
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_primary_meeting_location_value) as primaryMeetingLocation 
                FROM node__field_primary_meeting_location 
                GROUP BY entity_id) as primaryMeetingLocation ON primaryMeetingLocation.entity_id = node.nid                
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_primary_meeting_loc_other_value) as primaryMeetingLocationOther 
                FROM node__field_primary_meeting_loc_other 
                GROUP BY entity_id) as primaryMeetingLocationOther ON primaryMeetingLocationOther.entity_id = node.nid
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_operated_through_value) as programOperatedThrough
                FROM node__field_program_operated_through 
                GROUP BY entity_id) as programOperatedThrough ON programOperatedThrough.entity_id = node.nid
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_operated_other_value) as programOperatedThroughOther 
                FROM node__field_program_operated_other
                GROUP BY entity_id) as programOperatedThroughOther ON programOperatedThroughOther.entity_id = node.nid       
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_how_are_meetings_s_value) as meetingsScheduled 
                FROM node__field_program_how_are_meetings_s
                GROUP BY entity_id) as meetingsScheduled ON meetingsScheduled.entity_id = node.nid          
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_how_other_value) as meetingsScheduledOther 
                FROM node__field_program_how_other
                GROUP BY entity_id) as meetingsScheduledOther ON meetingsScheduledOther.entity_id = node.nid
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_genders_served_value) as programGendersServed 
                FROM node__field_program_genders_served
                GROUP BY entity_id) as programGendersServed ON programGendersServed.entity_id = node.nid          
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_genders_other_value) as programGendersServedOther 
                FROM node__field_program_genders_other
                GROUP BY entity_id) as programGendersServedOther ON programGendersServedOther.entity_id = node.nid
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_ages_served_value) as programAgesServed 
                FROM node__field_program_ages_served
                GROUP BY entity_id) as programAgesServed ON programAgesServed.entity_id = node.nid          
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_ages_other_value) as programAgesServedOther 
                FROM node__field_program_ages_other
                GROUP BY entity_id) as programAgesServedOther ON programAgesServedOther.entity_id = node.nid
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_family_served_value) as programfamilyServed 
                FROM node__field_program_family_served
                GROUP BY entity_id) as programfamilyServed ON programfamilyServed.entity_id = node.nid          
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_family_other_value) as programfamilyServedOther 
                FROM node__field_program_family_other
                GROUP BY entity_id) as programfamilyServedOther ON programfamilyServedOther.entity_id = node.nid
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_youth_served_value) as programYouthServed 
                FROM node__field_program_youth_served
                GROUP BY entity_id) as programYouthServed ON programYouthServed.entity_id = node.nid          
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_youth_other_value) as programYouthServedOther 
                FROM node__field_program_youth_other
                GROUP BY entity_id) as programYouthServedOther ON programYouthServedOther.entity_id = node.nid
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_gender_mentor_targ_value) as programMentorGenders 
                FROM node__field_program_gender_mentor_targ
                GROUP BY entity_id) as programMentorGenders ON programMentorGenders.entity_id = node.nid          
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_gender_mentor_oth_value) as programMentorGendersOther 
                FROM node__field_program_gender_mentor_oth
                GROUP BY entity_id) as programMentorGendersOther ON programMentorGendersOther.entity_id = node.nid
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_ages_mentor_target_value) as programMentorAges 
                FROM node__field_program_ages_mentor_target
                GROUP BY entity_id) as programMentorAges ON programMentorAges.entity_id = node.nid          
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_age_mentor_other_value) as programMentorAgesOther 
                FROM node__field_program_age_mentor_other
                GROUP BY entity_id) as programMentorAgesOther ON programMentorAgesOther.entity_id = node.nid
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_ns_bg_check_value) as nsBackgroundCheck 
                FROM node__field_ns_bg_check
                GROUP BY entity_id) as nsBackgroundCheck ON nsBackgroundCheck.entity_id = node.nid
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_ns_bg_check_types_value) as nsBackgroundCheckTypes 
                FROM node__field_ns_bg_check_types
                GROUP BY entity_id) as nsBackgroundCheckTypes ON nsBackgroundCheckTypes.entity_id = node.nid          
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_ns_training_value) as nsTrainingValue 
                FROM node__field_ns_training
                GROUP BY entity_id) as nsTrainingValue ON nsTrainingValue.entity_id = node.nid     
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_accepting_value) as programAccepting 
                FROM node__field_program_accepting 
                GROUP BY entity_id) as accepting ON accepting.entity_id = node.nid
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_types_of_mentoring_value) as typesOfMentoring 
                FROM node__field_types_of_mentoring GROUP BY entity_id) as typesOfMentoring ON typesOfMentoring.entity_id = node.nid             
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_ns_bg_peer_type_value) as nsPeerBackgroundCheck 
                FROM node__field_ns_bg_peer_type GROUP BY entity_id) as nsPeerBackgroundCheck ON nsPeerBackgroundCheck.entity_id = node.nid 
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_types_of_mentoring_other_value) as typesOfMentoringOther 
                FROM node__field_types_of_mentoring_other GROUP BY entity_id) as typesOfMentoringOther ON typesOfMentoringOther.entity_id = node.nid               
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_mentor_month_commi_value) as mentorMonthCommitment 
                FROM node__field_program_mentor_month_commi GROUP BY entity_id) as mentorMonthCommitment ON mentorMonthCommitment.entity_id = node.nid  
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_mentor_freq_commit_value) as mentorFrequencyCommitment 
                FROM node__field_program_mentor_freq_commit GROUP BY entity_id) as mentorFrequencyCommitment ON mentorFrequencyCommitment.entity_id = node.nid
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_mentor_freq_other_value) as mentorFrequencyCommitmentOther 
                FROM node__field_program_mentor_freq_other GROUP BY entity_id) as mentorFrequencyCommitmentOther ON mentorFrequencyCommitmentOther.entity_id = node.nid
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_mentor_hour_commit_value) as mentorHourlyCommitment 
                FROM node__field_program_mentor_hour_commit GROUP BY entity_id) as mentorHourlyCommitment ON mentorHourlyCommitment.entity_id = node.nid
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
            unset($program->adminUid);
        }
        return $programs;
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
            focusArea,
            focusAreaOther,
            primaryMeetingLocation,
            primaryMeetingLocationOther,
            node__field_program_youth_per_year.field_program_youth_per_year_value as youthPerYear,
            node__field_program_mentees_waiting_li.field_program_mentees_waiting_li_value as menteesWaitingList,            
            typesOfMentoring,
            typesOfMentoringOther,
            programOperatedThrough,
            programOperatedThroughOther,
            meetingsScheduled,
            meetingsScheduledOther,
            programGendersServed,
            programGendersServedOther,
            programAgesServed,
            programAgesServedOther,
            programFamilyServed,
            programFamilyServedOther,
            programYouthServed,
            programYouthServedOther,
            programMentorGenders,
            programMentorGendersOther,
            programMentorAges,
            programMentorAgesOther,
            nsBackgroundCheck,
            nsBackgroundCheckTypes,
            nsPeerBackgroundCheck,
            nsTrainingValue,
            mentorMonthCommitment,
            mentorFrequencyCommitment,
            mentorFrequencyCommitmentOther,
            mentorHourlyCommitment,
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
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_focus_area_value) as focusArea 
                FROM node__field_focus_area 
                GROUP BY entity_id) as focusArea ON focusArea.entity_id = orgEntity.entity_id
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_focus_area_other_value) as focusAreaOther 
                FROM node__field_focus_area_other 
                GROUP BY entity_id) as focusAreaOther ON focusAreaOther.entity_id = orgEntity.entity_id            
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_primary_meeting_location_value) as primaryMeetingLocation 
                FROM node__field_primary_meeting_location 
                GROUP BY entity_id) as primaryMeetingLocation ON primaryMeetingLocation.entity_id = orgEntity.entity_id                
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_primary_meeting_loc_other_value) as primaryMeetingLocationOther 
                FROM node__field_primary_meeting_loc_other 
                GROUP BY entity_id) as primaryMeetingLocationOther ON primaryMeetingLocationOther.entity_id = orgEntity.entity_id
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_operated_through_value) as programOperatedThrough
                FROM node__field_program_operated_through 
                GROUP BY entity_id) as programOperatedThrough ON programOperatedThrough.entity_id = orgEntity.entity_id
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_operated_other_value) as programOperatedThroughOther 
                FROM node__field_program_operated_other
                GROUP BY entity_id) as programOperatedThroughOther ON programOperatedThroughOther.entity_id = orgEntity.entity_id       
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_how_are_meetings_s_value) as meetingsScheduled 
                FROM node__field_program_how_are_meetings_s
                GROUP BY entity_id) as meetingsScheduled ON meetingsScheduled.entity_id = orgEntity.entity_id          
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_how_other_value) as meetingsScheduledOther 
                FROM node__field_program_how_other
                GROUP BY entity_id) as meetingsScheduledOther ON meetingsScheduledOther.entity_id = orgEntity.entity_id
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_genders_served_value) as programGendersServed 
                FROM node__field_program_genders_served
                GROUP BY entity_id) as programGendersServed ON programGendersServed.entity_id = orgEntity.entity_id          
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_genders_other_value) as programGendersServedOther 
                FROM node__field_program_genders_other
                GROUP BY entity_id) as programGendersServedOther ON programGendersServedOther.entity_id = orgEntity.entity_id
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_ages_served_value) as programAgesServed 
                FROM node__field_program_ages_served
                GROUP BY entity_id) as programAgesServed ON programAgesServed.entity_id = orgEntity.entity_id          
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_ages_other_value) as programAgesServedOther 
                FROM node__field_program_ages_other
                GROUP BY entity_id) as programAgesServedOther ON programAgesServedOther.entity_id = orgEntity.entity_id
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_family_served_value) as programfamilyServed 
                FROM node__field_program_family_served
                GROUP BY entity_id) as programfamilyServed ON programfamilyServed.entity_id = orgEntity.entity_id          
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_family_other_value) as programfamilyServedOther 
                FROM node__field_program_family_other
                GROUP BY entity_id) as programfamilyServedOther ON programfamilyServedOther.entity_id = orgEntity.entity_id
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_youth_served_value) as programYouthServed 
                FROM node__field_program_youth_served
                GROUP BY entity_id) as programYouthServed ON programYouthServed.entity_id = orgEntity.entity_id          
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_youth_other_value) as programYouthServedOther 
                FROM node__field_program_youth_other
                GROUP BY entity_id) as programYouthServedOther ON programYouthServedOther.entity_id = orgEntity.entity_id
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_gender_mentor_targ_value) as programMentorGenders 
                FROM node__field_program_gender_mentor_targ
                GROUP BY entity_id) as programMentorGenders ON programMentorGenders.entity_id = orgEntity.entity_id          
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_gender_mentor_oth_value) as programMentorGendersOther 
                FROM node__field_program_gender_mentor_oth
                GROUP BY entity_id) as programMentorGendersOther ON programMentorGendersOther.entity_id = orgEntity.entity_id
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_ages_mentor_target_value) as programMentorAges 
                FROM node__field_program_ages_mentor_target
                GROUP BY entity_id) as programMentorAges ON programMentorAges.entity_id = orgEntity.entity_id          
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_age_mentor_other_value) as programMentorAgesOther 
                FROM node__field_program_age_mentor_other
                GROUP BY entity_id) as programMentorAgesOther ON programMentorAgesOther.entity_id = orgEntity.entity_id
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_ns_bg_check_value) as nsBackgroundCheck 
                FROM node__field_ns_bg_check
                GROUP BY entity_id) as nsBackgroundCheck ON nsBackgroundCheck.entity_id = orgEntity.entity_id
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_ns_bg_check_types_value) as nsBackgroundCheckTypes 
                FROM node__field_ns_bg_check_types
                GROUP BY entity_id) as nsBackgroundCheckTypes ON nsBackgroundCheckTypes.entity_id = orgEntity.entity_id          
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_ns_training_value) as nsTrainingValue 
                FROM node__field_ns_training
                GROUP BY entity_id) as nsTrainingValue ON nsTrainingValue.entity_id = orgEntity.entity_id     
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_accepting_value) as programAccepting 
                FROM node__field_program_accepting 
                GROUP BY entity_id) as accepting ON accepting.entity_id = orgEntity.entity_id
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_types_of_mentoring_value) as typesOfMentoring 
                FROM node__field_types_of_mentoring GROUP BY entity_id) as typesOfMentoring ON typesOfMentoring.entity_id = orgEntity.entity_id             
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_ns_bg_peer_type_value) as nsPeerBackgroundCheck 
                FROM node__field_ns_bg_peer_type GROUP BY entity_id) as nsPeerBackgroundCheck ON nsPeerBackgroundCheck.entity_id = orgEntity.entity_id 
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_types_of_mentoring_other_value) as typesOfMentoringOther 
                FROM node__field_types_of_mentoring_other GROUP BY entity_id) as typesOfMentoringOther ON typesOfMentoringOther.entity_id = orgEntity.entity_id               
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_mentor_month_commi_value) as mentorMonthCommitment 
                FROM node__field_program_mentor_month_commi GROUP BY entity_id) as mentorMonthCommitment ON mentorMonthCommitment.entity_id = orgEntity.entity_id  
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_mentor_freq_commit_value) as mentorFrequencyCommitment 
                FROM node__field_program_mentor_freq_commit GROUP BY entity_id) as mentorFrequencyCommitment ON mentorFrequencyCommitment.entity_id = orgEntity.entity_id
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_mentor_freq_other_value) as mentorFrequencyCommitmentOther 
                FROM node__field_program_mentor_freq_other GROUP BY entity_id) as mentorFrequencyCommitmentOther ON mentorFrequencyCommitmentOther.entity_id = orgEntity.entity_id
            LEFT JOIN 
                (SELECT entity_id, JSON_ARRAYAGG(field_program_mentor_hour_commit_value) as mentorHourlyCommitment 
                FROM node__field_program_mentor_hour_commit GROUP BY entity_id) as mentorHourlyCommitment ON mentorHourlyCommitment.entity_id = orgEntity.entity_id
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

}
