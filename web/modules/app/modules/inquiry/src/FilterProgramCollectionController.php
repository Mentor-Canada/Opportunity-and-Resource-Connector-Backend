<?php

namespace Drupal\app_inquiry;

use Drupal\app\Controller\BaseController;
use Drupal\app\Utils\ProgramUtils;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\JsonResponse;

class FilterProgramCollectionController extends BaseController
{
    public function get()
    {
        $userPrograms = ProgramUtils::programsForUser();
        $programs = Node::loadMultiple($userPrograms);
        foreach ($programs as $program) {
            $item = [];
            $item['value'] = $program->uuid();
            $item['name'] = $program->get('field_display_title')->getValue()[0]['value'];
            $results[] = $item;
        }
        usort($results, function ($a, $b) {
            return strcmp($b['title'], $b['title']);
        });

        // Add filters
        $db = \Drupal::database();
        $q = $db->select('node', 'node');
        $this->addField($q, FilterFields::title);
        $this->addField($q, FilterFields::type);
        $q->condition(FilterFields::type . "_value", "program");
        $q->addField('node', 'uuid', 'id');
        $q->condition('node.type', 'filter');
        $result = $q->execute()->fetchAll();

        if (count($result)) {
            $filters = [];
            foreach ($result as $row) {
                $filters[] = [
                    'name' => $row->field_display_title,
                    'value' => $row->id
                ];
            }
            if (count($result)) {
                $filters[] = [
                    'name' => '',
                    'value' => ''
                ];
            }
            $results = array_merge($filters, $results);
        }

        array_unshift($results, [
            'name' => '',
            'value' => ''
        ]);

        return new JsonResponse(["data" => $results]);
    }

    private function addField($q, $fieldName, $langcode = null, $join = true)
    {
        $name = str_replace("field_", '', $fieldName);
        if ($join) {
            $q->leftJoin("node__field_$name", "$name", "node.nid = $name.entity_id");
        }
        $q->addField($name, "field_{$name}_value", "field_$name");
        if (!empty($langcode)) {
            $q->condition("$name.langcode", $langcode);
        }
    }
}
