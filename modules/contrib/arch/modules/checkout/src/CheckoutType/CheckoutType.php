<?php

namespace Drupal\arch_checkout\CheckoutType;

use Drupal\arch_cart\Cart\CartHandlerInterface;
use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginAssignmentTrait;
use Drupal\Core\Plugin\ContextAwarePluginBase;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\Core\Plugin\PluginWithFormsTrait;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a base checkout type implementation that most plugins will extend.
 *
 * This abstract class provides the generic panel configuration form, default
 * panel settings, and handling for general user-defined block visibility
 * settings.
 */
abstract class CheckoutType extends ContextAwarePluginBase implements ContainerFactoryPluginInterface, CheckoutTypePluginInterface, PluginWithFormsInterface {

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
   * Cart instance.
   *
   * @var \Drupal\arch_cart\Cart\CartInterface
   */
  protected $cart;

  /**
   * Form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    CartHandlerInterface $cart_handler,
    FormBuilderInterface $form_builder,
    LanguageManagerInterface $language_manager,
    ConfigFactoryInterface $config_factory
  ) {
    $this->cart = $cart_handler->getCart();
    $this->formBuilder = $form_builder;
    $this->languageManager = $language_manager;
    $this->configFactory = $config_factory;
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $this->setConfiguration($configuration);
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
      $container->get('arch_cart_handler'),
      $container->get('form_builder'),
      $container->get('language_manager'),
      $container->get('config.factory')
    );
  }

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
  public function getCheckoutFormClass() {
    $definition = $this->getPluginDefinition();
    return $definition['form_class'];
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
   * Returns generic default configuration for CheckoutType plugins.
   *
   * @return array
   *   An associative array with the default configuration.
   */
  protected function baseConfigurationDefaults() {
    return [];
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
    $access = AccessResult::allowed();
    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if ($this->cart->getCount() < 1) {
      return $this->buildEmptyCartMessage();
    }

    return $this->buildForm();
  }

  /**
   * {@inheritdoc}
   */
  public function buildEmptyCartMessage() {
    $this->messenger()->addError(
      $this->t('To checkout, please place a product first to your shopping cart.', [], ['context' => 'arch_onepage'])
    );

    // @todo Figure out something good for here.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm() {
    // Get the raw form in its original state.
    $form_state = new FormState();

    $form = $this->formBuilder->buildForm(
      $this->getCheckoutFormClass(),
      $form_state
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * Creates a generic configuration form for all checkout types. Individual
   * checkout type plugins can add elements to this form by overriding
   * CheckoutType::panelForm(). Most block plugins should not
   * override this method unless they need to alter the generic form elements.
   *
   * @see \Drupal\arch_checkout\CheckoutType\CheckoutType::blockForm()
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\arch_checkout\CheckoutType\CheckoutType::settingsFormValidate()
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

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
  }

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
