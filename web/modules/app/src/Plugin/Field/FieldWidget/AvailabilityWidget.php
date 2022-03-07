<?php

namespace Drupal\app\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * @FieldWidget(
 *   id = "availability_widget",
 *   label = @Translation("Availability Widget"),
 *   field_types = {
 *     "availability_item"
 *   },
 * )
 */
class AvailabilityWidget extends WidgetBase implements WidgetInterface
{
    public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state)
    {
        $day = $items[$delta]->day;
        $from = $items[$delta]->from;
        $to = $items[$delta]->to;

        $element['#attached']['library'][] = 'app/availability';
        $element['#prefix'] = "<div class='availability-widget'>";

        $element['day'] = [
            '#type' => 'select',
            '#title' => t('Day'),
            '#default_value' => $day,
            '#options' => [
                0 => "Monday",
                1 => "Tuesday",
                2 => "Wednesday",
                3 => "Thursday",
                4 => "Friday",
                5 => "Saturday",
                6 => "Sunday"
            ],
            '#prefix' => "<div class='availability-day'>"
        ];
        $element['from'] = [
            '#type' => 'textfield',
            '#title' => t('From'),
            '#default_value' => $from,
            '#prefix' => "</div><div class='availability-from'>"
        ];
        $element['to'] = [
            '#type' => 'textfield',
            '#title' => t('To'),
            '#default_value' => $to,
            '#attributes' => ['class' => ['whynot']],
            '#prefix' => "</div><div class='availability-to'>"
        ];

        return $element;
    }
}
