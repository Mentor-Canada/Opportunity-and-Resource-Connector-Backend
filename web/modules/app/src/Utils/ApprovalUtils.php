<?php

namespace Drupal\app\Utils;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

class ApprovalUtils
{
    public static function access(NodeInterface $node, $op, AccountInterface $account)
    {
        $nid = $node->get('field_approval_entity')->getValue()[0]['target_id'];
        $entity = Node::load($nid);
        $type = $entity->getType();
        if ($type == 'programs') {
            return ProgramUtils::access($entity, $op, $account);
        }
        if ($type == 'organization') {
            return ProgramUtils::access($entity, $op, $account);
        }
        return AccessResult::forbidden();
    }
}
