<?php

namespace Drupal\arch\StoreDashboardPanel;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Plugin\ContextAwarePluginAssignmentTrait;
use Drupal\Core\Plugin\ContextAwarePluginBase;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\Core\Plugin\PluginWithFormsTrait;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines a base panel implementation that most panels plugins will extend.
 *
 * This abstract class provides the generic panel configuration form, default
 * panel settings, and handling for general user-defined block visibility
 * settings.
 */
abstract class StoreDashboardPanel extends ContextAwarePluginBase implements StoreDashboardPanelPluginInterface, PluginWithFormsInterface {

  use ContextAwarePluginAssignmentTrait;
  use MessengerTrait;
  use PluginWithFormsTrait;

  /**
   * The transliteration service.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliteration;

  /**
   * {@inheritdoc}
   */
  public function label() {
    if (!empty($this->configuration['label'])) {
      return $this->configuration['label'];
    }

    $definition = $this->getPluginDefinition();
    // Cast the admin label to a string since it is an object.
    // @see \Drupal\Core\StringTranslation\TranslatableMarkup
    return (string) $definition['admin_label'];
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep(
      $this->baseConfigurationDefaults(),
      $this->defaultConfiguration(),
      $configuration
    );
  }

  /**
   * Returns generic default configuration for StoreDashboardPanel plugins.
   *
   * @return array
   *   An associative array with the default configuration.
   */
  protected function baseConfigurationDefaults() {
    return [
      'id' => $this->getPluginId(),
      'label' => '',
      'provider' => $this->pluginDefinition['provider'],
      'label_display' => static::VISIBLE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfigurationValue($key, $value) {
    $this->configuration[$key] = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, $return_as_object = FALSE) {
    $access = $this->panelAccess($account);
    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * Indicates whether the panel should be shown.
   *
   * Panels with specific access checking should override this method rather
   * than access(), in order to avoid repeating the handling of the
   * $return_as_object argument.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user session for which to check access.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   *
   * @see self::access()
   */
  protected function panelAccess(AccountInterface $account) {
    // By default, the panel is visible.
    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   *
   * Creates a generic configuration form for all panel types. Individual
   * panel plugins can add elements to this form by overriding
   * StoreDashboardPanelBase::panelForm(). Most block plugins should not
   * override this method unless they need to alter the generic form elements.
   *
   * @see \Drupal\arch\StoreDashboardPanel\StoreDashboardPanelBase::blockForm()
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $definition = $this->getPluginDefinition();
    $form['provider'] = [
      '#type' => 'value',
      '#value' => $definition['provider'],
    ];

    $form['admin_label'] = [
      '#type' => 'item',
      '#title' => $this->t('Panel description', [], ['context' => 'arch_dashboard']),
      '#plain_text' => $definition['admin_label'],
    ];
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title', [], ['context' => 'arch_dashboard']),
      '#maxlength' => 255,
      '#default_value' => $this->label(),
      '#required' => TRUE,
    ];
    $form['label_display'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display title', [], ['context' => 'arch_dashboard']),
      '#default_value' => ($this->configuration['label_display'] === static::VISIBLE),
      '#return_value' => static::VISIBLE,
    ];

    // Add context mapping UI form elements.
    $contexts = $form_state->getTemporaryValue('gathered_contexts') ?: [];
    $form['context_mapping'] = $this->addContextAssignmentElement($this, $contexts);
    // Add plugin-specific settings for this panel type.
    $form += $this->panelForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function panelForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   *
   * Most panel plugins should not override this method. To add validation
   * for a specific panel type, override BlockBase::blockValidate().
   *
   * @see \Drupal\arch\StoreDashboardPanel\StoreDashboardPanelBase::panelkValidate()
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Remove the admin_label form item element value so it will not persist.
    $form_state->unsetValue('admin_label');

    $this->panelValidate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function panelValidate(array $form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   *
   * Most panel plugins should not override this method. To add submission
   * handling for a specific panel type, override
   * StoreDashboardPanelBase::panelSubmit().
   *
   * @see \Drupal\arch\StoreDashboardPanel\StoreDashboardPanelkBase::panelSubmit()
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Process the panel's submission handling if no errors occurred only.
    if (!$form_state->getErrors()) {
      $this->configuration['label'] = $form_state->getValue('label');
      $this->configuration['label_display'] = $form_state->getValue('label_display');
      $this->configuration['provider'] = $form_state->getValue('provider');
      $this->panelSubmit($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function panelSubmit(array $form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function getMachineNameSuggestion() {
    $definition = $this->getPluginDefinition();
    $admin_label = $definition['admin_label'];

    // @todo This is basically the same as what is done in
    //   \Drupal\system\MachineNameController::transliterate(), so it might make
    //   sense to provide a common service for the two.
    $transliterated = $this->transliteration()->transliterate($admin_label, LanguageInterface::LANGCODE_DEFAULT, '_');
    $transliterated = mb_strtolower($transliterated);

    $transliterated = preg_replace('@[^a-z0-9_.]+@', '', $transliterated);

    return $transliterated;
  }

  /**
   * Wraps the transliteration service.
   */
  protected function transliteration() {
    if (!$this->transliteration) {
      $this->transliteration = \Drupal::transliteration();
    }
    return $this->transliteration;
  }

  /**
   * Sets the transliteration service.
   *
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   The transliteration service.
   */
  public function setTransliteration(TransliterationInterface $transliteration) {
    $this->transliteration = $transliteration;
  }

}
