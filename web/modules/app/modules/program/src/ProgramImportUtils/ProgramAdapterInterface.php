<?php

namespace Drupal\app_program\ProgramImportUtils;

interface ProgramAdapterInterface
{
    public function getExternalId();
    public function getTitle();
    public function getProgramDescription();
    public function getMentorRoleDescription();
    public function getOrganization();
    public function getContactFirstName();
    public function getContactLastName();
    public function getEmail();
    public function getPhone();
    public function getAlternatePhone();
    public function getProgramOffered();
    public function getProgramLocation();
    public function getFacebook();
    public function getTwitter();
    public function getWebsite();
    public function getInstagram();
    public function getFocusArea();
    public function getPrimaryMeetingLocation();
    public function getYouthServedPerYear();
    public function getMenteesOnWaitingList();
    public function getMentoringOffered();
    public function getProgramOperated();
    public function getMentorSchedule();
    public function getGendersServed();
    public function getAgesServed();
    public function getFamilyStructureProgram();
    public function getYouthServed();
    public function getTargetMentorGender();
    public function getTargetMentorAge();
    public function getMonthlyCommitment();
    public function getMentorFrequency();
    public function getAverageHour();
    public function getSource();
}
