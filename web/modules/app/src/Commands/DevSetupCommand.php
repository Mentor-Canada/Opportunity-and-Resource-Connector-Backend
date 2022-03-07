<?php

namespace Drupal\app\Commands;

use Drupal\user\Entity\User;
use Drush\Commands\DrushCommands;
use Exception;

/**
 * A drush command file.
 *
 * @package Drupal\app\Commands
 */
class DevSetupCommand extends DrushCommands
{
    /**
     * Setup dev database.
     *
     * @command app:devsetup
     */
    public function devsetup()
    {
        if ($_ENV['DEV'] != "true") {
            throw new Exception("Cannot run in production!");
        }

        $db = \Drupal::database();
        $q = $db->query("SELECT uid FROM users_field_data");
        $rows = $q->fetchAll();
        foreach ($rows as $row) {
            $uid = $row->uid;
            /** @var $user \Drupal\user\Entity\User */
            $user = User::load($uid);
            $user->setEmail("$uid@ubriety.com");
            $user->setUsername("$uid@ubriety.com");
            $user->setPassword("hello123");
            $user->save();
        }

        $user = User::load(1);
        $user->set('field_global_administrator', 1);
        $user->save();
    }
}
