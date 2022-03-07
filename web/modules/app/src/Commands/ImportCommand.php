<?php

namespace Drupal\app\Commands;

use Drupal\app\Utils\GooglePlaceUtils;
use Drupal\app\Utils\Utils;
use Drupal\node\Entity\Node;
use Drush\Commands\DrushCommands;

/**
 * A drush command file.
 *
 * @package Drupal\app\Commands
 */
class ImportCommand extends DrushCommands
{
    /**
     * @command app:import
     */
    public function import()
    {
        $path = realpath(DRUPAL_ROOT . "/../import/us_connector_programs.csv");
        $file = fopen($path, "r");
        $header = true;
        while (($data = fgetcsv($file)) !== false) {
            if ($header) {
                $header = false;
                continue;
            }

            $program = Node::create([
                'type' => 'programs'
            ]);
            $program->set('field_import_id', $data[0]);
            if ($data[1]) {
                $program->set('field_organization_entity', self::getOrganizationId(trim($data[1]), $data[11]));
            }
            $program->set('field_display_title', $data[2]);

            $address = "{$data[3]} $data[4] $data[5] $data[7]";
            /** TODO: $data[6] = physicalState **/
            $result = Utils::geocode($address);

            $country = GooglePlaceUtils::getComponent('country', $result['address_components']);
            $province = GooglePlaceUtils::getComponent('administrative_area_level_1', $result['address_components']);

            $program->set('field_physical_locations', [
                'place_id' => $result['place_id'],
                'type' => $result['types'][0],
                'name' => $result['formatted_address'],
                'lat' => $result['geometry']['location']['lat'],
                'lng' => $result['geometry']['location']['lng'],
                'country' => $country,
                'province' => $province
            ]);

            /** $data[8] = fax (Skip) */
            /** $data[9] = email (Skip column is empty) */
            $program->set('field_website', $data[10]);
            $program->set('created', strtotime($data[11]));
            /** $data[12] = enteredBy (Skip) */
            /** $data[13] = updatedBy (Skip) */
            /** $data[14] = updatedDate (Skip) */
            /** $data[15] = urlLink (Skip column is empty) */
            /** $data[16] = organizationType (Skip column is empty) */
            /** $data[17] = SC_subType1 (Skip column is empty) */
            /** $data[18] = SC_subType2 (Skip column is empty) */
            /** $data[19] = SC_subType3 (Skip column is empty) */
            /** TODO: $data[20] = statesServed (no field on Drupal)**/
            if (strlen($data[21]) <= 255) {
                $program->set('field_facebook', $data[21]);
            } else {
                print "field_facebook too long\n";
            }
            $program->set('field_twitter', $data[22]);
            /** $data[23] = state (Skip column is empty) */
            $program->set('field_external_id', $data[24]);
            /** TODO: $data[25] = eIN (no field on Drupal)**/
            /** TODO: $data[26] = physicalZipCodePlus4 (no field on Drupal)**/
            $program->set('field_phone', $data[27]);
            $program->set('field_types_of_mentoring', Utils::mapValues($data[28], 'field_types_of_mentoring'));
            $program->set('field_primary_meeting_location', Utils::mapValues($data[29], 'field_primary_meeting_location'));
            $program->set('field_primary_meeting_loc_other', $data[30]);
            /** TODO: $data[31] = otherMeetingLocations (no field on Drupal)**/
            $program->set('field_program_which_goals', Utils::mapValues($data[32], 'field_program_which_goals'));
            $program->set('field_program_which_goals_other', $data[33]);
            /** TODO: $data[34] = volunteerContactPrefix (no field on Drupal)**/
            $program->set('field_first_name', $data[35]); //not sure about this field (volunteerFirstName)
      $program->set('field_last_name', $data[36]); //not sure about this field (volunteerLastName)
      /** TODO: $data[37] = volunteerMiddleName (no field on Drupal)**/
            /** TODO: $data[38] = volunteerTitle (no field on Drupal)**/
            /** TODO: $data[39] = volunteerPhoneExt (no field on Drupal)**/
            /** TODO: $data[40] = volunteerFaxNumber (no field on Drupal)**/
            $program->set('field_email', $data[41]); //not sure about this field (volunteerEmail)
            /** TODO: $data[42] = programAcceptingMaleMentees (no field on Drupal)**/
            /** TODO: $data[43] = programAcceptingFemaleMentees (no field on Drupal)**/
            $program->set('field_program_ages_served', Utils::mapValues($data[44], 'field_program_ages_served'));
            $program->set('field_program_family_served', Utils::mapValues($data[45], 'field_program_family_served'));
            $program->set('field_program_family_other', $data[46]);
            $program->set('field_program_ages_other', $data[47]);
            $program->set('field_program_youth_served', Utils::mapValues($data[48], 'field_program_youth_served'));
            $program->set('field_program_youth_other', $data[49]);
            /** TODO: $data[50] = programAcceptingMaleVolunteers (possibility of merging with field_program_gender_mentor_targ)**/
            /** TODO: $data[51] = programAcceptingFemaleVolunteers (possibility of merging with field_program_gender_mentor_targ)**/
            $program->set('field_program_ages_mentor_target', Utils::mapValues($data[52], 'field_program_ages_mentor_target'));
            $program->set('field_program_age_mentor_other', $data[53]);
            $program->set('field_mentor_role_description', $data[54]);
            /** TODO: $data[55] = conductCriminalBackgroundChecksOnMentors (no field on Drupal)**/
            /** TODO: $data[56] = fingerprintbasedCriminalBackgroundCheck (no field on Drupal)**/
            /** TODO: $data[57] = typeOfFingerprintbasedBackgroundCheck (no field on Drupal)**/
            /** TODO: $data[58] = namebasedCriminalBackgroundChecks (no field on Drupal)**/
            /** TODO: $data[59] = typeOfNamebasedBackgroundCheck (no field on Drupal)**/
            /** TODO: $data[60] = otherRegisteries (no field on Drupal)**/
            $program->set('field_program_mentor_month_commi', $data[61]); // It was not possible to map the data
      $program->set('field_program_mentor_freq_commit', $data[62]); // It was not possible to map the data
      $program->set('field_program_mentor_freq_other', $data[63]);
            $program->set('field_program_mentor_hour_commit', $data[64]);
            $program->set('field_program_operated_through', Utils::mapValues($data[65], 'field_program_operated_through'));
            $program->set('field_program_operated_other', $data[66]);
            /** TODO: $data[67] = otherMeetingLocationsOther (no field on Drupal)**/
            $program->set('field_description', $data[68]);
            /** TODO: $data[69] = volunteerContactPrefixOther (no field on Drupal)**/
            /** TODO: $data[70] = programStatus (no field on Drupal)**/
            /** TODO: $data[71] = programType (no field on Drupal)**/
            /** TODO: $data[72] = partnerships_id (no field on Drupal)**/
            /** TODO: $data[73] = doesProgramServeMoreThanOneState (no field on Drupal)**/
            /** $data[74] = organizationName (Skip column is empty) */
            /** TODO: $data[75] = howDidYouHearAboutTheVRS (no field on Drupal)**/
            /** TODO: $data[76] = howDidYouHearAboutTheVRSOther (no field on Drupal)**/
            /** TODO: $data[77] = parentOrganizationEmail (no field on Drupal)**/
            /** TODO: $data[78] = parentOrganizationPhone (no field on Drupal)**/
            /** TODO: $data[79] = county (no field on Drupal)**/
            /** TODO: $data[80] = nQMSDesignation (no field on Drupal)**/
            /** TODO: $data[81] = programAdministratorPassword (no field on Drupal)**/
            $program->set('field_program_trains_mentors', Utils::mapValues($data[82], 'field_program_trains_mentors'));
            /** TODO: $data[83] = trainingForVolunteerMentors (no field on Drupal)**/
            /** TODO: $data[84] = inactive (no field on Drupal)**/
            /** TODO: $data[85] = noParentOrg (no field on Drupal)**/
            /** TODO: $data[86] = programAdministratorEmail (no field on Drupal)**/
            /** TODO: $data[87] = programAdministratorFax (no field on Drupal)**/
            /** TODO: $data[88] = programAdministratorFirstName (no field on Drupal)**/
            /** TODO: $data[89] = programAdministratorMiddleName (no field on Drupal)**/
            /** TODO: $data[90] = programAdministratorLastName (no field on Drupal)**/
            /** TODO: $data[91] = programAdministratorPhoneNumber (no field on Drupal)**/
            /** TODO: $data[92] = programAdministratorPhoneExt (no field on Drupal)**/
            /** TODO: $data[93] = programAdministratorPrefix (no field on Drupal)**/
            /** TODO: $data[94] = programAdministratorPrefixOther (no field on Drupal)**/
            /** TODO: $data[95] = programAdministratorTitle (no field on Drupal)**/
            /** TODO: $data[96] = programComments (no field on Drupal)**/
            /** TODO: $data[97] = latitude (no field on Drupal)**/
            /** TODO: $data[98] = longitude (no field on Drupal)**/
            $program->set('field_focus_area', $data[99]); // It was not possible to map the data
            $program->set('field_focus_area_other', $data[100]);
            /** TODO: $data[101] = chaptersBeingRepresented (no field on Drupal)**/
            /** TODO: $data[102] = surveyVersions_id (no field on Drupal)**/
            /** TODO: $data[103] = logo (no field on Drupal)**/
            /** TODO: $data[104] = badge (no field on Drupal)**/
            /** TODO: $data[105] = qms_logo (no field on Drupal)**/
            /** TODO: $data[106] = qms_stateBadge (no field on Drupal)**/
            /** TODO: $data[107] = qms_logoName (no field on Drupal)**/
            /** TODO: $data[108] = organizations_id (no field on Drupal)**/
            /** TODO: $data[109] = currentlyNotAcceptingVolunteers (no field on Drupal)**/
            /** TODO: $data[110] = parentOrgNotEntered (no field on Drupal)**/
            /** TODO: $data[111] = needToAddParentOrg (no field on Drupal)**/
            $program->set('field_program_youth_per_year', $data[112]);
            /** TODO: $data[113] = partnerships_id2 (no field on Drupal)**/
            /** TODO: $data[114] = outOfNetwork (no field on Drupal)**/
            /** TODO: $data[115] = reasonsDenied (no field on Drupal)**/
            /** TODO: $data[116] = cache_programResponse (no field on Drupal)**/
            /** TODO: $data[117] = cache_matchRate (no field on Drupal)**/
            /** TODO: $data[118] = cache_matchRateBucket (no field on Drupal)**/
            /** TODO: $data[119] = cache_programResponseBucket (no field on Drupal)**/
            /** TODO: $data[120] = excludeSummer (no field on Drupal)**/
            /** TODO: $data[121] = excludeMentees (no field on Drupal)**/
            /** TODO: $data[122] = linkedin (no field on Drupal)**/
            /** TODO: $data[123] = onboardTime (no field on Drupal)**/
            /** TODO: $data[124] = programAcceptingNonBinaryMentees (no field on Drupal)**/
            /** TODO: $data[125] = programAcceptingGenderqueerMentees (no field on Drupal)**/
            /** TODO: $data[126] = programAcceptingTwospiritMentees (no field on Drupal)**/
            /** TODO: $data[127] = programAcceptingNonBinaryVolunteers (no field on Drupal)**/
            /** TODO: $data[128] = programAcceptingGenderqueerVolunteers (no field on Drupal)**/
            /** TODO: $data[129] = programAcceptingTwoSpiritVolunteers (no field on Drupal)**/
            /** TODO: $data[130] = programAcceptingOtherGenderVolunteers (no field on Drupal)**/
            /** TODO: $data[131] = programAcceptingOtherGenderMentees (no field on Drupal)**/
            /** TODO: $data[132] = otherGenderMenteeDescriptor (no field on Drupal)**/
            /** TODO: $data[133] = otherAcceptedGenderVolunteerDescriptor (no field on Drupal)**/
            /** TODO: $data[134] = solelyPeer (no field on Drupal)**/
            $program->set('field_program_mentees_waiting_li', $data[135]);
            /** TODO: $data[136] = programAcceptsAdditionalMenteeGenderIdentities (no field on Drupal)**/
            /** TODO: $data[137] = programAcceptingAdditionalVolunteerGenderIdenties (no field on Drupal)**/

            $program->save();
        }
    }

    public static function getOrganizationId($title, $creationDate)
    {
        $q = \Drupal::entityQuery('node');
        $q->condition('field_import_id', $title);
        $q->condition('type', 'organization');
        $ids = $q->execute();
        if (count($ids)) {
            return current($ids);
        }

        $organization = Node::create([
            'type' => 'organization'
        ]);
        $organization->set('field_display_title', $title);
        $organization->set('field_import_id', $title);
        $organization->set('created', strtotime($creationDate));
        $organization->save();
        return $organization->id();
    }
}
