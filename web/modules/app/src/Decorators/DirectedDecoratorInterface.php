<?php

namespace Drupal\app\Decorators;

use Drupal\Core\Access\AccessResult;
use Drupal\user\Entity\User;

interface DirectedDecoratorInterface
{
    public function isDirector(User $account): bool;
    public function isAdministrator(User $account): bool;
    public static function directorAccessResult($uuid): AccessResult;
}
