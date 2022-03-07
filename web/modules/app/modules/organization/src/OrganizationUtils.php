<?php

namespace Drupal\app_organization;

use Drupal\app\Decorators\UserDecorator;
use Drupal\app\Factories\UserFactory;
use Drupal\app_program\ProgramRequestAdapter;

class OrganizationUtils
{
    public static function getFilterBuilder(ProgramRequestAdapter $adapter): OrganizationCollectionBuilder
    {
        $builder = (new OrganizationCollectionBuilder($adapter))
      ->start($adapter->filter['start_date'])
      ->end($adapter->filter['end_date'])
      ->orderBy($adapter->sortField, $adapter->sortDirection);

        if (\Drupal::currentUser()->isAuthenticated()) {
            $builder->orderBy($adapter->sortField, $adapter->sortDirection)
        ->whereUserIsStaff(UserFactory::currentUser());
        }

        if ($_REQUEST['view'] != "inquiry") {
            $builder->start($adapter->filter['start_date'])
        ->end($adapter->filter['end_date']);
        }

        $flatFields = [
            OrganizationFields::physicalLocation,
            OrganizationFields::webAddress,
            OrganizationFields::phone,
            OrganizationFields::altPhone,
            OrganizationFields::firstName,
            OrganizationFields::lastName,
            OrganizationFields::legalName,
            OrganizationFields::feedback,
            OrganizationFields::type,
            OrganizationFields::typeOther,
            OrganizationFields::taxStatus,
            OrganizationFields::taxStatusOther,
            OrganizationFields::position,
            OrganizationFields::positionOther,
            OrganizationFields::mentorCityEnabled,
            OrganizationFields::bbbscEnabled,
            OrganizationFields::displayTitle,
            OrganizationFields::hasLocation,
            OrganizationFields::description,
            OrganizationFields::mtgEnabled,
            OrganizationFields::email,
        ];

        foreach ($adapter->filter as $key => $value) {
            if (in_array($key, ['start_date', 'end_date'])) {
                continue;
            }
            if (in_array($key, $flatFields)) {
                $builder->flatFilter($key, $value);
                continue;
            }
            $builder->filter($key);
        }

        return $builder;
    }

    public static function userHasOrganizations(UserDecorator $user): bool
    {
        $isGlobalAdmin = $user->entity->get('field_global_administrator')->getValue()[0]['value'];
        if ($isGlobalAdmin) {
            return true;
        }
        $organizationCount = \Drupal::database()->query("SELECT
      COUNT(*) FROM node__field_administrators
      WHERE bundle = 'organization' AND field_administrators_target_id = :uid", [
            ':uid' => $user->entity->id()
        ])->fetchCol(0)[0];
        return $organizationCount == true;
    }
}
