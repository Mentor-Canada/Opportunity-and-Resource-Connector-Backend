<?php

namespace Drupal\app_filter;

use Drupal;
use Drupal\app\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;

class FilterController extends BaseController
{
    public function collection(): JsonResponse
    {
        $rows = (new FilterCollectionBuilder())
      ->uid(Drupal::currentUser()->id())
      ->type($_REQUEST['type'])
      ->execute();
        foreach ($rows as &$row) {
            $row->data = json_decode($row->data);
        }
        usort($rows, fn ($a, $b) => strcmp($a->title, $b->title));
        return new JsonResponse($rows);
    }

    public function post(): JsonResponse
    {
        $postData = json_decode(Drupal::request()->getContent());
        $filter = new FilterEntity();
        $filter->type = $postData->type;
        $filter->title = $postData->title;
        $filter->data = $postData->data;
        $filter->uid = Drupal::currentUser()->id();
        $id = $filter->save();
        return new JsonResponse([
            'status' => 'success',
            'data' => [
                'id' => $id
            ]
        ]);
    }

    public function delete($id): JsonResponse
    {
        $db = Drupal::database();

        $uid = Drupal::currentUser()->id();
        $db->query("DELETE FROM filter WHERE uid = :uid AND id = :id", [
            'uid' => $uid,
            'id' => $id
        ]);

        return new JsonResponse(['status' => 'success']);
    }
}
