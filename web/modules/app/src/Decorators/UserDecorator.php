<?php

namespace Drupal\app\Decorators;

use Drupal\user\Entity\User;

class UserDecorator
{
    public User $entity;

    public function isGlobalAdministrator(): bool
    {
        return $this->entity->get('field_global_administrator')->getValue()[0]['value'] == '1';
    }
}
