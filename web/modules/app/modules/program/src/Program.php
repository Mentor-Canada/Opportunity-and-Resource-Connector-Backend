<?php

namespace Drupal\app_program;

use Drupal;
use Drupal\app\Affiliate;
use Drupal\app\Controller\EntityAdministratorController;
use Drupal\app\LocalizedString;
use Drupal\app\Mailer;
use Drupal\app\Utils\UserUtils;
use Drupal\app\Views\ProgramSubmittedAdminNotificationView;
use Drupal\app\Views\ProgramSubmittedEmailReceiptView;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

class Program
{
    public const SUSPENDED = 'app-suspended';

    public $id;
    /* @var $node Drupal\node\Entity\Node */
    private $node;
    private $uilang;

    private ?string $firstName;
    private ?string $lastName;
    private ?string $email;
    public LocalizedString $title;
    private ?string $standing;

    private bool $backgroundCheck;
    private bool $training;
    private bool $commitment;
    private int $responsiveness;

    public function __construct()
    {
        $this->title = new LocalizedString();
    }

    public static function createWithNid($nid, $uilang): Program
    {
        $program = new Program();
        $program->node = Node::load($nid);
        $program->loadNodeAttributes();
        $program->uilang = $uilang;
        $program->loadAdditionalAttributes();
        return $program;
    }

    private function loadNodeAttributes()
    {
        $this->standing = $this->node->get('field_standing')->getValue()[0]['value'];
    }

    private function loadAdditionalAttributes()
    {
        $additionalAttributes = \Drupal::database()->query("SELECT first_name, last_name, email, title FROM programs WHERE entity_id = :entity_id;", [
            ":entity_id" => $this->node->id()
        ])->fetchObject();
        $this->firstName = $additionalAttributes->first_name;
        $this->lastName = $additionalAttributes->last_name;
        $this->email = $additionalAttributes->email;
        $title = json_decode($additionalAttributes->title);
        $this->title->en = $title->en;
        $this->title->fr = $title->fr;
    }

    public function setStanding()
    {
        $this->backgroundCheck = $this->node->get('field_ns_bg_check')->getValue()[0]['value'] == 'app-yes';
        $this->training = $this->node->get('field_ns_training')->getValue()[0]['value'] == 'app-yes';
        $this->commitment = $this->node->get('field_program_mentor_month_commi')->getValue()[0]['value'] != 'app-no-minimum-match-commitment';

        if (!$this->backgroundCheck || !$this->training || !$this->commitment) {
            $this->standing = self::SUSPENDED;
            $this->node->set('field_standing', $this->standing);
        }
    }

    public function sendNotifications()
    {
        $v = new ProgramSubmittedEmailReceiptView();
        $v->programName = $this->title->get(Drupal\app\App::getInstance()->uilang);
        $v->firstName = $this->firstName;
        $v->lastName = $this->lastName;
        $v->langcode = $this->uilang;

        $mailer = new Mailer();
        $mailer->viewModel($v)
      ->email($this->email)
      ->lang($this->uilang);

        $affiliate = Affiliate::createWithProgramId($this->node->id());
        if ($affiliate) {
            $v->affiliateName = $affiliate->name;
        }

        if ($this->standing != self::SUSPENDED) {
            $template = $_ENV['COUNTRY'] == 'ca' ? 'program_submitted_receipt_email_body_ca' : 'program_submitted_receipt_email_body';
            $mailer->template($template);
            $mailer->subject(t("Congratulations! @name has been submitted.", ["@name" => $v->programName], ['langcode' => $this->uilang]));
        } else {
            if ($_ENV['COUNTRY'] != 'ca') {
                $mailer->addFile(DRUPAL_ROOT . "/assets/3 National Standards Infographic.pdf", '3 National Standards Infographic.pdf', 'application/pdf');
            }
            $mailer->subject(t("Mentor Connector Application Under Review", [], ['langcode' => $this->uilang]));
            $template = $_ENV['COUNTRY'] == 'ca' ? 'program_suspended_email_body_ca' : 'program_suspended_email_body';
            $mailer->template($template);
            if (!$this->backgroundCheck && !$this->training && !$this->commitment) {
                $v->customTextA = "conduct background checks on mentors, provide training to mentors, or have a minimum match commitment between mentors and mentees";
                $v->customTextB = "meet all 3 National Standards listed above";
            } elseif (!$this->backgroundCheck && !$this->training) {
                $v->customTextA = "conduct background checks on mentors and does not provide training for them";
                $v->customTextB = "integrate background checks into your Program’s policies and design and implement training procedures for mentors";
            } elseif (!$this->training && !$this->commitment) {
                $v->customTextA = "provide training to new mentors and does not have a minimum match commitment between mentors and mentees";
                $v->customTextB = "design and implement training procedures for mentors and set a minimum match commitment between mentors and mentees";
            } elseif (!$this->commitment && !$this->backgroundCheck) {
                $v->customTextA = "have a minimum match commitment between mentors and mentees and does not conduct background checks on mentors";
                $v->customTextB = "set a minimum match commitment between mentors and mentees integrate background checks into your Program’s policies";
            } elseif (!$this->backgroundCheck) {
                $v->customTextA = "conduct background checks on mentors";
                $v->customTextB = "integrate background checks into your Program’s policies";
            } elseif (!$this->training) {
                $v->customTextA = "conduct training on new mentors";
                $v->customTextB = "design and implement training procedures for mentors";
            } elseif (!$this->commitment) {
                $v->customTextA = "have a minimum match commitment between mentors and mentees";
                $v->customTextB = "set a minimum match commitment between mentors and mentees";
            }
        }

        $mailer->mail();
    }

