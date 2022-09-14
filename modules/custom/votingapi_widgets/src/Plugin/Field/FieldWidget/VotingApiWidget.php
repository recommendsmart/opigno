<?php

namespace Drupal\votingapi_widgets\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\votingapi_widgets\Plugin\VotingApiWidgetManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Plugin implementation of the 'voting_api_widget' widget.
 *
 * @FieldWidget(
 *   id = "voting_api_widget",
 *   label = @Translation("Voting api widget"),
 *   field_types = {
 *     "voting_api_field"
 *   }
 * )
 */
class VotingApiWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return ['show_initial_vote' => 0];
  }

  /**
   * The votingapi_widget widget manager.
   *
   * @var \Drupal\votingapi_widgets\Plugin\VotingApiWidgetManager
   */
  protected $votingapiWidgetProcessor;

  /**
   * The user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Constructs the VotingApiWidget object.
   *
   * @param string $plugin_id
   *   The plugin ID for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\votingapi_widgets\Plugin\VotingApiWidgetManager $widget_manager
   *   The votingapi_widget widget manager.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, VotingApiWidgetManager $widget_manager, AccountInterface $account) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->account = $account;
    $this->votingapiWidgetProcessor = $widget_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('plugin.manager.voting_api_widget.processor'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['show_initial_vote'] = [
      '#type' => 'select',
      '#options' => [
        0 => $this->t("Don't show initial vote"),
        1 => $this->t('Show initial vote'),
      ],
      '#default_value' => $this->getSetting('show_initial_vote'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $entity = $items->getEntity();
    $element['status'] = [
      '#type' => 'radios',
      '#title' => $this->fieldDefinition->getLabel(),
      '#default_value' => isset($items->getValue('status')[0]['status']) ? $items->getValue('status')[0]['status'] : 1,
      '#options' => [
        1 => $this->t('Open'),
        0 => $this->t('Closed'),
      ],
    ];
    $entity_type = $this->fieldDefinition->getTargetEntityTypeId();
    $bundle = $this->fieldDefinition->getTargetBundle();
    $field_name = $this->fieldDefinition->getName();
    $permission = 'edit voting status on ' . $entity_type . ':' . $bundle . ':' . $field_name;
    $element['status']['#access'] = $this->account->hasPermission($permission);

    $plugin = $this->fieldDefinition->getSetting('vote_plugin');
    /** @var \Drupal\votingapi_widgets\Plugin\VotingApiWidgetBase $plugin */
    $plugin = $this->votingapiWidgetProcessor->createInstance($plugin);

    $permission = 'vote on ' . $entity_type . ':' . $bundle . ':' . $field_name;
    $options = [
      '' => $this->t('None'),
    ];

    $vote_type = 'vote';
    $vote = $plugin->getEntityForVoting($entity_type, $bundle, $entity->id(), $vote_type, $field_name);
    $options += $plugin->getValues();
    $element['value'] = [
      '#type' => 'select',
      '#title' => $this->t('Your vote'),
      '#options' => $options,
      '#default_value' => $vote->getValue(),
      '#access' => ($this->getSetting('show_initial_vote') && $this->account->hasPermission($permission)) ? TRUE : FALSE,
    ];

    $plugin->getInitialVotingElement($element);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t(
      'Show initial vote: @show_initial_vote',
      ['@show_initial_vote' => $this->getSetting('show_initial_vote') ? $this->t('yes') : $this->t('no')]
    );

    return $summary;
  }

}
