<?php

namespace Drupal\app_inquiry;

use Drupal\app\Controller\BaseController;
use Drupal\app\Utils\ApplicationUtils;
use Drupal\app_program\Program;
use Symfony\Component\HttpFoundation\JsonResponse;

class CreateInquiryController extends BaseController
{
    public function post()
    {
        $content = json_decode(\Drupal::request()->getContent());
        $adapter = new CreateInquiryRequestAdapter($content);

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
            "how" => $adapter->how,
            "howOther" => $adapter->howOther,
            "created" => $adapter->created
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

        $program = new Program();
        $program->id = $adapter->programId;
        $program->computeResponsiveness();
        $program->saveResponsiveness();

        $row = \Drupal::database()->query("SELECT
       id,
       uuid,
       programId,
       firstName,
       lastName,
       email,
       phone,
       voice,
       role,
       sms,
       how,
       howOther
       FROM inquiries WHERE id = :id
    ", [
            ':id' => $id
        ])->fetchAll();

        return new JsonResponse(['data' => current($row)]);
    }
}
