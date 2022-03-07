<?php

namespace Drupal\app\Settings;

use Drupal\app\Utils\RequestUtils;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class SettingsLanguageController extends ControllerBase implements ContainerInjectionInterface
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
        $data = RequestUtils::postData();

        $languages = \Drupal::languageManager()->getLanguages();
        $current = array_keys($languages);

        $new = [];
        foreach ($data as $row) {
            $new[] = $row->iso639_1;
        }

        $add = array_diff($new, $current);
        $remove = array_diff($current, $new);

        foreach ($add as $row) {
            $language = ConfigurableLanguage::createFromLangcode($row);
            $language->save();
        }

        foreach ($languages as $language) {
            $id = $language->getId();
            if (in_array($id, $remove)) {
                \Drupal::configFactory()->getEditable("language.entity.{$id}")->delete();
            }
        }

        drupal_flush_all_caches();

        return new JsonResponse(['data' => []]);
    }
}
