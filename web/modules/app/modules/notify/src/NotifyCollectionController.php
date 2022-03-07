<?php

namespace Drupal\app_notify;

use Drupal\app\Collection\CollectionControllerBase;
use Drupal\app\Collection\CollectionControllerInterface;
use Drupal\app\RequestAdapterBase;
use Drupal\app\Utils\Utils;

class NotifyCollectionController extends CollectionControllerBase implements CollectionControllerInterface
{
    public function __construct($http_kernel)
    {
        parent::__construct($http_kernel);
        $this->adapter = new RequestAdapterBase(\Drupal::request());

        $builder = (new NotifyCollectionBuilder())
      ->createdStart($this->adapter->createdStartDate)
      ->createdStop($this->adapter->createdEndDate)
      ->orderBy($this->adapter->sortField, $this->adapter->sortDirection);

        $this->total = $builder->executeCount();
        $builder->range($this->adapter->offset, $this->adapter->limit);
        $this->data = $builder->execute();
    }

    public function csv()
    {
        $rows = [];
        $header = [];
        $header[] = strval(t('app-partner'));
        $header[] = strval(t('app-role'));
        $header[] = strval(t('app-mentoring-type'));
        $header[] = strval(t('app-postal-zip-code'));
        $header[] = strval(t('app-email'));
        $header[] = strval(t('app-first-name'));
        $header[] = strval(t('app-last-name'));
        $header[] = strval(t('app-field-how-did-you-hear-about-us'));
        $header[] = strval(t('app-other'));
        $header[] = strval(t('app-created'));

        $rows[] = $header;

        foreach ($this->data as $row) {
            $item = [];

            $item[] = $row->field_display_title_value;
            $item[] = $row->field_role_value;
            $item[] = $row->field_type_of_mentoring_value;
            $item[] = $row->field_zip_value;
            $item[] = $row->field_email_value;
            $item[] = $row->field_first_name_value;
            $item[] = $row->field_last_name_value;
            $item[] = !empty($row->field_how_did_you_hear_about_us_value) ? t($row->field_how_did_you_hear_about_us_value) : '';
            $item[] = $row->field_how_did_you_hear_other_value;
            $item[] = date('Y-m-d H:i:s', $row->created);

            $rows[] = $item;
        }

        Utils::exporter($rows, 'notification-requests.csv');
        exit;
    }

    public function collectionTotal(): int
    {
        return $this->total;
    }

    public function collectionData(): array
    {
        return $this->data;
    }

    public function paginationOffset(): ?int
    {
        return $this->adapter->offset;
    }

    public function paginationLimit(): ?int
    {
        return $this->adapter->limit;
    }
}
