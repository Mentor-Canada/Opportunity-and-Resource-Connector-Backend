<?php

namespace Drupal\app\Commands;

use Drush\Commands\DrushCommands;
use PDO;

class ImportZipCodes extends DrushCommands
{
    /**
     * Setup dev database.
     *
     * @command app:importZip
     */
    public function importZip()
    {
        $dbUsername = $_ENV['DATABASE_USER'] ?: 'root';
        $dbPassword = $_ENV['DATABASE_PASSWORD'] ?: '';
        $pdo = new PDO('mysql:host=localhost;dbname=civicore_connector', $dbUsername, $dbPassword);
        $q = $pdo->query("SELECT * FROM US$");
        $rows = $q->fetchAll();
        $pdo = null;

        $db = \Drupal::database();
        $q = $db->insert('zipCodes')->fields(['zip', 'state', 'abrv', 'city', 'county']);
        foreach ($rows as $row) {
            $record = [
                'zip' => $row['Zip'],
                'state' => $row['State'],
                'abrv' => $row['Abbrev'],
                'city' => $row['City'],
                'county' => $row['County']
            ];
            $q->values($record);
        }
        $q->execute();
    }
}
