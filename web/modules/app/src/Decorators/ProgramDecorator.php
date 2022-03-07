<?php

namespace Drupal\app\Decorators;

use Drupal\app\Utils\EntityUtils;
use Drupal\app\Utils\UserUtils;
use Drupal\app_program\ProgramFields;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

class ProgramDecorator extends DirectedFactoryBase implements DirectedDecoratorInterface
{
    use DirectedDecoratorTrait;

    public const JSONAPIType = 'node--programs';
    public const TYPE = 'programs';

    public static function createWithImportId($importId): ProgramDecorator
    {
        $q = \Drupal::entityQuery('node');
        $q->condition(ProgramFields::importId, $importId);
        $q->condition('type', 'programs');
        $ids = $q->execute();

        $program = new ProgramDecorator();

        if (!count($ids)) {
            $program->node = Node::create([
                'type' => 'programs',
                'langcode' => 'en',
                ProgramFields::importId => $importId
            ]);
        } else {
            $id = current($ids);
            $program->node = Node::load($id);
        }

        return $program;
    }

    public function save()
    {
        $this->node->save();
    }

    public function isDirector(User $account): bool
    {
        if ($account->get('field_global_administrator')->getValue()[0]['value'] == '1') {
            return true;
        }
        if (EntityUtils::isOrganizationAdmin($this->node)) {
            return true;
        }
        if (EntityUtils::isRegionalAdmin($this->node)) {
            return true;
        }
        return false;
    }

    public function getDirectorUids()
    {
        $uids = UserUtils::getGlobalAdministratorUids();
        $organizationId = $this->node->get('field_organization_entity')->getValue()[0]['target_id'];
        $organizationNode = Node::load($organizationId);
        if ($organizationNode) {
            $organizationAdministrators = $organizationNode->get('field_administrators')->getValue();
            foreach ($organizationAdministrators as $row) {
                $uids[] = $row['target_id'];
            }
        }

        $q = "SELECT TRIM(LEADING '0' FROM postal_code) FROM programs_locations WHERE entity_id = :entity_id";
        $zips = \Drupal::database()->query($q, [":entity_id" => $this->node->id()])->fetchCol(0);
        if (count($zips)) {
            $q = \Drupal::database()->select('node__field_zips', 'zips');
            $q->leftJoin('node__field_administrators', 'admins', 'admins.entity_id = zips.entity_id');
            $q->addField('admins', 'field_administrators_target_id');
            $q->condition('zips.field_zips_value', $zips, 'IN');
            $regionAdmins = $q->execute()->fetchCol();
            $uids = array_merge($uids, $regionAdmins);
        }


        return $uids;
    }

    public function isAdministrator(User $account): bool
    {
        if ($this->isDirector($account)) {
            return true;
        }
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
}
