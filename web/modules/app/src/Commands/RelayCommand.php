<?php

namespace Drupal\app\Commands;

use Drupal\app\Utils\EmailUtils;
use Drush\Commands\DrushCommands;

class RelayCommand extends DrushCommands
{
    /**
     * @command app:relay
     * @param sender
     * @param size
     * @param recipient
     */
    public function relay($sender, $size, $recipient)
    {
        $email = file_get_contents('php://stdin');
        EmailUtils::relay($email);
    }
}
