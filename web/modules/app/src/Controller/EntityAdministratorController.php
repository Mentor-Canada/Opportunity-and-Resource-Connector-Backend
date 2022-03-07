<?php

namespace Drupal\app\Controller;

use Drupal\app\App;
use Drupal\app\Factories\NodeFactory;
use Drupal\app\Utils\UserUtils;
use Drupal\app\Views\SetProgramAdministratorEmailView;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class EntityAdministratorController
{
    public static function post($uuid, $mail, $firstName = null, $lastName = null, $notify = true)
    {
        $result = self::addAdministrator($uuid, $mail, $firstName, $lastName);
        $node = $result['node'];
        $accountIsNew = $result['accountIsNew'];

        $postData = \Drupal::request()->request->all();
        $postBody = json_decode($postData['entityData']);
        $lang = isset($postBody->uilang) ? $postBody->uilang : 'en';
        $type = $node->getType();
        if ($type == "programs" && $notify) {
            self::sendAddProgramAdministratorEmail($mail, $node, $accountIsNew, $lang);
        }

        return new JsonResponse(["status" => "user added"]);
    }

    public static function addAdministrator($uuid, $mail, $firstName = null, $lastName = null): array
    {
        $users = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->loadByProperties([
          'mail' => $mail,
      ]);

        $accountIsNew = false;

        if (!count($users)) {
            $user = User::create([
                'name' => $mail,
                'mail' => $mail,
                'field_first_name' => $firstName,
                'field_last_name' => $lastName
            ]);
            $user->set('status', 1);
            $user->save();
            $accountIsNew = true;
        } else {
            $user = current($users);
        }

        $node = NodeFactory::abstractFactory($uuid)->node;

        $exists = array_filter($node->get('field_administrators')->getValue(), function ($row) use ($user) {
            return $row['target_id'] == $user->id();
        });
        if (count($exists)) {
            return [ "exists" => true ];
        }

        $node->get('field_administrators')->appendItem(['target_id' => $user->id()]);
        $node->save();

        return [
            "node" => $node,
            "accountIsNew" => $accountIsNew,
            "mail" => $mail
        ];
    }

    public static function delete($uuid, $mail)
    {
        $user = UserUtils::loadByMail($mail);
        if (!$user) {
            return new JsonResponse("Invalid User", Response::HTTP_BAD_REQUEST);
        }

        $node = NodeFactory::abstractFactory($uuid)->node;

        $administrators = $node->get('field_administrators')->getValue();
        foreach ($administrators as $key => $row) {
            if ($row['target_id'] == $user->id()) {
                $node->get('field_administrators')->removeItem($key);
                break;
            }
        }
        $node->save();
        return new JsonResponse(["status" => "user removed"]);
    }

    public static function sendAddProgramAdministratorEmail($mail, $program, $accountIsNew)
    {
        $lang = App::getInstance()->uilang;

        $mailManager = \Drupal::service('plugin.manager.mail');

        $account = user_load_by_mail($mail);
        $v = new SetProgramAdministratorEmailView();
        $v->otp = rest_password_temp_pass_token($account);
        $v->account = $account;
        $v->accountIsNew = $accountIsNew;

        $v->programTitle = \Drupal::database()->query("SELECT JSON_UNQUOTE(JSON_EXTRACT(title, '$.en')) FROM programs WHERE entity_id = :id", [':id' => $program->id()])->fetchField();
        $v->programLink = "admin/programs/detail/{$program->uuid()}";
        $v->approved = $program->get('field_standing')->getValue()[0]['value'] == "app-allowed";

        $v->langcode = $lang;

        $template = $_ENV['COUNTRY'] == 'us' ? 'set_program_administrator_email_us' : 'set_program_administrator_email';

        $vars = [
            '#theme' => $template,
            '#v' => $v
        ];

        $mailManager->mail("app", "program_admin_added", $mail, $lang, [
            'subject' => t("'@title' Program Administration", ['@title' => $v->programTitle], ['langcode' => $lang]),
            'body' => $vars
        ]);
    }
}
