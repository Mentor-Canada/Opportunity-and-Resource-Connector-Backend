<?php

namespace Drupal\app_filter;

use Drupal;

class FilterEntity
{
    public $title;
    public $type;
    public $data;
    public $uid;

    public function save()
    {
        $db = Drupal::database();
        $db->insert('filter')
      ->fields([
          'uid' => $this->uid,
          'title' => $this->title,
          'type' => $this->type,
          'data' => $this->data
      ])->execute();
    }
}
