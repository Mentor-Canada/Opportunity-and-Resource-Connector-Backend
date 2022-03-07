<?php

namespace Drupal\app\Plugin\Field\FieldWidget;

use Drupal\app\Plugin\Field\FieldType\MtgItem;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * @FieldWidget(
 *   id = "mtg_widget",
 *   label = @Translation("MTG Widget"),
 *   field_types = {
 *     "mtg_item"
 *   },
 * )
 */
class MtgWidget extends WidgetBase implements WidgetInterface
{
    public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state)
    {
        $columns = MtgItem::columns();
        foreach ($columns as $key => $column) {
            $element[$key] = [
                '#type' => 'textfield',
                '#title' => $key,
                '#default_value' => $items[$delta]->$key,
            ];
        }
        return $element;
    }
}
