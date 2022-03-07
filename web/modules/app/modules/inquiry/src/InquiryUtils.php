<?php

namespace Drupal\app_inquiry;

use Drupal\app\Utils\ApplicationUtils;

class InquiryUtils
{
    public static function create($adapter)
    {
        $fields = [
            "searchId" => $adapter->searchId,
            "programId" => $adapter->programId,
            "firstName" => $adapter->firstName,
            "lastName" => $adapter->lastName,
            "email" => $adapter->email,
            "phone" => $adapter->phone,
            "voice" => $adapter->voice,
            "role" => $adapter->role,
            "sms" => $adapter->sms,
            "created" => $adapter->created,
        ];

        $id = \Drupal::database()->insert("inquiries")
      ->fields($fields)
      ->execute()
    ;

        $uuid = \Drupal::database()->query("SELECT UUID() FROM inquiries WHERE id = :id", [
            ':id' => $id
        ])->fetchCol();
        $uuid = current($uuid);

        \Drupal::database()->update("inquiries")
      ->fields(["uuid" => $uuid])
      ->condition("id", $id)
      ->execute();
        $adapter->uuid = $uuid;

        ApplicationUtils::sendSubmittedReceipt($adapter);
        ApplicationUtils::sendSubmittedAdminNotification($adapter);
    }
}
