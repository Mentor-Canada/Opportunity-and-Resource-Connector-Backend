<?php

namespace Drupal\app\Factories;

use Drupal\app\Decorators\OrganizationDecorator;
use Drupal\app\Decorators\ProgramDecorator;
use Drupal\app\Utils\Utils;
use Drupal\app_search\SearchDecorator;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @todo This class should be renamed NodeDecoratorFactory
 */
class NodeFactory
{
    public Node $node;

    public static function createWithUuid($uuid)
    {
        $node = Utils::loadNodeByUUid($uuid);
        return self::withNode($node);
    }

    /**
     * @deprecated use Node::createWithUuid
     */
    public static function abstractFactory($uuid)
    {
        return self::createWithUuid($uuid);
    }

    public static function withNode($node)
    {
        switch ($node->getType()) {
      case OrganizationDecorator::TYPE:
        $entity = new OrganizationDecorator();
        break;
      case ProgramDecorator::TYPE:
        $entity = new ProgramDecorator();
        break;
      case SearchDecorator::TYPE:
        $entity = new SearchDecorator();
        break;
      default:
        throw new HttpException(500, 'Invalid Node Type.');
    }
        /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $entity->node = $node;
        return $entity;
    }

    /**
     * @deprecated Use Node::createWithUuid
     */
    public static function factory($type, $uuid)
    {
        switch ($type) {
      case OrganizationDecorator::JSONAPIType:
        $entity = new OrganizationDecorator();
        break;
      case ProgramDecorator::JSONAPIType:
        $entity = new ProgramDecorator();
        break;
      default:
        throw new \Exception("Invalid Node Type");
    }
        /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $entity->node = Utils::loadNodeByUUid($uuid);
        return $entity;
    }
}
