<?php

namespace Drupal\app_organization;

use Drupal\app\App;
use Drupal\app\Controller\EntityAdministratorController;
use Drupal\app\LocalizedString;
use Drupal\app\Mailer;
use Drupal\app\Views\OrganizationSubmittedAdminNotificationView;
use Drupal\app_organization\Organization\OrganizationAddAdminEmailBodyViewModel;
use Drupal\app_organization\Organization\OrganizationApprovedEmailBodyViewModel;
use Drupal\app_organization\Organization\OrganizationSubmittedEmailReceiptViewModel;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use stdClass;

class Organization
{
    private $node;
    public string $nid;
    public LocalizedString $title;

    public ?string $id;
    public ?string $contactFirstName;
    public ?string $contactLastName;
    public ?string $contactEmail;
    public ?stdClass $location;
    public ?string $website;
    public ?string $contactPhone;
    public ?string $contactAlternatePhone;
    public ?string $legalName;
    public ?string $feedback;
    public ?string $type;
    public ?string $typeOther;
    public ?string $taxStatus;
    public ?string $taxStatusOther;
    public ?string $contactPosition;
    public ?string $contactPositionOther;
    public ?string $hasLocation;
    public ?stdClass $description;

    public ?string $mentorCityEnabled;
    public ?string $bbbscEnabled;
    public ?string $mtgEnabled;

    public function __construct()
    {
        $this->title = new LocalizedString();
    }

    public static function createFromNode(Node $node): Organization
    {
        $organization = new Organization();
        $organization->node = $node;
        $organization->nid = $node->id();
        $organization->id = $node->uuid();
        $titleJSON = json_decode(\Drupal::database()->query(
            "SELECT title from organizations WHERE entity_id = :nid",
            [":nid" => $node->id()]
        )->fetchCol()[0]);
        $organization->title->en = $titleJSON->en;
        $organization->title->fr = $titleJSON->fr;
        return $organization;
    }

    public static function createFromUuid(string $uuid): Organization
    {
        $row = \Drupal::database()->query("SELECT * FROM node LEFT JOIN organizations ON node.nid = organizations.entity_id WHERE node.uuid = :uuid", [
            ":uuid" => $uuid
        ])->fetch();

        $organization = new Organization();
        $organization->id = $uuid;
        $organization->nid = $row->nid;
        $title = json_decode($row->title);
        $organization->title->en = $title->en;
        $organization->title->fr = $title->fr;
        $organization->contactFirstName = $row->first_name;
        $organization->contactLastName = $row->last_name;
        $organization->contactEmail = $row->email;
        $organization->location = json_decode($row->location);
        $organization->website = $row->website;
        $organization->contactPhone = $row->phone;
        $organization->contactAlternatePhone = $row->alt_phone;
        $organization->legalName = $row->legal_name;
        $organization->feedback = $row->feedback;
        $organization->type = $row->type;
        $organization->typeOther = $row->other_type;
        $organization->taxStatus = $row->tax_status;
        $organization->taxStatusOther = $row->other_tax_status;
        $organization->contactPosition = $row->position;
        $organization->contactPositionOther = $row->other_position;
        $organization->hasLocation = $row->has_location;
        $organization->description = json_decode($row->description);
        // global admin only.
        $organization->mentorCityEnabled = $row->mentor_city_enabled;
        $organization->mtgEnabled = $row->mtg_enabled;
        $organization->bbbscEnabled = $row->bbbsc_enabled;
        return $organization;
    }

