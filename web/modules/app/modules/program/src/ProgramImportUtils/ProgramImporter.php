<?php

namespace Drupal\app_program\ProgramImportUtils;

use Drupal\app\Utils\GooglePlaceUtils;
use Drupal\node\Entity\Node;

class ProgramImporter
{
    private $externalId;
    private $title;
    private $programDescription;
    private $mentorRoleDescription;
    private $organization;
    private $contactFirstName;
    private $contactLastName;
    private $email;
    private $phone;
    private $alternatePhone;
    private $programOffered;
    private $programLocation;
    private $facebook;
    private $twitter;
    private $website;
    private $instagram;
    private $focusArea;
    private $primaryMeetingLocation;
    private $youthServedPerYear;
    private $menteesOnWaitingList;
    private $mentoringOffered;
    private $programOperated;
    private $mentorSchedule;
    private $gendersServed;
    private $agesServed;
    private $familyStructureProgram;
    private $youthServed;
    private $targetMentorGender;
    private $targetMentorAge;
    private $monthlyCommitment;
    private $mentorFrequency;
    private $averageHour;
    private $source;

    public function __construct(ProgramAdapterInterface $adapter)
    {
        $this->externalId = $adapter->getExternalId();
        $this->title = $adapter->getTitle();
        $this->programDescription = $adapter->getProgramDescription();
        $this->mentorRoleDescription = $adapter->getMentorRoleDescription();
        $this->organization = $adapter->getOrganization();
        $this->contactFirstName = $adapter->getContactFirstName();
        $this->contactLastName = $adapter->getContactLastName();
        $this->email = $adapter->getEmail();
        $this->phone = $adapter->getPhone();
        $this->alternatePhone = $adapter->getAlternatePhone();
        $this->programOffered = $adapter->getProgramOffered();
        $this->programLocation = $adapter->getProgramLocation();
        $this->facebook = $adapter->getFacebook();
        $this->twitter = $adapter->getTwitter();
        $this->website = $adapter->getWebsite();
        $this->instagram = $adapter->getInstagram();
        $this->focusArea = $adapter->getFocusArea();
        $this->primaryMeetingLocation = $adapter->getPrimaryMeetingLocation();
        $this->youthServedPerYear = $adapter->getYouthServedPerYear();
        $this->menteesOnWaitingList = $adapter->getMenteesOnWaitingList();
        $this->mentoringOffered = $adapter->getMentoringOffered();
        $this->programOperated = $adapter->getProgramOperated();
        $this->mentorSchedule = $adapter->getMentorSchedule();
        $this->gendersServed = $adapter->getGendersServed();
        $this->agesServed = $adapter->getAgesServed();
        $this->familyStructureProgram = $adapter->getFamilyStructureProgram();
        $this->youthServed = $adapter->getYouthServed();
        $this->targetMentorGender = $adapter->getTargetMentorGender();
        $this->targetMentorAge = $adapter->getTargetMentorAge();
        $this->monthlyCommitment = $adapter->getMonthlyCommitment();
        $this->mentorFrequency = $adapter->getMentorFrequency();
        $this->averageHour = $adapter->getAverageHour();
        $this->source = $adapter->getSource();
    }

    public function import()
    {
        $externalIdCheck = \Drupal::database()->query(
            "SELECT DISTINCT external_id FROM programs WHERE external_id = :external_id AND source = :source",
            [
                ":external_id" => $this->externalId,
                ":source" => $this->source
            ]
        )->fetchCol();
        if (count($externalIdCheck)) {
            $this->updateProgram();
        } else {
            $this->createNewProgram();
        }
    }

    public function createNewProgram()
    {
        $node = Node::create($this->getNodeFields());
        $node->save();
        $entity_id = $node->id();

        $fields = $this->getFlatFields($entity_id);
        if ($this->programLocation) {
            $fields[':siteBased'] = 1;
        }
        \Drupal::database()
      ->insert("programs")
      ->fields($fields)
      ->execute()
    ;

        if ($this->programLocation) {
            $placeDataFields = $this->getLocationFields($entity_id);
            \Drupal::database()
        ->insert("programs_locations")
        ->fields($placeDataFields)
        ->execute()
      ;
        }
    }

    public function updateProgram()
    {
        $entity_id = \Drupal::database()->query(
            "SELECT entity_id from programs WHERE external_id = :external_id AND
                source = :source AND NOT external_id IS NULL",
            [
                ":external_id" => $this->externalId,
                ":source" => $this->source
            ]
        )->fetchCol()[0];
        $node = Node::load($entity_id);
        $this->updateNode($node);

        $fields = $this->getFlatFields($node->id());
        if ($this->programLocation) {
            $fields[':siteBased'] = 1;
        }
        \Drupal::database()
      ->update("programs")
      ->fields($fields)
      ->condition('entity_id', $entity_id)
      ->execute()
    ;

        if ($this->programLocation) {
            $placeDataFields = $this->getLocationFields($entity_id);
            \Drupal::database()
        ->update("programs_locations")
        ->fields($placeDataFields)
        ->condition('entity_id', $node->id())
        ->execute()
      ;
        }
    }

    public function updateNode($node)
    {
        $node->field_facebook = $this->facebook;
        $node->field_twitter = $this->twitter;
        $node->field_website = $this->website;
        $node->field_instagram = $this->instagram;
        $node->save();
    }

    public function getLocationFields($entity_id)
    {
        $postalCode = GooglePlaceUtils::getComponent(
            'postal_code',
            $this->programLocation->address_components
        );
        return [
            ':entity_id' => $entity_id,
            ':location' => json_encode($this->programLocation),
            ':type' => 'siteBased',
            ':postal_code' => $postalCode
        ];
    }

    public function getFlatFields($entity_id)
    {
        return [
            ':entity_id' => $entity_id,
            ':external_id' => $this->externalId,
            ':title' => json_encode($this->title),
            ':programDescription' => json_encode($this->programDescription),
            ':mentorDescription' => json_encode($this->mentorRoleDescription),
            ':first_name' => $this->contactFirstName,
            ':last_name' => $this->contactLastName,
            ':email' => $this->email,
            ':phone' => $this->phone,
            ':altPhone' => $this->alternatePhone,
            ':source' => $this->source
        ];
    }

    public function getNodeFields()
    {
        return [
            'type' => 'programs',
            'field_standing' => 'app-pending',
            'field_facebook' => $this->facebook,
            'field_twitter' => $this->twitter,
            'field_website' => $this->website,
            'field_instagram' => $this->instagram
        ];
    }
}
