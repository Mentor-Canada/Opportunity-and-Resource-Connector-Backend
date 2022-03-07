<?php

namespace Drupal\app_notify;

use Drupal;
use Symfony\Component\HttpFoundation\JsonResponse;

class CreateNotifyRequestController
{
    public function post()
    {
        $data = json_decode(Drupal::request()->getContent());
        $notify = new NotifyDecorator();
        $notify->email = $data->email;
        $search = Drupal\app\Utils\Utils::loadNodeByUUid($data->searchId);
        $notify->searchId = $search->id();
        $notify->save();
        return new JsonResponse(["data" => "success"]);
    }
}
