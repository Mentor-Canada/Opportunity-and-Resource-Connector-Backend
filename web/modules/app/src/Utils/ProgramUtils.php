<?php

namespace Drupal\app\Utils;

use Drupal;
use Drupal\app\Views\ProgramSubmittedEmailReceiptView;
use Drupal\Core\Access\AccessResult;
use Drupal\user\Entity\User;

class ProgramUtils
{
    public static function access(\Drupal\node\NodeInterface $node, $op, \Drupal\Core\Session\AccountInterface $account)
    {
        if (EntityUtils::isOrganizationAdmin($node)) {
            return AccessResult::allowed();
        }
        if (EntityUtils::isRegionalAdmin($node)) {
            return AccessResult::allowed();
        }
        if (EntityUtils::isAdmin($node, $account)) {
            return AccessResult::allowed();
        }
        return AccessResult::neutral();
    }

    public static function sendSubmittedReceipt($node, $lang)
    {
        $mailManager = Drupal::service('plugin.manager.mail');
        $mail = $node->get('field_email')->getValue()[0]['value'];

        $v = new ProgramSubmittedEmailReceiptView();
        $v->programName = $node->get('field_display_title')->getValue()[0]['value'];
        $v->firstName = $node->get('field_first_name')->getValue()[0]['value'];
        $v->lastName = $node->get('field_last_name')->getValue()[0]['value'];
        $v->langcode = $lang;

        $vars = [
            '#theme' => 'program_submitted_receipt_email_body',
            '#v' => $v
        ];

        $mailManager->mail("app", "program_submitted", $mail, $lang, [
            'subject' => t("Congratulations! @name has been submitted.", ["@name" => $v->programName], ['langcode' => $lang]),
            'body' => $vars
        ]);
    }

    public static function programsForOrganizations($organizations)
    {
        if (!count($organizations)) {
            return [];
        }
        $inOrganizations = implode(',', $organizations);
        $q = "SELECT entity_id FROM node__field_organization_entity
            WHERE field_organization_entity_target_id IN ($inOrganizations)
            AND bundle = 'programs'
            AND langcode = 'en'
            ";
        $database = Drupal::database();
        $query = $database->query($q);
        $result = $query->fetchAll();
        $ids = array_map(function ($a) {
            return $a->entity_id;
        }, $result);
        return $ids;
    }

    public static function programsForRegions($regions)
    {
        if (!count($regions)) {
            return [];
        }
        $regionIds = implode($regions, ",");
        $postalCodeQ = "SELECT field_zips_value FROM node__field_zips WHERE entity_id IN ($regionIds)";
        $q = "SELECT entity_id FROM programs_locations
WHERE TRIM(LEADING '0' FROM postal_code) IN ($postalCodeQ)
    ";
        $ids = Drupal::database()->query($q)->fetchCol(0);
        return $ids;
    }

    public static function programsForUser(): array
    {
        $uid = Drupal::currentUser()->id();
        $account = User::load($uid);
        $globalAdministrator = $account->get('field_global_administrator')->getValue()[0]['value'];
        if ($globalAdministrator == 1) {
            $db = Drupal::database();
            $q = $db->select('node', 'node');
            $q->addField('node', 'nid', 'nid');
            $q->condition('node.type', 'programs');
            return $q->execute()->fetchCol();
        }
        $userOrganizations = EntityUtils::administeredBy($uid, 'organization');
        $userPrograms = ProgramUtils::programsForOrganizations($userOrganizations);
        $userRegions = RegionUtils::getList(Drupal::currentUser()->id());
        $regionPrograms = ProgramUtils::programsForRegions($userRegions);
        $programs = EntityUtils::administeredBy($uid, 'programs');
        return array_merge($userPrograms, $programs, $regionPrograms);
    }
}
