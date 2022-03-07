<?php

namespace Drupal\app\Utils;

class UserUtils
{
    public static function loadByMail($mail)
    {
        $users = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->loadByProperties([
          'mail' => $mail,
      ]);
        if (count($users)) {
            return current($users);
        }
    }

    public static function getGlobalAdministratorUids()
    {
        $ids = \Drupal::entityQuery('user')
      ->condition('status', 1)
      ->condition('field_global_administrator', 1)
      ->execute();
        return array_values($ids);
    }
}
