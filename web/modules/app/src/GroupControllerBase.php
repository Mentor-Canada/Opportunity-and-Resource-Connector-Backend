<?php

namespace Drupal\app;

use Drupal\app\Builders\RequestBuilder;
use Drupal\app\Controller\BaseController;
use Drupal\app\Factories\NodeFactory;
use Drupal\app\Utils\Utils;
use Drupal\Core\Access\AccessResult;
use Drupal\user\Entity\User;

abstract class GroupControllerBase extends BaseController
{
    private $files;
    protected $content;
    private $path;
    protected $nid;
    protected $node;
    protected $uuid;

    public function groupAdministratorAccess($uuid)
    {
        $entityDecorator = NodeFactory::abstractFactory($uuid);
        $user = \Drupal::currentUser()->getAccount();
        if ($user->id()) {
            $account = User::load($user->id());
            $entityAdministrator = $entityDecorator->isAdministrator($account);
            if ($entityAdministrator) {
                return AccessResult::allowed();
            }
        }
        return AccessResult::forbidden();
    }

    public function common()
    {
        $this->files = file_save_upload('logo', [], 'public://');
        $this->content = $this->getContent();
        $this->path = $this->getPath();
    }

    public function post()
    {
        $this->common();

        \Drupal::configFactory()->getEditable('system.site')->set('default_langcode', 'en')->save();

        $request = new RequestBuilder($this->httpKernel, "/$this->path");
        $jsonApiBodyEn = $this->formatJsonApiBody($this->content->nodes->en);
        $response = $request->body($jsonApiBodyEn)
      ->execute()
    ;
        unset($this->content->nodes->en);
        $responseContent = json_decode($response->getContent());
        $this->uuid = $responseContent->data->id;
        $this->nid = $responseContent->data->attributes->drupal_internal__nid;
        $entityType = explode('--', $responseContent->data->type)[1];
        if ($entityType == 'organization') {
            $entityType .= 's';
        }
        $fields = [
            ':entity_id' => $this->nid
        ];
        \Drupal::database()
      ->insert($entityType)
      ->fields($fields)
      ->execute()
    ;

        $result = $this->patch($this->uuid);
        return $result;
    }

    public function patch($uuid)
    {
        $this->common();

        $this->node = Utils::loadNodeByUUid($uuid);
        $this->nid = $this->node->id();
        if ($this->files) {
            $file = $this->files[0];
            $this->node->set('field_logo', $file);
            $this->node->save();
        } elseif (isset($_POST['clear_logo']) && $_POST['clear_logo'] == '1') {
            $this->node->set('field_logo', null);
            $this->node->save();
        }

        $translated = array_keys($this->node->getTranslationLanguages());

        foreach ($this->content->nodes as $key => $translation) {
            \Drupal::configFactory()->getEditable('system.site')->set('default_langcode', $key)->save();
            if (!in_array($key, $translated)) {
                $newTranslation = $this->node->addTranslation($key);
                $newTranslation->setTitle($this->node->getTitle());
                $this->node->save();
            }
            $translationRequest = new RequestBuilder($this->httpKernel, "/$this->path/$uuid");
            $translationContent = $this->formatJsonApiBody($translation);
            $translationContent->data->id = $uuid;
            $translationResponse = $translationRequest->body($translationContent)
        ->method("PATCH")
        ->execute()
      ;
            if ($key == 'en') {
                $response = $translationResponse;
            }
        }
        \Drupal::configFactory()->getEditable('system.site')->set('default_langcode', 'en')->save();

        return $response;
    }

    private function formatJsonApiBody($data)
    {
        return (object) [
            'data' => $data
        ];
    }

    protected function getContent()
    {
        $content = $_POST['entityData'] ;
        $content = json_decode($content);
        return $content;
    }

    private function getPath()
    {
        $content = $this->getContent();
        $type = explode("--", $content->nodes->en->type);
        $type = array_pop($type);
        $path = "a/node/{$type}";
        return $path;
    }
}