    public static function createFromData(stdClass $data): Organization
    {
        $organization = new Organization();
        $organization->title->en = $data->title->en;
        $organization->title->fr = $data->title->fr;
        $organization->contactFirstName = $data->contactFirstName;
        $organization->contactLastName = $data->contactLastName;
        $organization->contactEmail = $data->contactEmail;
        $organization->location = $data->location;
        $organization->website = $data->website;
        $organization->contactPhone = $data->contactPhone;
        $organization->contactAlternatePhone = $data->contactAlternatePhone;
        $organization->legalName = $data->legalName;
        $organization->feedback = $data->feedback;
        $organization->type = $data->type;
        $organization->typeOther = $data->typeOther;
        $organization->taxStatus = $data->taxStatus;
        $organization->taxStatusOther = $data->taxStatusOther;
        $organization->contactPosition = $data->contactPosition;
        $organization->contactPositionOther = $data->contactPositionOther;
        $organization->hasLocation = $data->hasLocation;
        $organization->description = $data->description;
        // global admin only.
        $organization->mentorCityEnabled = $data->mentor_city_enabled;
        $organization->mtgEnabled = $data->mtgEnabled;
        $organization->bbbscEnabled = $data->bbbsc_enabled;
        return $organization;
    }

    public function save()
    {
        $fields = [
            "entity_id" => $this->nid,
            "title" => json_encode($this->title),
            "first_name" => $this->contactFirstName,
            "last_name" => $this->contactLastName,
            "email" => $this->contactEmail,
            'location' => $this->hasLocation === 'yes' ? json_encode($this->location) : null,
            'website' => $this->website,
            'phone' => $this->contactPhone,
            'alt_phone' => $this->contactAlternatePhone,
            'legal_name' => $this->legalName,
            'feedback' => $this->feedback,
            "type" => $this->type,
            "other_type" => $this->type == 'other' ? $this->typeOther : '',
            "tax_status" => $this->taxStatus,
            "other_tax_status" => $this->taxStatus == 'other' ? $this->taxStatusOther : '',
            "position" => $this->contactPosition,
            "other_position" => $this->contactPosition == 'other' ? $this->contactPositionOther : '',
            "has_location" => $this->hasLocation === 'yes' ? '1' : '0',
            "description" => json_encode($this->description),
        ];
        \Drupal::database()
      ->upsert("organizations")
      ->key("entity_id", $this->nid)
      ->fields($fields)
      ->execute();
    }

    public function saveIntegrations()
    {
        $fields = [
            "entity_id" => $this->nid,
            "mentor_city_enabled" => $this->mentorCityEnabled == '1' ? '1' : '0',
            'mtg_enabled' => $this->mtgEnabled,
            "bbbsc_enabled" => $this->bbbscEnabled  == '1' ? '1' : '0',
        ];
        \Drupal::database()
      ->upsert("organizations")
      ->key("entity_id", $this->nid)
      ->fields($fields)
      ->execute();
    }

    public function onInsert($uilang)
    {
        if (!$uilang) {
            $uilang = 'en';
        }
        $this->sendSubmittedReceipt($uilang);
        $this->sendSubmittedAdminNotification();
        $response = EntityAdministratorController::addAdministrator(
            $this->id,
            $this->contactEmail,
            $this->contactFirstName,
            $this->contactLastName
        );
        $mail = $response['mail'];
        $accountIsNew = $response['accountIsNew'];
        $this->sendNewAdministratorNotification($uilang, $mail, $accountIsNew);
    }

    private function sendSubmittedReceipt($uilang)
    {
        $v = new OrganizationSubmittedEmailReceiptViewModel();
        $v->organizationName = $this->title->en;
        $v->firstName = $this->contactFirstName;
        $v->lastName = $this->contactLastName;
        $v->langcode = $uilang;

        $vars = [
            '#theme' => 'organization_submitted_receipt_email_body',
            '#v' => $v
        ];

        $mailManager = \Drupal::service('plugin.manager.mail');
        $mailManager->mail("app", "organization_submitted", $this->contactEmail, $uilang, [
            'subject' => t("Congratulations! @name has been submitted.", ["@name" => $this->title->en], ['langcode' => $uilang]),
            'body' => $vars
        ]);
    }

