<?php

namespace Drupal\app\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class CompleteRegistrationController extends ControllerBase implements ContainerInjectionInterface
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

    public function getData($email)
    {
        $email = str_replace(" ", "+", $email);
        $users = \Drupal::entityTypeManager()->getStorage('user')
        ->loadByProperties(['mail' => $email]);
        $user = reset($users);

        $active = !!$user->get("login")->getValue()[0]['value'];
        if ($active) {
            return new JsonResponse([
                "redirect" => true
            ]);
        }

        $firstName = $user->get('field_first_name')->getValue()[0]['value'];
        $lastName = $user->get('field_last_name')->getValue()[0]['value'];

        $data = [];
        $data['firstName'] = $firstName;
        $data['lastName'] = $lastName;

        return new JsonResponse($data);
    }

    public function update()
    {
        $request = \Drupal::request();
        $postBody = $request->getContent();
        $post = json_decode($postBody);

        $sub_request = Request::create(
            '/user/lost-password-reset?_format=json',
            'POST',
            [],
            [],
            [],
            [],
            $postBody
        );
        $sub_request->setRequestFormat('json');
        $sub_request->headers->set('content-type', 'application/json');

        $subResponse = $this->httpKernel->handle($sub_request, HttpKernelInterface::SUB_REQUEST);
        if (!$subResponse->getStatusCode() == 200) {
            return $subResponse;
        }

        $post->pass = $post->new_pass;
        $postBody = json_encode($post);

        $sub_request = Request::create(
            '/user/login?_format=json',
            'POST',
            [],
            [],
            [],
            [],
            $postBody
        );
        $sub_request->setRequestFormat('json');

        $subResponse = $this->httpKernel->handle($sub_request, HttpKernelInterface::SUB_REQUEST);

        $response = json_decode($subResponse->getContent());
        $uid = $response->current_user->uid;
        $account = User::load($uid);
        $account->set('field_first_name', $post->firstName);
        $account->set('field_last_name', $post->lastName);
        $account->save();

        return $subResponse;
    }
}
