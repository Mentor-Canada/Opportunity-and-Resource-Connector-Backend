<?php

namespace Drupal\app\Utils;

use Drupal\app\Views\ApplicationSubmittedAdminNotificationView;
use Drupal\app\Views\ApplicationSubmittedEmailReceiptView;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

class ApplicationUtils
{
    public static function sendSubmittedReceipt($adapter)
    {
        $mailManager = \Drupal::service('plugin.manager.mail');

        $v = new ApplicationSubmittedEmailReceiptView();
        $v->programTitle = $adapter->programTitle;
        $v->applicantFirstName = $adapter->firstName;
        $v->applicantLastName = $adapter->lastName;
        $v->applicantRole = strtolower(t(strtolower($adapter->role), [], ["langcode" => $adapter->uilang])->__toString());
        $v->langcode = $adapter->uilang;
        $v->baseUrl = $GLOBALS['base_url'];

        if ($adapter->role == 'mentor') {
            $theme = "application_submitted_receipt_email_body_mentor_" . $adapter->uilang;
            if ($_ENV['COUNTRY'] == 'us') {
                $theme = "application_submitted_receipt_email_body_mentor_en_us";
            }
            $vars = [
                '#theme' => $theme,
                '#v' => $v
            ];
        } else {
            $vars = [
                '#theme' => 'application_submitted_receipt_email_body',
                '#v' => $v
            ];
        }

        $mailManager->mail("app", "application", $adapter->email, $adapter->uilang, [
            'subject' => t("Thank you for contacting @programTitle", ["@programTitle" => $v->programTitle], ['langcode' => $adapter->uilang]),
            'body' => $vars
        ]);
    }

    public static function sendSubmittedAdminNotification($adapter)
    {
        $mailManager = \Drupal::service('plugin.manager.mail');

        $replyTo = "{$adapter->uuid}@{$_ENV['RELAY_HOST']}";

        $v = new ApplicationSubmittedAdminNotificationView();
        $v->programTitle = $adapter->programTitle;
        $v->applicantFirstName = $adapter->firstName;
        $v->applicantLastName = $adapter->lastName;
        $v->applicantRole = strtolower(t(strtolower($adapter->role), [], ["langcode" => $adapter->uilang])->__toString());
        $v->replyTo = $replyTo;
        $v->langcode = $adapter->uilang;

        $vars = [
            '#theme' => 'application_submitted_admin_notification_email_body',
            '#v' => $v
        ];

        $program = Node::load($adapter->programId);
        $administrators = self::getNotificationCollection($program);
        foreach ($administrators as $row) {
            $account = User::load($row);
            if (!$account) {
                continue;
            }
            $mail = $account->get('mail')->getValue()[0]['value'];
            $hasAppliedTo = t('app-has-applied-to', [], ['langcode' => $adapter->uilang])->__toString();
            $mailManager->mail("app", "application", $mail, "en", [
                'subject' => "{$adapter->firstName} {$adapter->lastName} {$hasAppliedTo} {$adapter->programTitle}",
                'body' => $vars,
                'reply-to' => $replyTo
            ]);
        }
    }

    public static function getNotificationCollection($program): array
    {
        $collection = [];
        $administrators = $program->get('field_administrators')->getValue();
        foreach ($administrators as $row) {
            $collection[] = $row['target_id'];
        }
        if (!count($collection)) {
            $organizationId = $program->get('field_organization_entity')->getValue()[0]['target_id'];
            $organizationNode = Node::load($organizationId);
            if ($organizationNode) {
                $organizationAdministrators = $organizationNode->get('field_administrators')->getValue();
                foreach ($organizationAdministrators as $row) {
                    $collection[] = $row['target_id'];
                }
            }
        }
        if (!count($collection)) {
            $code = $program->get('field_physical_locations')->getValue()[0]['postal_code'];
            $code = ltrim($code, '0');

            $q = \Drupal::database()->select('node__field_zips', 't');
            $q->addField('t', 'entity_id');
            $q->condition('bundle', 'region');
            $q->condition('field_zips_value', $code);
            $regionIds = $q->execute()->fetchCol();

            if (count($regionIds)) {
                $q = \Drupal::database()->select('node', 'node');
                $q->addField('administrators', 'field_administrators_target_id', 'uid');
                $q->leftJoin('node__field_administrators', 'administrators', 'administrators.entity_id = node.nid');
                $q->condition('node.type', 'region');
                $q->condition('node.nid', $regionIds, 'IN');
                $rows = $q->execute()->fetchAll();
                foreach ($rows as $row) {
                    $collection[] = $row->uid;
                }
            }
        }
        if (!count($collection)) {
            $collection = UserUtils::getGlobalAdministratorUids();
        }
        return $collection;
    }
}
