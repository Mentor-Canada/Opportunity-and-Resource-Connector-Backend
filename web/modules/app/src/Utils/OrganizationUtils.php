<?php

namespace Drupal\app\Utils;

use Drupal\app\Views\OrganizationSubmittedAdminNotificationView;
use Drupal\app_organization\Organization\OrganizationSubmittedEmailReceiptViewModel;
use Drupal\Core\Access\AccessResult;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

class OrganizationUtils
{
    //  public static function presave(Node $organization) {
//    if(!Security::globalAdministrator()) {
//      if($organization->id()) {
//        $current = Node::load($organization->id());
//        $value = $current->get('field_mtg_enabled')->getValue()[0]['value'];
//        $organization->set('field_mtg_enabled', $value);
//      }
//      else {
//        $organization->set('field_mtg_enabled', 0);
//      }
//    }
    //  }

    public static function access(\Drupal\node\NodeInterface $node, $op, \Drupal\Core\Session\AccountInterface $account)
    {
        if (EntityUtils::isAdmin($node, $account)) {
            return AccessResult::allowed();
        }
        if ($op == 'view') {
            if ($node->get('field_standing')->getValue()[0]['value'] != 'app-allowed') {
                if ($account->isAuthenticated() && Security::globalAdministrator()) {
                    return AccessResult::neutral();
                }
                if (isset($_REQUEST['organizationId']) && $_REQUEST['organizationId'] == $node->uuid()) {
                    return AccessResult::neutral();
                }
            }
        }
        return AccessResult::neutral();
    }

    private static function sendSubmittedReceipt($node, $lang)
    {
        $mailManager = \Drupal::service('plugin.manager.mail');
        $mail = $node->get('field_email')->getValue()[0]['value'];

        $v = new OrganizationSubmittedEmailReceiptViewModel();
        $v->organizationName = $node->get('field_display_title')->getValue()[0]['value'];
        $v->firstName = $node->get('field_first_name')->getValue()[0]['value'];
        $v->lastName = $node->get('field_last_name')->getValue()[0]['value'];
        $v->langcode = $lang;

        $vars = [
            '#theme' => 'organization_submitted_receipt_email_body',
            '#v' => $v
        ];

        $mailManager->mail("app", "organization_submitted", $mail, $lang, [
            'subject' => t("Congratulations! @name has been submitted.", ["@name" => $v->organizationName], ['langcode' => $lang]),
            'body' => $vars
        ]);
    }

    private static function sendSubmittedAdminNotification(Node $node, $langcode)
    {
        $v = new OrganizationSubmittedAdminNotificationView();
        $v->organizationName = $node->get('field_display_title')->getValue()[0]['value'];
        $v->submittedByFirstName = $node->get('field_first_name')->getValue()[0]['value'];
        $v->submittedByLastName = $node->get('field_last_name')->getValue()[0]['value'];
        $v->submittedByMail = $node->get('field_email')->getValue()[0]['value'];
        $v->langcode = 'en';
        $uuid = $node->uuid();
        $v->link = "{$_ENV['CLIENT_URL']}/en/admin/organizations/detail/{$uuid}";

        $admins = self::getSystemAdministrators();
        $mailManager = \Drupal::service('plugin.manager.mail');
        foreach ($admins as $admin) {
            $to = $admin->get('mail')->getValue()[0]['value'];
            $mailManager->mail("app", "organization_admin_notification", $to, "en", [
                'subject' => "{$v->organizationName} has been submitted.",
                'body' => [
                    '#theme' => 'organization_submitted_admin_notification_email_body',
                    '#v' => $v
                ]
            ]);
        }
    }

    public static function getSystemAdministrators(): array
    {
        $ids = \Drupal::entityQuery('user')
      ->condition('field_global_administrator', 1)
      ->execute();
        $result = [];
        foreach ($ids as $id) {
            $result[] = User::load($id);
        }
        return $result;
    }

    public static function loadOrganizationFromPost()
    {
        $postBody = \Drupal::request()->getContent();
        $postData = json_decode($postBody);
        $organizationId = $postData->organizationId;
        $organization = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['uuid' => $organizationId]);
        /* @var $organization \Drupal\node\Entity\Node */
        return current($organization);
    }
}
