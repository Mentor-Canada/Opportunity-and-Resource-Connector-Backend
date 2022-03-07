<?php

namespace Drupal\app_program;

use Drupal\app\Factories\UserFactory;
use Drupal\app_filter\FilterCollectionBuilder;
use Drupal\app_organization\OrganizationUtils;

class ProgramUtils
{
    public static function getFilterBuilder(ProgramRequestAdapter $adapter): ProgramCollectionBuilder
    {
        $builder = (new ProgramCollectionBuilder($adapter))
      ->orderBy($adapter->sortField, $adapter->sortDirection)
      ->whereUserIsStaff(UserFactory::currentUser())
      ->start($adapter->filter['start_date'])
      ->end($adapter->filter['end_date'])
    ;

        $multipleValueFields = [
            ProgramFields::accepting,
            ProgramFields::physicalLocations,
            ProgramFields::agesServed,
            ProgramFields::familyServed,
            ProgramFields::gendersServed,
            ProgramFields::gradesServed,
            ProgramFields::howAreMeetingsScheduled,
            ProgramFields::operatedThrough,
            ProgramFields::typesOfMentoring,
            ProgramFields::agesMentorTarget,
            ProgramFields::genderMentorTarget,
            ProgramFields::youthServed,
            ProgramFields::nsBgCheckTypes,
            ProgramFields::nsBgOtherTypes
        ];

        $flatFields = [
            ProgramFields::firstName,
            ProgramFields::lastName,
            ProgramFields::email,
            ProgramFields::position,
            ProgramFields::phone,
            ProgramFields::altPhone,
            ProgramFields::siteBased,
            ProgramFields::communityBased,
            ProgramFields::eMentoring,
            ProgramFields::nsTrainingDescription,
            ProgramFields::source
        ];

        $localizedFields = [
            ProgramFields::displayTitle,
            ProgramFields::description,
            ProgramFields::mentorDescription
        ];

        foreach ($adapter->filter as $key => $value) {
            if (in_array($key, ['start_date', 'end_date', 'dateMode'])) {
                continue;
            }
            if ($key == ProgramFields::organizationEntity) {
                $builder->filter($key, null, 'target_id');
                continue;
            }
            if ($key == ProgramFields::organizationFilter) {
                $ids = $adapter->getFilter($key);
                $filters = (new FilterCollectionBuilder())->ids($ids)->execute();
                $organizationIds = [];
                foreach ($filters as $filter) {
                    $data = json_decode($filter->data, true);
                    $organizationIds = array_merge($organizationIds, self::getOrganizationIdsFromFilter($data));
                }
                $builder->filter(ProgramFields::organizationEntity, 'organization', 'target_id', $organizationIds);
                continue;
            }
            if ($key == "location") {
                $builder->filterLocation($value);
                continue;
            }
            if (in_array($key, $multipleValueFields)) {
                $builder->filterMultiple($key);
                continue;
            }
            if (in_array($key, $flatFields)) {
                $builder->flatFilter($key, $value);
                continue;
            }
            if (in_array($key, $localizedFields)) {
                $builder->localizedFilter($key, $value);
                continue;
            }
            $builder->filter($key);
        }
        return $builder;
    }

    private static function getOrganizationIdsFromFilter($filter)
    {
        $adapter = new ProgramRequestAdapter();
        $adapter->filter = $filter;
        $builder = OrganizationUtils::getFilterBuilder($adapter);
        $result = $builder->execute();
        return array_map(fn ($a) => $a->nid, $result);
    }
}
