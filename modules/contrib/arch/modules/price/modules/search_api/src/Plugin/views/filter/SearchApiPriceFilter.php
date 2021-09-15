<?php

namespace Drupal\arch_price_search_api\Plugin\views\filter;

use Drupal\arch_price\Manager\PriceTypeManagerInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\search_api\Plugin\views\filter\SearchApiNumeric;
use Drupal\search_api\Query\ConditionGroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a price filter to the view.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("arch_price_search_api")
 */
class SearchApiPriceFilter extends SearchApiNumeric {

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Price type manager.
   *
   * @var \Drupal\arch_price\Manager\PriceTypeManagerInterface
   */
  protected $priceTypeManager;

  /**
   * Currency storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $currencyStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    AccountInterface $current_user,
    PriceTypeManagerInterface $price_type_manager,
    ConfigEntityStorageInterface $currency_storage
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition
    );

    $this->currentUser = $current_user;
    $this->priceTypeManager = $price_type_manager;
    $this->currencyStorage = $currency_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('price_type.manager'),
      $container->get('entity_type.manager')->getStorage('currency')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['value']['contains']['base'] = ['default' => 'net'];
    $options['value']['contains']['currency_expose'] = ['default' => FALSE];
    $options['value']['contains']['currency'] = ['default' => ''];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['value']['base'] = [
      '#type' => 'select',
      '#title' => $this->t('Price base', [], ['context' => 'arch_price']),
      '#options' => [
        'net' => $this->t('Net', [], ['context' => 'arch_price']),
        'gross' => $this->t('Gross', [], ['context' => 'arch_price']),
      ],
      '#default_value' => $this->value['base'],
      '#required' => TRUE,
      '#weight' => -5,
    ];

    $currency_options = [
      '' => $this->t('- Select -'),
    ];
    $currency_options += \Drupal::service('currency.form_helper')->getCurrencyOptions();
    $form['value']['currency'] = [
      '#type' => 'select',
      '#options' => $currency_options,
      '#default_value' => $this->value['currency'],
      '#title' => $this->t('Currency'),
      '#weight' => -1,
    ];
    if ($this->options['exposed']) {
      $form['value']['currency_expose'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Expose currency selection'),
        '#default_value' => $this->value['currency_expose'],
        '#weight' => -2,
      ];
      $form['value']['currency']['#states']['required'] = [
        ':input[name="options[value][currency_expose]"]' => ['checked' => FALSE],
      ];
    }
    else {
      $form['value']['currency_expose'] = [
        '#type' => 'value',
        '#value' => FALSE,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposeForm(&$form, FormStateInterface $form_state) {
    parent::buildExposeForm($form, $form_state);
    $form['value']['currency_expose'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Expose currency selection'),
      '#default_value' => $this->value['currency_expose'],
      '#weight' => -2,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);

    $form['value']['base'] = [
      '#type' => 'value',
      '#value' => $this->options['expose']['currency'],
    ];

    if ($this->options['expose']['currency_expose']) {
      $currency_options = \Drupal::service('currency.form_helper')->getCurrencyOptions();
      unset($currency_options['XXX']);
      $form['value']['currency'] = [
        '#type' => 'select',
        '#title' => $this->t('Currency'),
        '#default_value' => $this->value['currency'],
        '#options' => $currency_options,
      ];
    }
    else {
      $currency = $this->options['expose']['currency'];
      $this->getModuleHandler()->alter('arch_price_search_api_filter_currency', $currency);
      $form['value']['currency'] = [
        '#type' => 'value',
        '#value' => $currency,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

    $fields = $this->getFilteringFields();
    $info = $this->operators();
    if (!empty($info[$this->operator]['method'])) {
      $group = $this->query->createConditionGroup('or', ['arch_price']);
      foreach ($fields as $field) {
        $this->{$info[$this->operator]['method']}($field['property'], $group);
      }
      $this->query->addConditionGroup($group);
    }
  }

  /**
   * Get filtering fields.
   *
   * @return string[][]
   *   List of filtering fields.
   */
  public function getFilteringFields(array $input = []) {
    $fields = [];

    $base = $this->options['value']['base'] ?: 'net';
    if (
      !empty($this->value['base'])
      && in_array($this->value['base'], ['net', 'gross'])
    ) {
      $base = $this->value['base'];
    }

    $currencies = $this->currencyStorage->loadMultiple();
    $selected_currency = $this->options['value']['currency'];
    if ($this->options['value']['currency_expose']) {
      if (
        !empty($input['currency'])
        && !empty($currencies[$input['currency']])
      ) {
        $selected_currency = $input['currency'];
      }
      elseif (
        !empty($this->value['currency'])
        && !empty($currencies[$this->value['currency']])
      ) {
        $selected_currency = $this->value['currency'];
      }
    }

    foreach ($this->priceTypeManager->getAvailablePriceTypes($this->currentUser, 'view') as $price_type) {
      foreach ($currencies as $currency_id => $currency) {
        if ($currency_id != $selected_currency) {
          continue;
        }

        /** @var \Drupal\currency\Entity\CurrencyInterface $currency */
        foreach (['net', 'gross'] as $field) {
          if ($field != $base) {
            continue;
          }
          $field = strtolower('arch_price_' . $field . '_' . $price_type->id() . '_' . $currency->id());
          $fields[$field] = [
            'property' => $field,
            'base' => $base,
            'currency' => $currency->id(),
            'price_type' => $price_type->id(),
          ];
        }
      }
    }
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  protected function opBetween($field, ConditionGroupInterface $group = NULL) {
    $operator = $this->operator == 'between' ? 'BETWEEN' : 'NOT BETWEEN';
    if ($group) {
      $group->addCondition(
        $field,
        [$this->value['min'], $this->value['max']],
        $operator
      );
    }
    else {
      $this->query->addWhere(
        $this->options['group'],
        $field,
        [$this->value['min'], $this->value['max']],
        $operator
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function opSimple($field, ConditionGroupInterface $group = NULL) {
    if ($group) {
      $group->addCondition($field, $this->value['value'], $this->operator);
    }
    else {
      $this->query->addWhere($this->options['group'], $field, $this->value['value'], $this->operator);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function opEmpty($field, ConditionGroupInterface $group = NULL) {
    $operator = $this->operator == 'empty' ? 'IS NULL' : 'IS NOT NULL';
    if ($group) {
      $group->addCondition($field, NULL, $operator);
    }
    else {
      $this->query->addWhere($this->options['group'], $field, NULL, $operator);
    }
  }

}
