<?php

namespace Drupal\app\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * @FieldWidget(
 *   id = "location_widget",
 *   label = @Translation("Location Widget"),
 *   field_types = {
 *     "location_item"
 *   },
 * )
 */
class LocationWidget extends WidgetBase implements WidgetInterface
{
    public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state)
    {
        $element['place_id'] = [
            '#type' => 'textfield',
            '#title' => t('Place Id'),
            '#default_value' => $items[$delta]->place_id,
        ];
        $element['type'] = [
            '#type' => 'textfield',
            '#title' => t('Type'),
            '#default_value' => $items[$delta]->type,
        ];
        $element['name'] = [
            '#type' => 'textfield',
            '#title' => t('Name'),
            '#default_value' => $items[$delta]->name,
        ];
        $element['name'] = [
            '#type' => 'textfield',
            '#title' => t('Postal Code'),
            '#default_value' => $items[$delta]->postal_code,
        ];
        $element['lat'] = [
            '#type' => 'textfield',
            '#title' => t('Lat'),
            '#default_value' => $items[$delta]->lat,
        ];
        $element['lng'] = [
            '#type' => 'textfield',
            '#title' => t('Lng'),
            '#default_value' => $items[$delta]->lng,
        ];
        $element['country'] = [
            '#type' => 'textfield',
            '#title' => t('Country'),
            '#default_value' => $items[$delta]->country,
        ];
        $element['province'] = [
            '#type' => 'textfield',
            '#title' => t('Province'),
            '#default_value' => $items[$delta]->province,
        ];
        $element['components'] = [
            '#type' => 'textarea',
            '#title' => t('Address Components'),
            '#default_value' => $items[$delta]->components,
        ];
        return $element;
    }
}
