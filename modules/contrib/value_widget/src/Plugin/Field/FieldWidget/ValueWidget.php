<?php

namespace Drupal\value_widget\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Widget to set a value.
 *
 * @FieldWidget(
 *   id = "value",
 *   label = @Translation("Value"),
 *   field_types = {
 *     "integer",
 *     "decimal",
 *     "float",
 *     "list_integer",
 *     "list_float",
 *     "list_string",
 *     "text"
 *   }
 * )
 */
class ValueWidget extends WidgetBase {

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $plugin->token = $container->get('token');
    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'callback' => '',
      'type' => 'value',
      'values' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $fieldName = $this->fieldDefinition->getName();

    $element['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#options' => [
        'value' => $this->t('Static value'),
        'callback' => $this->t('Callback'),
      ],
      '#default_value' => $this->getSetting('type'),
      '#description' => $this->t('How to determine the value to set in this field.'),
      '#required' => TRUE,
    ];

    $description['text'] = [
      '#plain_text' => $this->t('Enter the values to set on the field. Use a comma-delimited string for multiple values with " as enclosure and \ as escape characters.'),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
    $description['token_help'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => [$this->fieldDefinition->getTargetEntityTypeId()],
    ];

    $element['values'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Values'),
      '#default_value' => $this->getSetting('values'),
      '#description' => $description,
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $fieldName . '][settings_edit_form][settings][type]"]' => ['value' => 'value'],
        ],
      ],
    ];

    $element['callback'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Callback'),
      '#default_value' => $this->getSetting('callback'),
      '#description' => $this->t('Enter a callback function used to obtain values to set on the field.'),
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $fieldName . '][settings_edit_form][settings][type]"]' => ['value' => 'callback'],
        ],
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    switch ($this->getSetting('type')) {
      case 'callback':
        $values = $this->getValuesFromCallback($items);
        break;

      case 'value':
        $values = $this->getValues($items->getEntity());
        break;

      default:
        $values = [];
    }

    $values = array_values($values);
    if (array_key_exists($delta, $values)) {
      $element['value'] = [
        '#type' => 'value',
        '#value' => $values[$delta],
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    if ($this->getSetting('type') == 'value') {
      $values = $this->getValues();
      if (count($values) == 0) {
        $summary[] = $this->t('Value:') . ' ' . $this->t('Not set');
      }
      elseif (count($values) > 1) {
        $summary[] = $this->t('Values:');
        $summary[] = [
          '#theme' => 'item_list',
          '#items' => $values,
        ];
      }
      else {
        $summary[] = $this->t('Value:') . ' ' . reset($values);
      }
    }
    elseif ($this->getSetting('type') == 'callback') {
      if (!empty($this->getSetting('callback'))) {
        $summary[] = $this->t('Callback:') . ' ' . $this->getSetting('callback');
      }
      else {
        $summary[] = $this->t('Callback:') . ' ' . $this->t('Not set');
      }
    }

    return $summary;
  }

  /**
   * Get the values to use for the field's form elements.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   The entity. Optional, tokenization functionality is performed when entity
   *   is used.
   *
   * @return array
   *   The values to set in the field's form elements.
   */
  protected function getValues(EntityInterface $entity = NULL) {
    $values = str_getcsv($this->getSetting('values'));

    // Tokenize.
    if ($entity) {
      $data = [$entity->getEntityTypeId() => $entity];
      foreach ($values as $delta => $value) {
        $values[$delta] = $this->token->replace($value, $data);
      }
    }

    return array_filter($values);
  }

  /**
   * Get the values to use for the field's form elements from a callback.
   *
   * @return array
   *   The values to set in the field's form elements.
   */
  protected function getValuesFromCallback(FieldItemListInterface $items) {
    $callback = $this->getSetting('callback');
    if (is_callable($callback)) {
      return $callback($items->getEntity(), $items);
    }
    return [];
  }

}
