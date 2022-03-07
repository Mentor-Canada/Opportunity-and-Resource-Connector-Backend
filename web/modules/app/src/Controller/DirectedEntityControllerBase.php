<?php

namespace Drupal\app\Controller;

use Drupal\app\Factories\NodeFactory;
use Drupal\app\Factories\UserFactory;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

abstract class DirectedEntityControllerBase extends ControllerBase
{
    public function me($uuid)
    {
        $entity = NodeFactory::abstractFactory($uuid);

        return new JsonResponse([
            'data' => [
                'director' => $entity->isDirector(UserFactory::currentUser()->entity)
            ]
        ]);
    }
}
