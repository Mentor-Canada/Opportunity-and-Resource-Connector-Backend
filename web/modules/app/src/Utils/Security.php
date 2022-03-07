<?php

namespace Drupal\app\Utils;

use Drupal\Core\Access\AccessResult;
use Drupal\user\Entity\User;

class Security
{
    private const mailField = "mail";
    private const nameField = "name";
    private const globalAdministratorField = 'field_global_administrator';

    public static function globalAdministrator(): bool
    {
        $user = \Drupal::currentUser()->getAccount();
        $account = User::load($user->id());
        if ($user->id() == 1) {
            return true;
        }

        $globalAdministrator = $account->get(self::globalAdministratorField)->getValue()[0]['value'];
        if ($globalAdministrator === "1") {
            return true;
        }
        return false;
    }

    public static function globalAdministratorAccess()
    {
        return self::globalAdministrator() ? AccessResult::allowed() : AccessResult::forbidden();
    }

    public static function validateProfileSave($entity)
    {
        $account = \Drupal::currentUser()->getAccount();
        /** reset with otp. */
        if ($account->id() == 0) {
            return;
        }

        if (self::globalAdministrator($account)) {
            return;
        }

        $user = User::load($entity->id());
        self::readOnly(self::globalAdministratorField, $user, $entity);
        self::readOnly(self::nameField, $user, $entity);
        self::readOnly(self::mailField, $user, $entity);
    }

    private static function readOnly($field, $entity1, $entity2)
    {
        if ($entity1->get($field)->getValue()[0]['value']
      != $entity2->get($field)->getValue()[0]['value']) {
            self::deny();
        }
    }

    private static function deny()
    {
        header('HTTP/1.0 403 Forbidden');
        exit;
    }
}
