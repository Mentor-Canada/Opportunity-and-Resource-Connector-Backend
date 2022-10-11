<?php

namespace Drupal\app_program;

use Drupal;
use Drupal\app\Collection\CollectionControllerBase;
use Drupal\app\Collection\CollectionControllerInterface;
use Drupal\app\CSVBuilder;

class ProgramCollectionController extends CollectionControllerBase implements CollectionControllerInterface
{
    public function __construct($http_kernel)
    {
        parent::__construct($http_kernel);
        $this->adapter = new ProgramRequestAdapter(Drupal::request());

        $builder = ProgramUtils::getFilterBuilder($this->adapter);

        $this->total = $builder->executeCount();
        $builder->range($this->adapter->offset, $this->adapter->limit);
        $this->data = $builder->execute();
    }

    public function csv()
    {
        $builder = new CSVBuilder($this->data, "programs.csv");
        $builder
      /** General */
      ->header("app-program-name-en")->text(ProgramFields::displayTitle)
      ->header("app-program-name-fr")->text(ProgramFields::displayTitle . "_fr")
      ->header("app-program-description-en")->text(ProgramFields::description)
      ->header("app-program-description-fr")->text(ProgramFields::description . "_fr")
      ->header("app-mentor-role-description-en")->text(ProgramFields::mentorDescription)
      ->header("app-mentor-role-description-fr")->text(ProgramFields::mentorDescription . "_fr")
      ->header("app-organization")->text("organization_title")

      /** Currently Accepting */
      ->header("app-program-status")->json(ProgramFields::accepting, true)

      /** Program Contact Information */
      ->header("app-first-name")->text(ProgramFields::firstName)
      ->header("app-last-name")->text(ProgramFields::lastName)
      ->header("app-contact-position")->text(ProgramFields::position)
      ->header("app-email")->text(ProgramFields::email)
      ->header("app-contact-phone")->text(ProgramFields::phone)
      ->header("app-contact-alternate-phone")->text(ProgramFields::altPhone)

      /** Delivery */
      ->header("app-program-is-delivered-how")->text(ProgramFields::delivery, true)
      ->header("app-locations")->json(ProgramFields::physicalLocations)

      /** Social */
      ->header("app-program-facebook")->text(ProgramFields::facebook)
      ->header("app-program-twitter")->text(ProgramFields::twitter)
      ->header("app-program-website")->text(ProgramFields::website)
      ->header("app-program-instagram")->text(ProgramFields::instagram)

      /** Program Details */
      ->header("app-program-focus-area")->text(ProgramFields::focusArea, true)
      ->header("app-program-focus-area-other")->text(ProgramFields::focusAreaOther)
      ->header("app-program-primary-meeting-location")->text(ProgramFields::primaryMeetingLocation, true)
      ->header("app-program-estimated-number-of-youth-served-per-year")->text(ProgramFields::primaryMeetingLocationOther)
      ->header("app-program-estimated-number-of-youth-served-per-year")->text(ProgramFields::youthPerYear)
      ->header("app-program-estimated-number-of-mentees-on-waiting-list")->text(ProgramFields::menteesWaitingList)
      ->header("app-program-type-of-mentoring")->json(ProgramFields::typesOfMentoring, true)
      ->header("app-program-type-of-mentoring-other")->text(ProgramFields::typesOfMentoringOther)
      ->header("app-program-operated-through")->json(ProgramFields::operatedThrough, true)
      ->header("app-program-operated-through-other")->text(ProgramFields::operatedOther)
      ->header("app-program-how-are-meetings-scheduled")->json(ProgramFields::howAreMeetingsScheduled, true)
      ->header("app-program-how-are-meetings-scheduled-other")->text(ProgramFields::howOther)
      ->header("app-program-genders-program-serves")->json(ProgramFields::gendersServed, true)
      ->header("app-program-genders-program-serves-other")->json(ProgramFields::gendersOther, true)
      ->header("app-program-ages-program-serves")->json(ProgramFields::agesServed, true)
      ->header("app-program-ages-program-serves-other")->json(ProgramFields::agesOther, true)
      ->header("app-program-grade-program-serves")->json(ProgramFields::gradesServed, true)
      ->header("app-program-family-structures-program-serves")->json(ProgramFields::familyServed, true)
      ->header("app-program-family-structures-program-serves-other")->text(ProgramFields::familyOther)
      ->header("app-program-youth-program-serves")->json(ProgramFields::youthServed, true)
      ->header("app-program-youth-program-serves-other")->text(ProgramFields::youthOther)
      ->header("app-program-target-mentor-population-genders")->json(ProgramFields::genderMentorTarget, true)
      ->header("app-program-target-mentor-population-genders-other")->text(ProgramFields::genderMentorOther)
      ->header("app-program-target-mentor-population-ages")->json(ProgramFields::agesMentorTarget, true)
      ->header("app-field-program-age-mentor-other")->text(ProgramFields::ageMentorOther)

      /** CA National Standards */
////      ->header("app-field-program-matches-explain")->text(ProgramFields::matchesExplain)
//      ->header("app-field-program-beginning-and-end")->text(ProgramFields::beginningAndEnd)
//      ->header("app-field-program-has-specific-goals")->text(ProgramFields::hasSpecificGoals)
//      ->header("app-field-program-matches-how")->json(ProgramFields::matchesHow, true)
//      ->header("app-field-program-must-train-mentees")->text(ProgramFields::mustTrainMentees)
//      ->header("app-field-program-mentor-freq-commit")->json(ProgramFields::mentorFreqCommit, true)
//      ->header("app-field-program-mentor-freq-other"), fn($row) => "test")
//      ->header("app-field-program-mentor-hour-commit")->text(ProgramFields::mentorHourCommit, true)
//      ->header("app-field-program-mentor-hour-other")->text(ProgramFields::mentorHourOther)
//      ->header("app-field-program-mentor-month-commit")->text(ProgramFields::mentorMonthCommit, true)
//      ->header("app-field-program-mentor-month-other")->text(ProgramFields::mentorMonthOther)
//      ->header("app-field-program-must-train-mentors")->text(ProgramFields::trainsMentors)
//      ->header("app-field-program-ongoing-support")->text(ProgramFields::ongoingSupport)
//      ->header("app-field-program-screens-mentees")->text(ProgramFields::screensMentees)
//      ->header("app-field-program-screens-mentees-how")->text(ProgramFields::screensMenteesHow, true)
//      ->header("app-field-program-screens-mentors")->text(ProgramFields::screensMentors)
//      ->header("app-field-program-screens-mentors-how")->text(ProgramFields::screensMentorsHow, true)
//      ->header("app-field-program-which-goals")->text(ProgramFields::whichGoals)
//      ->header("app-field-program-which-goals-other")->text(ProgramFields::whichGoalsOther)
//      ->header("app-field-program-gender-mentor-other")->text(ProgramFields::genderMentorOther)
//      ->header("app-field-program-gender-mentor-target")->json(ProgramFields::genderMentorTarget, true)
//      ->header("app-field-program-trains-mentees")->text(ProgramFields::trainsMentees)
//      ->header("app-field-program-trains-mentees-how")->text(ProgramFields::trainsMenteesHow)

      /** USA National Standards */
      ->header("app-background-check")->text(ProgramFields::nsBgCheck, true)
      ->header("app-program-background-check-type")->json(ProgramFields::nsBgCheckTypes, true);
        if ($_ENV['COUNTRY'] != 'ca') {
            $builder->header("app-background-check-fingerprint-type")->text(ProgramFields::nsBgFingerprintType, true)
        ->header("app-background-check-name-type")->text(ProgramFields::nsBgNameType, true)
        ->header("app-background-check-other-type")->json(ProgramFields::nsBgOtherTypes, true);
        }
        $builder->header("app-background-check-peer-bg-check")->text(ProgramFields::nsPeerType, true)
      ->header("app-training")->text(ProgramFields::nsTraining, true)
      ->header("app-program-mentor-training")->text(ProgramFields::nsTrainingDescription)
      ->header("app-program-mentoring-relationship-commitment-months")->text(ProgramFields::mentorMonthCommit, true)
      ->header("app-program-mentoring-relationship-commitment-frequency")->text(ProgramFields::mentorFreqCommit, true)
      ->header("app-program-mentoring-relationship-commitment-frequency-other")->text(ProgramFields::mentorFreqOther, true)
      ->header("app-program-mentoring-relationship-commitment-hours-per-week")->text(ProgramFields::mentorHourCommit, true)

      /** Administrative */
      ->header("app-field-administrators")->json(ProgramFields::administrators)
      ->header("app-field-standing")->text(ProgramFields::standing, true)

      ->render()
    ;
    }

    public function collectionTotal(): int
    {
        return $this->total;
    }

    public function collectionData(): array
    {
        return $this->data;
    }

    public function paginationOffset(): ?int
    {
        return $this->adapter->offset;
    }

    public function paginationLimit(): ?int
    {
        return $this->adapter->limit;
    }
}
