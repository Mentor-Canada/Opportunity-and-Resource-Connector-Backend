<?php

namespace Drupal\app_contacts;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ContactsCollectionController
{
    public static function collection()
    {
        $filterType = null;
        if (isset($_REQUEST['uuid'])) {
            $filterType = self::getFilterType($_REQUEST['uuid']);
            if (!$filterType) {
                return new Response("Invalid UUID", 400);
            }
        }

        $builder = new ContactCollectionBuilder($_REQUEST['uuid'], $filterType);
        return new JsonResponse(['data' => $builder->getCollection()]);
    }

    private static function getFilterType($uuid): ?string
    {
        $q = \Drupal::database()->select('node', 'node');
        $q->addField('node', 'type');
        $q->condition('node.uuid', $uuid);
        $q->condition('node.type', ['programs', 'organization'], 'IN');
        $filterQuery = $q->execute()->fetchCol();
        if (!empty($filterQuery)) {
            return $filterQuery[0];
        }

        $q = \Drupal::database()->select('users', 'users');
        $q->addField('users', 'uuid');
        $q->condition('users.uuid', $uuid);
        $filterQuery = $q->execute()->fetchCol();
        if (!empty($filterQuery)) {
            return 'contact';
        }

        return null;
    }
}
