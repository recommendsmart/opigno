<?php

namespace Drupal\entity_list\Plugin\EntityListFilter;

use Drupal\Core\Entity\Query\Sql\Query;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\entity_list\Entity\EntityList;
use Drupal\entity_list\Plugin\EntityListFilterBase;
use Drupal\entity_list\Service\ContentFilterService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class SearchEntityListFilter.
 *
 * @package Drupal\entity_list\Plugin
 *
 * @EntityListFilter(
 *   id = "search_entity_list_filter",
 *   label = @Translation("Search Entity List Filter"),
 *   content_type = {},
 *   entity_type = {
 *     "node"
 *   },
 * )
 */
class SearchEntityListFilter extends EntityListFilterBase implements ContainerFactoryPluginInterface {

  const FIELD_SEARCH = 'search';

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Drupal\entity_list\Service\ContentFilterService definition.
   *
   * @var \Drupal\entity_list\Service\ContentFilterService
   */
  protected $contentFilterService;

  /**
   * Constructs DateEntityListFilter object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service used to get url query parameters.
   * @param \Drupal\entity_list\Service\ContentFilterService $content_filter_service
   *   The content filter service.
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, RequestStack $request_stack, ContentFilterService $content_filter_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestStack = $request_stack;
    $this->contentFilterService = $content_filter_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('request_stack'),
      $container->get('service.content_filter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildFilter(array $parameters, EntityList $entity_list) {
    $filter = parent::buildFilter($parameters, $entity_list);
    $request = $this->requestStack->getCurrentRequest();

    $filter[self::FIELD_SEARCH] = [
      '#type' => 'textfield',
      '#title' => $this->t($parameters['settings']['title']),
      '#attributes' => [
        'placeholder' => $parameters['settings']['placeholder'] ?? '',
      ],
      '#default_value' => $request->get(self::FIELD_SEARCH, ''),
    ];

    return $filter;
  }

  /**
   * {@inheritdoc}
   */
  public function configurationFilter(array $default_value, EntityList $entity_list) {
    $configuration =  parent::configurationFilter($default_value, $entity_list);
    $options = $this->fieldsTextToOption($this->contentFilterService->getFilterTextFields($entity_list));

    $configuration['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $default_value['title'] ?? '',
      '#required' => TRUE,
    ];

    $configuration['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#default_value' => $default_value['placeholder'] ?? '',
    ];

    $configuration['fields'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Fields'),
      '#default_value' => $default_value['fields'] ?? [],
      '#required' => TRUE,
      '#options' => $options,
    ];

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setFields(array $settings) {
    $this->fields = [
      [
        'name' => self::FIELD_SEARCH,
        'callback_condition' => __CLASS__ . '::searchFilter',
        'fields' => $settings['fields'] ?? [],
      ],
    ];

    return $this->fields;
  }

  /**
   * Taxonomy filter.
   *
   * @param Query $query
   *   This is query.
   * @param $fieldSettings
   *   This is field settings.
   * @param $fieldValue
   *   This is field value.
   */
  public static function searchFilter(Query $query, $fieldSettings, $fieldValue) {
    $orCondition = $query->orConditionGroup();

    foreach($fieldSettings['fields'] as $field) {
      if ($field) {
        $orCondition->condition($field, '%' . $fieldValue . '%', 'LIKE');
      }
    }

    $query->condition($orCondition);
  }

  /**
   * Get fields text in array of options.
   *
   * @param array $fields_text
   *   This is array of fields text.
   *
   * @return array
   *   Return array of options.
   */
  public function fieldsTextToOption(array $fields_text) {
    $options = [];

    foreach ($fields_text as $field) {
      $options[$field['key']] = $field['label'];
    }

    return $options;
  }
}
