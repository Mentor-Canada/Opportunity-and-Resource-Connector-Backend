<?php

namespace Drupal\app\Commands;

use Drush\Commands\DrushCommands;

class USSetupCommand extends DrushCommands
{
    /**
     *
     * Workaround to disable Canada specific configuration.
     * Remove after [config split](https://www.drupal.org/project/config_split) is implemented.
     *
     * @command app:ussetup
     */
    public function ussetup()
    {
        $languages = \Drupal::languageManager()->getLanguages();
        foreach ($languages as $language) {
            $id = $language->getId();
            if ($id != 'en') {
                \Drupal::configFactory()->getEditable("language.entity.{$id}")->delete();
            }
        }

        drupal_flush_all_caches();
    }
}
