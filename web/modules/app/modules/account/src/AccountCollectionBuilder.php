<?php

namespace Drupal\app_account;

use Drupal;

class AccountCollectionBuilder
{
    private $q;

    public function __construct()
    {
        $this->q = Drupal::database()->select("users");
        $this->q->addField('users', 'uuid');
        $this->q->condition('users.uid', 0, '>');
        $this->q->leftJoin('users_field_data', 'data', 'users.uid = data.uid');
        $this->q->addField('data', 'mail');
        $this->q->addField('data', 'created');
        $this->q->leftJoin('user__field_first_name', 'firstName', 'users.uid = firstName.entity_id');
        $this->q->addField('firstName', 'field_first_name_value', 'firstName');
        $this->q->leftJoin('user__field_last_name', 'lastName', 'users.uid = lastName.entity_id');
        $this->q->addField('lastName', 'field_last_name_value', 'lastName');

        // global
        $this->q->leftJoin('user__field_global_administrator', 'globalAdministrators', 'users.uid = globalAdministrators.entity_id');
        $this->q->addField('globalAdministrators', 'field_global_administrator_value', 'globalAdministrator');
        $this->q->addField('data', 'mail');

        // affiliates
        Drupal::database()->query("CREATE TEMPORARY TABLE affiliateAdministrators
SELECT field_administrators_target_id as uid, JSON_ARRAYAGG(JSON_OBJECT('id', node.uuid, 'name', node_field_data.title)) as affiliates
FROM node__field_administrators
LEFT JOIN node_field_data ON node__field_administrators.entity_id = node_field_data.nid
LEFT JOIN node ON node__field_administrators.entity_id = node.nid
WHERE bundle = 'region'
AND node_field_data.langcode = 'en'
GROUP BY field_administrators_target_id
    ");
        $this->q->leftJoin('affiliateAdministrators', 'affiliateAdministrators', 'users.uid = affiliateAdministrators.uid');
        $this->q->addField('affiliateAdministrators', 'affiliates');

        // organizations
        Drupal::database()->query("CREATE TEMPORARY TABLE organizationAdministrators
SELECT field_administrators_target_id as uid, JSON_ARRAYAGG(JSON_OBJECT('id', node.uuid, 'name', field_display_title_value)) as organizations
FROM node__field_administrators
LEFT JOIN node ON node__field_administrators.entity_id = node.nid
LEFT JOIN node_field_data ON node.nid = node_field_data.nid
LEFT JOIN node__field_display_title ON node.nid = node__field_display_title.entity_id
WHERE node.type = 'organization'
AND node_field_data.langcode = 'en'
AND node__field_display_title.langcode = 'en'
GROUP BY field_administrators_target_id
");
        $this->q->leftJoin('organizationAdministrators', 'organizationAdministrators', 'users.uid = organizationAdministrators.uid');
        $this->q->addField('organizationAdministrators', 'organizations');

        // programs
        Drupal::database()->query("CREATE TEMPORARY TABLE programAdministrators
SELECT field_administrators_target_id as uid, JSON_ARRAYAGG(JSON_OBJECT('id', node.uuid, 'name', JSON_EXTRACT(programs.title, '$.en'))) as programs
FROM node__field_administrators
LEFT JOIN node ON node__field_administrators.entity_id = node.nid
LEFT JOIN node_field_data ON node.nid = node_field_data.nid
LEFT JOIN programs ON node.nid = programs.entity_id
WHERE node.type = 'programs'
AND node_field_data.langcode = 'en'
GROUP BY field_administrators_target_id
");
        $this->q->leftJoin('programAdministrators', 'programAdministrators', 'users.uid = programAdministrators.uid');
        $this->q->addField('programAdministrators', 'programs');
    }

    public function accountType($value): AccountCollectionBuilder
    {
        if ($value) {
            if ($value == "app-global-administrator") {
                $this->q->condition("field_global_administrator_value", "1");
            }
            if ($value == "app-affiliate-administrator") {
                $this->q->isNotNull("affiliates");
            }
            if ($value == "app-organization-administrator") {
                $this->q->isNotNull("organizations");
            }
            if ($value == "app-program-administrator") {
                $this->q->isNotNull("programs");
            }
        }
        return $this;
    }

    public function mentorCity($value)
    {
        if ($value) {
            $mentorCityProgramCollection = (new Drupal\app_mentorcity\MentorCityProgramCollectionBuilder())->build();
            $adminAccountIds = $mentorCityProgramCollection->adminAccountIds();
            $operator = $value == "app-yes" ? "IN" : "NOT IN";
            $this->q->condition("users.uid", $adminAccountIds, $operator);
        }
        return $this;
    }

    function firstName($value)
    {
        if ($value) {
            $this->q->condition('firstName.field_first_name_value', "%$value%", "LIKE");
        }
        return $this;
    }

    function lastName($value)
    {
        if ($value) {
            $this->q->condition('lastName.field_last_name_value', "%$value%", "LIKE");
        }
        return $this;
    }

    public function range($limit, $offset = 0): AccountCollectionBuilder
    {
        if ($limit) {
            $this->q->range($offset, $limit);
        }
        return $this;
    }

    public function orderBy($field, $direction): AccountCollectionBuilder
    {
        if ($field && $direction) {
            $this->q->orderBy($field, $direction);
        }
        return $this;
    }

    public function mail($value): AccountCollectionBuilder
    {
        if ($value) {
            $this->q->condition('data.mail', "%$value%", "LIKE");
        }
        return $this;
    }

    public function created($start, $stop): AccountCollectionBuilder
    {
        if ($start) {
            $this->q->condition('data.created', $start, ">=");
        }
        if ($stop) {
            $this->q->condition('data.created', $stop, "<=");
        }
        return $this;
    }

    public function total(): int
    {
        $this->q->range(null, null);
        return intval($this->q->countQuery()->execute()->fetchField());
    }

    public function build(): array
    {
        $result = $this->q->execute()->fetchAll();

        $accounts = [];
        foreach ($result as $result) {
            $account = new Account();
            $account->id = $result->uuid;
            $account->mail = $result->mail;
            $account->firstName = $result->firstName;
            $account->lastName = $result->lastName;
            $account->globalAdministrator = $result->globalAdministrator;
            $account->affiliates = $this->formatList($result->affiliates);
            $account->organizations = $this->formatList($result->organizations);
            $account->programs = $this->formatList($result->programs);
            $account->created = intval($result->created) * 1000;
            $accounts[] = $account;
        }

        return $accounts;
    }

    private function formatList(?string $listJSON)
    {
        $list = json_decode($listJSON);
        if ($list) {
            usort($list, function ($a, $b) {
                $result = strcmp($a->name, $b->name);
                if ($result == 0) {
                    return 0;
                }
                if ($result > 0) {
                    return 1;
                }
                if ($result < 0) {
                    return -1;
                }
            });
            return $list;
        }
        return [];
    }
}