    private function sendSubmittedAdminNotification()
    {
        $v = new OrganizationSubmittedAdminNotificationView();
        $v->organizationName = $this->title->en;
        $v->submittedByFirstName = $this->contactFirstName;
        $v->submittedByLastName = $this->contactLastName;
        $v->submittedByMail = $this->contactEmail;
        $v->langcode = 'en';
        $v->link = "{$_ENV['CLIENT_URL']}/en/admin/organizations/detail/{$this->id}";

        $admins = $this->getSystemAdministrators();
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

    public function getSystemAdministrators(): array
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

    public function delete()
    {
        \Drupal::database()->query("DELETE FROM organizations WHERE entity_id = :entity_id", [
            ":entity_id" => $this->nid
        ]);
        $this->node = \Drupal::service('entity.repository')->loadEntityByUuid('node', $this->id);
        $this->node->delete();
    }

    public function sendApprovedNotification($lang)
    {
        $emails = $this->getAdministrators();

        $vm = new OrganizationApprovedEmailBodyViewModel();
        $vm->langcode = $lang;
        $vm->organizationLink = $this->detailUrl($lang);
        $vm->programLink = $this->addProgramsUrl($lang);

        foreach ($emails as $email) {
            (new Mailer())
        ->lang($lang)
        ->email($email)
        ->subject(t("app-organization-approved-email-subject", [
            '@title' => $this->title->get($lang)
        ], ['langcode' => $lang]))
        ->viewModel($vm)
        ->template("organization_approved_email_body")
        ->mail()
      ;
        }
    }

    public function sendPausedNotification()
    {
        $emails = $this->getAdministrators();
        $lang = App::getInstance()->uilang;

        $vm = new OrganizationSubmittedEmailReceiptViewModel();
        $vm->langcode = $lang;
        $vm->organizationName = $this->title->get(App::getInstance()->uilang);

        foreach ($emails as $email) {
            (new Mailer())
        ->lang($lang)
        ->email($email)
        ->subject(t("app-organization-approved-email-subject", [
            '@title' => $this->title->get($lang)
        ], ['langcode' => $lang]))
        ->template("organization_paused_email_body")
        ->viewModel($vm)
        ->mail()
      ;
        }
    }

    public function sendNewAdministratorNotification($lang, $mail, $accountIsNew)
    {
        $vm = new OrganizationAddAdminEmailBodyViewModel();
        $vm->langcode = $lang;

        if ($accountIsNew) {
            $template = "organization_add_admin_new_account_email_body";
            $account = user_load_by_mail($mail);
            $otp = rest_password_temp_pass_token($account);
            $registerUrl = "{$_ENV['CLIENT_URL']}/$lang/complete-registration?email={$account->mail->value}&otp={$otp}";
            $detailUrl = "$registerUrl&dest={$this->detailUrl($lang)}";
            $addProgramsUrl = "$registerUrl&dest={$this->addProgramsUrl($lang)}";

            $vm->title = $this->title->get($lang);
            $vm->organizationLink = $detailUrl;
            $vm->programLink = $addProgramsUrl;
        } else {
            $template = "organization_add_admin_existing_account_email_body";
            $vm->title = $this->title->get($lang);
            $vm->organizationLink = $this->detailUrl($lang);
            $vm->programLink = $this->addProgramsUrl($lang);
        }

        (new Mailer())
      ->lang($lang)
      ->email($mail)
      ->subject(t("'@title' Organization Administration", [
          '@title' => $this->title->get($lang)
      ], ['langcode' => $lang]))
      ->viewModel($vm)
      ->template($template)
      ->mail()
    ;
    }

    private function getAdministrators()
    {
        return \Drupal::database()->query("SELECT DISTINCT(mail) FROM node__field_administrators
LEFT JOIN users_field_data ON field_administrators_target_id = users_field_data.uid
WHERE uid is not null AND entity_id = :id;", [
            ":id" => $this->id
        ])->fetchCol();
    }

    private function detailUrl($lang): string
    {
        return "{$_ENV['CLIENT_URL']}/$lang/admin/organizations/detail/{$this->id}";
    }

    private function addProgramsUrl($lang): string
    {
        return "{$_ENV['CLIENT_URL']}/$lang/programs/add/step/1?organization={$this->id}";
    }
}
