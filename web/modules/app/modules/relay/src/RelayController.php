<?php

namespace Drupal\app_relay;

use Drupal\app\Commands\EmailBuilder;
use Drupal\app\Controller\BaseController;
use Drupal\app\Utils\Utils;
use Drupal\app_inquiry\Inquiry;
use Drupal\app_program\Program;
use Symfony\Component\HttpFoundation\JsonResponse;

class RelayController extends BaseController
{
    public function post()
    {
        $adapter = new SendgridAdapter();
        \Drupal::logger('relay')->error("Relay attempt {$adapter->inquiryId}");

        $inquiry = new Inquiry($adapter->inquiryId);
        $applicant = Utils::getApplicantInfo($adapter->inquiryId);

        \Drupal::logger('relay')->error("{$applicant['email']}");

        (new EmailBuilder())
      ->from($adapter->fromEmail, $adapter->fromName)
      ->to($applicant['email'], $applicant['firstName'], $applicant['lastName'])
      ->subject($adapter->subject)
      ->html($adapter->html)
      ->text($adapter->text)
      ->files($adapter->files)
      ->send();

        if ($inquiry->status != 'app-contacted') {
            \Drupal::database()->query("UPDATE inquiries SET status = 'app-contacted' WHERE uuid = :uuid", [
                ":uuid" => $adapter->inquiryId
            ]);
        }

        $program = new Program();
        $program->id = $inquiry->programId;
        $program->computeResponsiveness();
        $program->saveResponsiveness();

        return new JsonResponse(['status' => 'success']);
    }
}
