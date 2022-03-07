<?php

namespace Drupal\app\Decorators;

use Drupal\app\Factories\NodeFactory;
use Drupal\Core\Access\AccessResult;
use Drupal\user\Entity\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

abstract class DirectedFactoryBase extends NodeFactory
{
    public static function baseDirectorAccessResult($uuid, $type): AccessResult
    {
        $account = User::load(\Drupal::currentUser()->id());
        $entity = NodeFactory::abstractFactory($uuid);
        if ($entity->node->getType() != $type) {
            throw new HttpException(500, 'Invalid uuid.');
        }
        /** @noinspection PhpParamsInspection */
        $isDirector = $entity->isDirector($account);
        return $isDirector ? AccessResult::allowed() : AccessResult::forbidden();
    }

    public static function baseValidEntityAccessResult($uuid, $type)
    {
        return NodeFactory::abstractFactory($uuid)->node != $type ?
      AccessResult::allowed() : AccessResult::forbidden();
    }
}
