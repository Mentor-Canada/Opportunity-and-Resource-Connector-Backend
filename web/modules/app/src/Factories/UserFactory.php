<?php

namespace Drupal\app\Factories;

use Drupal\app\Decorators\UserDecorator;
use Drupal\user\Entity\User;

class UserFactory
{
    public static function currentUser(): UserDecorator
    {
        $user = new UserDecorator();
        /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $user->entity = User::load(\Drupal::currentUser()->id());
        return $user;
    }
}
