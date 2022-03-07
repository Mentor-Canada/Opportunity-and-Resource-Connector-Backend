<?php

use Drupal\app\Utils\ApprovalUtils;
use Drupal\app\Utils\OrganizationUtils;
use Drupal\app\Utils\ProgramUtils;
use Drupal\app\Utils\RegionUtils;
use Drupal\app\Utils\Security;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Implements hook_entity_create_access().
 */
function app_entity_create_access(AccountInterface $account, array $context, $entity_bundle)
{
    if ($context['entity_type_id'] == 'user') {
        return AccessResult::allowedIf(Security::globalAdministrator());
    }
    if ($context['entity_type_id'] == 'node') {
        switch ($entity_bundle) {
      case 'application':
      case 'search':
      case 'programs':
      case 'organization':
        return AccessResult::allowed();
      case 'region':
      case 'partner':
        return AccessResult::allowedIf(Security::globalAdministrator());
      case 'approval':
        $user = \Drupal::currentUser();
        if ($user->id()) {
            return AccessResult::allowed();
        }
    }
    }
    return AccessResult::neutral();
}

function app_user_access(EntityInterface $entity, $operation, AccountInterface $account)
{
    if ($operation == 'view' || $operation == 'view label') {
        return AccessResult::allowed();
    }
    if ($operation == 'update' || $operation == 'delete') {
        $globalAdministrator = Security::globalAdministrator();
        if ($globalAdministrator) {
            return AccessResult::allowedIf(Security::globalAdministrator());
        }

        /** Own account some edits allowed. Validate in app_entity_presave. */
        if ($entity->id() == $account->id()) {
            return AccessResult::allowed();
        }
    }
    return AccessResult::neutral();
}

/**
 * Implements hook_node_access().
 * Hook does not run for UID 1.
 */
function app_node_access(NodeInterface $node, $op, AccountInterface $account)
{
    if (Security::globalAdministrator()) {
        return AccessResult::allowed();
    }
    $type = $node->getType();
    if ($type == 'organization') {
        return OrganizationUtils::access($node, $op, $account);
    }
    if ($type == 'programs') {
        return ProgramUtils::access($node, $op, $account);
    }
    if ($type == 'region') {
        return RegionUtils::access($node, $op, $account);
    }
    if ($type == 'partner') {
        return Security::globalAdministratorAccess();
    }
    if ($type == 'approval') {
        return ApprovalUtils::access($node, $op, $account);
    }
    return AccessResult::neutral();
}
