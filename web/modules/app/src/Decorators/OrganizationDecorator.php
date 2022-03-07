<?php

namespace Drupal\app\Decorators;

use Drupal\app\Utils\EntityUtils;
use Drupal\app\Utils\UserUtils;
use Drupal\app_organization\OrganizationFields;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

class OrganizationDecorator extends DirectedFactoryBase implements DirectedDecoratorInterface
{
    use DirectedDecoratorTrait;

    public const JSONAPIType = 'node--organization';
    public const TYPE = 'organization';

    public function isDirector(User $account): bool
    {
        return $account->get('field_global_administrator')->getValue()[0]['value'] == '1';
    }

    public function getDirectorUids()
    {
        return UserUtils::getGlobalAdministratorUids();
    }

    public function isAdministrator(User $account): bool
    {
        $globalAdministratorArray = $account->get('field_global_administrator')->getValue()[0];
        $globalAdminValue = $globalAdministratorArray['value'];
        if ($globalAdminValue) {
            return true;
        }
        if (EntityUtils::isAdmin($this->node, $account)) {
            return true;
        }
        return false;
    }

    public static function createWithName($name): OrganizationDecorator
    {
        $q = \Drupal::entityQuery('node');
        $q->condition(OrganizationFields::displayTitle, $name);
        $q->condition('type', 'organization');
        $ids = $q->execute();

        $organization = new OrganizationDecorator();

        if (!count($ids)) {
            $organization->node = Node::create([
                'type' => 'organization',
                'langcode' => 'en',
                OrganizationFields::displayTitle => $name,
                OrganizationFields::standing => "app-allowed",
            ]);
            $organization->node->save();
        } else {
            $id = current($ids);
            $organization->node = Node::load($id);
        }

        return $organization;
    }
}
