<?php

namespace Drupal\app\Controller;

use Drupal\app\App;
use Drupal\app\Decorators\DirectedFactoryBase;
use Drupal\app\Factories\NodeFactory;
use Drupal\app\Utils\EntityUtils;
use Drupal\app\Utils\RequestUtils;
use Drupal\app\Utils\Utils;
use Drupal\app_organization\Organization;
use Drupal\app_program\Program;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ApprovalController extends Controller
{
    private $requestContent;
    private User $account;
    private DirectedFactoryBase $entity;
    private $directors = [];

    private $initialStatus;
    private $newStatus;

    public function __construct($http_kernel)
    {
        parent::__construct($http_kernel);
        $request = \Drupal::request();

        if ($request->getMethod() != 'DELETE') {
            $this->requestContent = RequestUtils::postData();
            $entityType = $this->requestContent->data->relationships->field_approval_entity->data[0]->type;
            $entityUuid = $this->requestContent->data->relationships->field_approval_entity->data[0]->id;
            $this->entity = NodeFactory::factory($entityType, $entityUuid);
        } else {
            $path = $request->getPathInfo();
            $path = explode('/', $path);
            $uuid = array_pop($path);
            $approvalNode = Utils::loadNodeByUUid($uuid);
            $entityId = $approvalNode->get('field_approval_entity')->getValue()[0]['target_id'];
            $entityNode = Node::load($entityId);
            $this->entity = NodeFactory::withNode($entityNode);
        }

        $this->initialStatus = $this->entity->node->get('field_standing')->getValue()[0]['value'];

        /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->account = User::load(\Drupal::currentUser()->id());

        if ($this->account->get('field_global_administrator')->getValue()[0]['value'] == '1') {
            return;
        }
        $type = $this->entity->node->getType();
        if ($type == 'programs') {
            if (EntityUtils::isRegionalAdmin($this->entity->node)) {
                return;
            }
            if (EntityUtils::isRegionalAdmin($this->entity->node)) {
                return;
            }
        }

        throw new AccessDeniedHttpException();
    }

    private function afterUpdate()
    {
        $uilang = App::getInstance()->uilang;
        if ($this->newStatus != $this->initialStatus) {
            if ($this->newStatus == "app-allowed") {
                $type = $this->entity->node->getType();
                if ($type == 'programs') {
                    $programId = $this->entity->node->id();
                    $program = Program::createWithNid($programId, $uilang);
                    $program->sendApprovalNotification($uilang);
                }
                if ($type == 'organization') {
                    $organization = Organization::createFromNode($this->entity->node);
                    $organization->sendApprovedNotification($uilang);
                }
            }

            if ($this->newStatus == "app-suspended") {
                $type = $this->entity->node->getType();
                if ($type == 'programs') {
                    $programId = $this->entity->node->id();
                    $program = Program::createWithNid($programId, $uilang);
                    $program->sendPausedNotification();
                }
                if ($type == 'organization') {
                    $organization = Organization::createFromNode($this->entity->node);
                    $organization->sendPausedNotification($uilang);
                }
            }
        }
    }

    public function post()
    {
        $this->requestContent->data->relationships->field_user_entity->data[0] = (object) [
            'type' => 'user--user',
            'id' => $this->account->uuid()
        ];

        $subResponse = $this->requestFactory("/a/node/approval")
      ->body($this->requestContent)
      ->execute();

        $this->updateStatus();
        $this->afterUpdate();

        return $subResponse;
    }

    public function patch($id): Response
    {
        unset($this->requestContent->data->relationships);

        $subResponse = $this->requestFactory("/a/node/approval/$id")
      ->method('PATCH')
      ->body($this->requestContent)
      ->execute();

        $this->updateStatus();
        $this->afterUpdate();

        return $subResponse;
    }

    public function delete($uuid)
    {
        $subResponse = $this->requestFactory("/a/node/approval/$uuid")
      ->method('DELETE')
      ->execute();

        $this->updateStatus();
        $this->afterUpdate();

        return $subResponse;
    }

    public function updateStatus()
    {
        $this->directors = $this->entity->getDirectorUids();
        $status = '';

        $isDenied = $this->checkStatus("app-denied");
        if ($isDenied) {
            $status = 'app-denied';
        }

        if (!$isDenied) {
            $isPaused = $this->checkStatus("app-suspended");
            if ($isPaused) {
                $status = 'app-suspended';
            }
        }

        if (!$isDenied && !$isPaused) {
            $isAllowed = $this->checkStatus("app-allowed");
            if ($isAllowed) {
                $status = 'app-allowed';
            }
        }

        $this->newStatus = $status;
        $this->entity->node->set('field_standing', $status);
        $this->entity->node->save();
    }

    private function checkStatus($status)
    {
        $q = \Drupal::database()->select('node__field_status', 'status');
        $q->leftJoin('node__field_approval_entity', 'approval_entity', 'approval_entity.entity_id = status.entity_id');
        $q->leftJoin('node__field_user_entity', 'user_entity', 'user_entity.entity_id = approval_entity.entity_id');
        $q->addField('status', 'field_status_value');
        $q->condition('user_entity.field_user_entity_target_id', $this->directors, 'IN');
        $q->condition('status.bundle', 'approval');
        $q->condition('status.field_status_value', $status);
        $q->condition('approval_entity.field_approval_entity_target_id', $this->entity->node->id());
        return $q->countQuery()->execute()->fetchField() > 0;
    }
}
