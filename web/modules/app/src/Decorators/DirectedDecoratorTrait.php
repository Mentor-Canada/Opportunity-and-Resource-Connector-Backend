<?php

namespace Drupal\app\Decorators;

use Drupal\Core\Access\AccessResult;

trait DirectedDecoratorTrait
{
    public static function directorAccessResult($uuid): AccessResult
    {
        return parent::baseDirectorAccessResult($uuid, self::TYPE);
    }

    public static function validEntityAccessResult($uuid): AccessResult
    {
        return parent::baseValidEntityAccessResult($uuid, self::TYPE);
    }
}