    public function computeResponsiveness($allTime = false)
    {
        $q = \Drupal::database()->select("inquiries");
        if (!$allTime) {
            $lastWeek = \Drupal::database()->query("SELECT UNIX_TIMESTAMP() - (7 * 24 * 60 * 60)")->fetchField();
            $q->condition("created", $lastWeek, ">=");
        }
        $q->condition("programId", $this->id);
        $totalInquiries = $q->countQuery()->execute()->fetchField();

        /**
         * Place programs without any inquiries in the last 7 days in Tier 1
         */
        if ($totalInquiries == 0) {
            $this->responsiveness = 1;
            return;
        }
        $q->condition('status', 'app-contacted');
        $contactedInquiries = $q->countQuery()->execute()->fetchField();
        $ratio = $contactedInquiries / $totalInquiries;
        if ($ratio > 0.9) {
            $this->responsiveness = 1;
            return;
        }
        if ($ratio > 0.75) {
            $this->responsiveness = 2;
            return;
        }
        if ($ratio > 0.60) {
            $this->responsiveness = 3;
            return;
        }
        $this->responsiveness = 4;
    }

    public function saveResponsiveness()
    {
        Drupal::database()->query("UPDATE programs SET responsivenessTier = :responsivenessTier WHERE entity_id = :entity_id", [
            ':responsivenessTier' => $this->responsiveness,
            ':entity_id' => $this->id
        ]);
    }

    public function setInitialAdministrator()
    {
        $uuid = $this->node->get('uuid')->getValue()[0]['value'];
        EntityAdministratorController::post($uuid, $this->email, $this->firstName, $this->lastName);
    }

    public function save()
    {
        $this->node->save();
    }

    public function sendApprovalNotification($lang)
    {
        $emails = $this->getAdministratorEmails();

        $vm = new ProgramApprovedEmailViewModel();
        $vm->title =  $this->title->get(Drupal\app\App::getInstance()->uilang);
        $vm->detailUrl = "{$_ENV['CLIENT_URL']}/$lang/admin/programs/detail/{$this->node->uuid()}";
        $vm->lang = $lang;

        foreach ($emails as $email) {
            (new Mailer())
        ->email($email)
        ->lang($lang)
        ->subject(t("app-email-program-approved-subject", [], ['langcode' => $lang]))
        ->template('program_approved_email_body')
        ->viewModel($vm)
        ->mail()
      ;
        }
    }

    public function sendPausedNotification()
    {
        $v = new ProgramSubmittedEmailReceiptView();
        $v->programName = $this->title->get(Drupal\app\App::getInstance()->uilang);
        $v->firstName = $this->firstName;
        $v->lastName = $this->lastName;
        $v->langcode = $this->uilang;

        $emails = $this->getAdministratorEmails();

        foreach ($emails as $email) {
            (new Mailer())
        ->email($email)
        ->subject(t("Mentor Connector Application Under Review", [], ['langcode' => $this->uilang]))
        ->template("program_suspended_email_body_ca")
        ->viewModel($v)
        ->lang($this->uilang)
        ->mail()
      ;
        }
    }

    private function getAdministratorEmails()
    {
        $id = $this->node->id();
        return \Drupal::database()->query("SELECT DISTINCT(mail) FROM node__field_administrators
LEFT JOIN users_field_data ON field_administrators_target_id = users_field_data.uid
WHERE uid is not null AND entity_id = :id;", [
            ":id" => $id
        ])->fetchCol();
    }

    public function sendAdminNotifications()
    {
        $v = new ProgramSubmittedAdminNotificationView();
        $v->programName = $this->title->get(Drupal\app\App::getInstance()->uilang);
        $v->submittedByFirstName = $this->firstName;
        $v->submittedByLastName = $this->lastName;
        $v->submittedByMail = $this->email;
        $v->langcode = $this->uilang;
        $uuid = $this->node->uuid();
        $v->link = "{$_ENV['CLIENT_URL']}/en/admin/programs/detail/{$uuid}";

        $affiliate = Affiliate::createWithProgramId($this->node->id());
        $admins = [];
        if ($affiliate) {
            $admins = $affiliate->getAdministrators();
        }
        if (!count($admins)) {
            $globalAdministratorUids = UserUtils::getGlobalAdministratorUids();
            $admins = User::loadMultiple($globalAdministratorUids);
        }

        $mailManager = \Drupal::service('plugin.manager.mail');
        foreach ($admins as $admin) {
            $to = $admin->get('mail')->getValue()[0]['value'];
            $mailManager->mail("app", "program_admin_notification", $to, "en", [
                'subject' => "{$v->programName} has been submitted.",
                'body' => [
                    '#theme' => 'program_submitted_admin_notification_email_body',
                    '#v' => $v
                ]
            ]);
        }
    }
}
