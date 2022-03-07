<?php

namespace Drupal\app\Controller;

use Drupal\app\Utils\ProgramUtils;
use Drupal\app\Utils\Security;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class UserApplicationsController extends ControllerBase implements ContainerInjectionInterface
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

    public function get()
    {
        $params = $_GET;
        if (!Security::globalAdministrator()) {
            $userPrograms = ProgramUtils::programsForUser();
            $applications = self::applicationsForPrograms($userPrograms);
            if (!count($applications)) {
                $applications[] = 0;
            }
            $params['filter']['drupal_internal__nid'] = [];
            $params['filter']['drupal_internal__nid']['value'] = $applications;
            $params['filter']['drupal_internal__nid']['operator'] = 'IN';
        }
        $sub_request = Request::create("/a/application", 'GET', $params);
        return $this->httpKernel->handle($sub_request, HttpKernelInterface::SUB_REQUEST);
    }

    private static function applicationsForPrograms($programs)
    {
        if (!count($programs)) {
            return [];
        }
        $in = implode(',', $programs);
        $q = "SELECT entity_id FROM node__field_program
            WHERE field_program_target_id IN ($in)
            AND bundle = 'application'
            AND langcode = 'en'
            ";
        $database = \Drupal::database();
        $query = $database->query($q);
        $result = $query->fetchAll();
        $ids = array_map(function ($a) {
            return $a->entity_id;
        }, $result);
        return $ids;
    }
}
