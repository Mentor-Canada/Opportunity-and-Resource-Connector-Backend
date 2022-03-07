<?php

namespace Drupal\rest_password\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;
use Drupal\user\UserStorageInterface;

/**
 * Provides a resource to Email new password to user.
 *
 * @RestResource(
 *   id = "lost_password_resource",
 *   label = @Translation("Lost password"),
 *   uri_paths = {
 *     "canonical" = "/user/lost-password",
 *     "create" = "/user/lost-password"
 *   }
 * )
 */
class GetPasswordRestResource extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * Constructs a new GetPasswordRestResourse object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user,
    UserStorageInterface $user_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->currentUser = $current_user;
    $this->userStorage = $user_storage;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest_password'),
      $container->get('current_user'),
      $container->get('entity_type.manager')->getStorage('user')
    );
  }

  /**
   * Responds to POST requests with mail . and lang.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function post(array $data) {
    $response = ['message' => $this->t('Please Post mail key.')];
    $code = 400;
    if (!empty($data['mail'])) {
      $email = $data['mail'];
      $lang = NULL;
      if (!empty($data['lang'])) {
        $lang = $data['lang'];
      }

      // Try to load by email.
      $users = $this->userStorage->loadByProperties(['mail' => $email]);
      if (!empty($users)) {
        $account = reset($users);
        if ($account && $account->id()) {
          // Blocked accounts cannot request a new password.
          if (!$account->isActive()) {
            $response = t('This account is blocked or has not been activated yet.');
          }
          else {
            // Mail a temp password.
            $mail = _rest_password_user_mail_notify('password_reset_rest', $account, $lang);
            if (!empty($mail)) {
              $this->logger->notice('Password temp password instructions mailed to %email.', ['%email' => $account->getEmail()]);
              $response = ['message' => $this->t('Further instructions have been sent to your email address.')];
              $code = 200;
            }
            else {
              $response = ['message' => $this->t("Sorry system can't send email at the moment")];
              $code = '400';
            }
          }
        }
      }
      else {
        $response = ['message' => $this->t('This User was not found or invalid')];
      }
    }

    return new ResourceResponse($response, $code);
  }

}
