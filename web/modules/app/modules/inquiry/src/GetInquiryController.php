<?php

namespace Drupal\app_inquiry;

use Drupal\app\Controller\BaseController;
use Drupal\app\Utils\ProgramUtils;
use Drupal\app\Utils\Security;
use Drupal\Core\Access\AccessResult;
use Symfony\Component\HttpFoundation\JsonResponse;

class GetInquiryController extends BaseController
{
    private Inquiry $inquiry;

    public function __construct($http_kernel)
    {
        parent::__construct($http_kernel);
        $request = \Drupal::request();
        $uuid = $request->attributes->get('uuid');
        $this->inquiry = new Inquiry($uuid);
    }

    public function get()
    {
        $uilang = $_REQUEST['uilang'];
        return new JsonResponse(['data' => $this->inquiry->serialize($uilang)]);
    }

    public function access(): AccessResult
    {
        if (Security::globalAdministrator()) {
            return AccessResult::allowed();
        }

        $userPrograms = ProgramUtils::programsForUser();
        if (in_array($this->inquiry->programId, $userPrograms)) {
            return AccessResult::allowed();
        }

        return AccessResult::forbidden();
    }
}
