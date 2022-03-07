<?php

namespace Drupal\app\Plugin\rest\resource;

use Drupal\app\Utils\Utils;
use Drupal\rest\Annotation\RestResource;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @RestResource(
 *   id = "applicant_resource",
 *   label = @Translation("Applicant Resource"),
 *   uri_paths = {
 *     "canonical" = "/a/utils/applicant"
 *   }
 * )
 */
class ApplicantResource extends ResourceBase
{
    public function get()
    {
        if (!isset($_REQUEST['id'])) {
            throw new BadRequestHttpException("Missing id parameter.");
        }

        return new ResourceResponse(Utils::getApplicantInfo($_REQUEST['id']));
    }
}
