<?php

namespace Drupal\digital_signage_framework\Plugin\Field\FieldFormatter;

use Drupal;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'Preview' formatter.
 *
 * @FieldFormatter(
 *   id = "digital_signage_schedule_preview",
 *   label = @Translation("Preview"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class Preview extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition): bool {
    /** @noinspection PhpUndefinedMethodInspection */
    return
      $field_definition->toArray()['entity_type'] === 'digital_signage_device' &&
      $field_definition->getSetting('target_type') === 'digital_signage_schedule';
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    if (!Drupal::currentUser()->hasPermission('digital signage framework access preview')) {
      return [];
    }
    /** @var \Drupal\digital_signage_framework\DeviceInterface $device */
    $device = $items->getEntity();
    $build = [
      '#prefix' => '<div class="digital-signage-device-preview-buttons">',
      '#suffix' => '</div>',
      '#attached' => [
        'drupalSettings' => [
          'digital_signage' => [
            'devices' => [
              $device->id() => [
                'orientation' => $device->getOrientation(),
                'proportion' => $device->getWidth() / $device->getHeight(),
                'schedule' => $device->getApiSpec(),
                'width' => $device->getWidth(),
                'height' => $device->getHeight(),
              ],
            ],
          ],
        ],
        'library' => [
          'digital_signage_framework/schedule.preview',
        ],
      ],
      [
        '#type' => 'button',
        '#value' => $this->t('Preview diagram'),
        '#attributes' => [
          'class' => ['digital-signage', 'diagram'],
          'device-id' => $device->id(),
          'stored-schedule' => 'false',
        ],
      ],
      [
        '#type' => 'button',
        '#value' => $this->t('Preview schedule'),
        '#attributes' => [
          'class' => ['digital-signage', 'preview'],
          'device-id' => $device->id(),
          'stored-schedule' => 'false',
        ],
      ],
      [
        '#type' => 'button',
        '#value' => $this->t('Preview slide'),
        '#attributes' => [
          'class' => ['digital-signage', 'slide'],
          'device-id' => $device->id(),
          'stored-schedule' => 'false',
        ],
      ],
    ];
    if ($schedule = $device->getSchedule()) {
      $live = [
        [
          '#type' => 'button',
          '#value' => $this->t('Live diagram'),
          '#attributes' => [
            'class' => ['digital-signage', 'diagram', 'live'],
            'device-id' => $device->id(),
            'stored-schedule' => 'true',
          ],
        ],
        [
          '#type' => 'button',
          '#value' => $this->t('Live schedule (@date)', [
            '@date' => Drupal::service('date.formatter')->format($schedule->getCreatedTime(), 'short'),
          ]),
          '#attributes' => [
            'class' => ['digital-signage', 'preview', 'live'],
            'device-id' => $device->id(),
            'stored-schedule' => 'true',
          ],
        ],
        [
          '#type' => 'button',
          '#value' => $this->t('Live slide'),
          '#attributes' => [
            'class' => ['digital-signage', 'slide', 'live'],
            'device-id' => $device->id(),
            'stored-schedule' => 'true',
          ],
        ],
        [
          '#type' => 'button',
          '#value' => $this->t('Live screenshot'),
          '#attributes' => [
            'class' => ['digital-signage', 'screenshot', 'live'],
            'device-id' => $device->id(),
          ],
        ],
        [
          '#type' => 'button',
          '#value' => $this->t('Live debug log'),
          '#attributes' => [
            'class' => ['digital-signage', 'debug-log', 'live'],
            'device-id' => $device->id(),
          ],
        ],
        [
          '#type' => 'button',
          '#value' => $this->t('Live error log'),
          '#attributes' => [
            'class' => ['digital-signage', 'error-log', 'live'],
            'device-id' => $device->id(),
          ],
        ],
      ];
      foreach ($live as $item) {
        $build[] = $item;
      }

    }
    return $build;
  }

}
