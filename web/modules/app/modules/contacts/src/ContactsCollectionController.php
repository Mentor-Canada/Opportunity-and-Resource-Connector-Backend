<?php

namespace Drupal\app_contacts;

use Symfony\Component\HttpFoundation\JsonResponse;

class ContactsCollectionController
{

    public static function collection()
    {
        $builder = new ContactCollectionBuilder();
        return new JsonResponse(['data' => $builder->getCollection()]);
    }

}
