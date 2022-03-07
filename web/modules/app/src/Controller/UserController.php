<?php

namespace Drupal\app\Controller;

use Drupal\app\Utils\Utils;
use Drupal\app\Views\CompleteRegistrationEmailView;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UserController extends ControllerBase implements ContainerInjectionInterface
{
    protected $httpKernel;

    public function __construct($http_kernel)
    {
        $this->httpKernel = $http_kernel;
    }

    public static function create(ContainerInterface $container)
    {
        return new static(
        $container->get('http_kernel.basic')
    );
    }

    public function post()
    {
        $response = Utils::request('/entity/user?_format=json', $this->httpKernel);
        $status = $response->getStatusCode();
        if ($response->getStatusCode() == 201) {
            $json = json_decode($response->getContent());
            $uid = $json->uid[0]->value;
            $account = User::load($uid);
            self::notify($account);
        }
        return $response;
    }

    private static function notify($account)
    {
        $mailManager = \Drupal::service('plugin.manager.mail');

        $v = new CompleteRegistrationEmailView();
        $v->account = $account;
        $v->otp = rest_password_temp_pass_token($account);

        $vars = [
            '#theme' => 'complete_registration_email',
            '#v' => $v
        ];

        $email = $account->get('mail')->getValue()[0]['value'];
        $mailManager->mail("app", "complete_registration_email", $email, $v->langcode, [
            'subject' => t("Welcome to the Mentoring Connector", [], ['langcode' => $v->langcode]),
            'body' => $vars
        ]);
    }
}
