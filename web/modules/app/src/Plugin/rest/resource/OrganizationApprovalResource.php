<?php

namespace Drupal\app\Plugin\rest\resource;

use Drupal\app\Decorators\OrganizationDecorator;
use Drupal\app\Factories\NodeFactory;
use Drupal\rest\Annotation\RestResource;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @RestResource(
 *   id = "organization_approval_resource",
 *   label = @Translation("Organization Approval Resource"),
 *   uri_paths = {
 *     "canonical" = "/a/utils/organization/approval"
 *   }
 * )
 */
class OrganizationApprovalResource extends ResourceBase
{
    private $organizationId;
    private $directors = [];

    private function checkStatus($status)
    {
        $q = \Drupal::database()->select('node__field_status', 'status');
        $q->leftJoin('node__field_approval_entity', 'approval_entity', 'approval_entity.entity_id = status.entity_id');
        $q->leftJoin('node__field_user_entity', 'user_entity', 'user_entity.entity_id = approval_entity.entity_id');
        $q->addField('status', 'field_status_value');
        $q->condition('user_entity.field_user_entity_target_id', $this->directors, 'IN');
        $q->condition('status.bundle', 'approval');
        $q->condition('status.field_status_value', $status);
        $q->condition('approval_entity.field_approval_entity_target_id', $this->organizationId);
        return $q->countQuery()->execute()->fetchField() > 0;
    }

    public function get()
    {
        if (!isset($_REQUEST['organizationUuid'])) {
            throw new BadRequestHttpException("Missing required organizationUuid value.");
        }

        $uuid = $_REQUEST['organizationUuid'];

        $organization = NodeFactory::factory(OrganizationDecorator::JSONAPIType, $uuid);
        $this->organizationId = $organization->node->id();
        $this->directors = $organization->getDirectorUids();

        $status = '';

        $isAllowed = $this->checkStatus("app-allowed");
        if ($isAllowed) {
            $status = 'app-allowed';
        }
        $isDenied = $this->checkStatus("app-denied");
        if ($isDenied) {
            $status = 'app-denied';
        }

        $organization->node->set('field_standing', $status);
        $organization->node->save();

        $response = ['data' => ['status' => $status]];
        return new ResourceResponse($response);
    }
}
