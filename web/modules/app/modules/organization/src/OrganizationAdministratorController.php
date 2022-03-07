<?php

namespace Drupal\app_organization;

use Drupal\app\App;
use Drupal\app\Controller\EntityAdministratorController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class OrganizationAdministratorController extends EntityAdministratorController
{
    public static function post($uuid, $mail, $firstName = null, $lastName = null, $notify = true)
    {
        $result = EntityAdministratorController::addAdministrator($uuid, $mail, $firstName, $lastName);
        if (count($result['exists'])) {
            return new JsonResponse("User is already admin", Response::HTTP_BAD_REQUEST);
        }

        $organization = Organization::createFromNode($result['node']);
        $organization->sendNewAdministratorNotification(App::getInstance()->uilang, $mail, $result['accountIsNew']);

        return new JsonResponse(["status" => "user added"]);
    }
}
