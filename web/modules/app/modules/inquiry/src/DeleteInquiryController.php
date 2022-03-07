<?php

namespace Drupal\app_inquiry;

use Drupal\app\Controller\BaseController;
use Drupal\app\Utils\Utils;
use Drupal\Core\Access\AccessResult;
use Symfony\Component\HttpFoundation\JsonResponse;

class DeleteInquiryController extends BaseController
{
    public function delete($uuid)
    {
        $node = Utils::loadNodeByUUid($uuid);
        $node->delete();
        return new JsonResponse(['stats' => 'success']);
    }

    public function access($uuid): AccessResult
    {
        $node = Utils::loadNodeByUUid($uuid);
        $createdBy = $node->get('uid')->getValue()[0]['target_id'];
        $account = \Drupal::currentUser();
        if ($account->id() == $createdBy) {
            return AccessResult::allowed();
        }
        return AccessResult::forbidden();
    }
}
