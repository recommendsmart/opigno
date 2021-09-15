<?php

namespace Drupal\arch\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Store content settings form.
 *
 * @package Drupal\arch\Form
 */
class StoreContentSettingsForm extends FormBase {

  /**
   * Key value store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  protected $keyValueStore;

  /**
   * Settings.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $keyValue;

  /**
   * Node storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStore;

  /**
   * StoreContentSettingsForm constructor.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory
   *   Key value factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    KeyValueFactoryInterface $key_value_factory,
    EntityTypeManagerInterface $entity_type_manager,
    MessengerInterface $messenger
  ) {
    $this->keyValueStore = $key_value_factory;
    $this->keyValue = $key_value_factory->get('arch.content_settings');
    $this->nodeStore = $entity_type_manager->getStorage('node');
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('keyvalue'),
      $container->get('entity_type.manager'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'arch_content_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $form['mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Mode', [], ['context' => 'arch_content_settings']),
      '#options' => [
        '_none' => $this->t('Do not display links on checkout form', [], ['context' => 'arch_content_settings']),
        'TC' => $this->t('Display only "Terms and Conditions" link', [], ['context' => 'arch_content_settings']),
        'PP' => $this->t('Display only "Privacy Policy" link', [], ['context' => 'arch_content_settings']),
        'TCPP' => $this->t('Display "Terms and Conditions" and "Privacy Policy" link', [], ['context' => 'arch_content_settings']),
      ],
      '#default_value' => $this->keyValue->get('mode', '_none'),
    ];

    $form['nodes'] = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#states' => [
        'visible' => [
          [':input[data-drupal-selector="edit-mode"]' => ['value' => 'TC']],
          [':input[data-drupal-selector="edit-mode"]' => ['value' => 'PP']],
          [':input[data-drupal-selector="edit-mode"]' => ['value' => 'TCPP']],
        ],
      ],
    ];

    $tc_value = NULL;
    if ($tc_node_id = $this->keyValue->get('nodes.tc')) {
      $tc_value = $this->nodeStore->load($tc_node_id);
    }
    $form['nodes']['tc'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Terms and Conditions content', [], ['context' => 'arch_content_settings']),
      '#default_value' => $tc_value,
      '#states' => [
        'visible' => [
          [':input[data-drupal-selector="edit-mode"]' => ['value' => 'TC']],
          [':input[data-drupal-selector="edit-mode"]' => ['value' => 'TCPP']],
        ],
        'required' => [
          [':input[data-drupal-selector="edit-mode"]' => ['value' => 'TC']],
          [':input[data-drupal-selector="edit-mode"]' => ['value' => 'TCPP']],
        ],
      ],
      '#target_type' => 'node',
    ];

    $pp_value = NULL;
    if ($pp_node_id = $this->keyValue->get('nodes.pp')) {
      $pp_value = $this->nodeStore->load($pp_node_id);
    }
    $form['nodes']['pp'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Privacy Policy content', [], ['context' => 'arch_content_settings']),
      '#default_value' => $pp_value,
      '#states' => [
        'visible' => [
          [':input[data-drupal-selector="edit-mode"]' => ['value' => 'PP']],
          [':input[data-drupal-selector="edit-mode"]' => ['value' => 'TCPP']],
        ],
        'required' => [
          [':input[data-drupal-selector="edit-mode"]' => ['value' => 'PP']],
          [':input[data-drupal-selector="edit-mode"]' => ['value' => 'TCPP']],
        ],
      ],
      '#target_type' => 'node',
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save settings'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $mode = $form_state->getValue('mode');
    $nodes = [
      'tc' => ['TC', 'TCPP'],
      'pp' => ['PP', 'TCPP'],
    ];
    foreach ($nodes as $key => $modes) {
      if (
        in_array($mode, $modes)
        && $nid = $form_state->getValue(['nodes', $key])
      ) {
        /** @var \Drupal\node\NodeInterface $node */
        $node = $this->nodeStore->load($nid);
        if (!$node->isPublished()) {
          $form_state->setErrorByName(
            'nodes][' . $key,
            $this->t('Hidden content', [], ['context' => 'arch_content_settings'])
          );
        }
      }
      else {
        $form_state->setValue(['nodes', $key], NULL);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $tc = NULL;
    $pp = NULL;
    $mode = $form_state->getValue('mode');

    $this->keyValue->set('mode', $mode);
    if (in_array($mode, ['TC', 'TCPP'])) {
      $tc = $form_state->getValue(['nodes', 'tc']);
    }
    if (in_array($mode, ['PP', 'TCPP'])) {
      $pp = $form_state->getValue(['nodes', 'pp']);
    }
    $this->keyValue->set('nodes.tc', $tc);
    $this->keyValue->set('nodes.pp', $pp);
    $this->messenger()->addMessage($this->t('New settings have been saved.'));
  }

}
