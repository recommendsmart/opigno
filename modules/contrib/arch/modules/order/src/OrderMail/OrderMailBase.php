<?php

namespace Drupal\arch_order\OrderMail;

use Drupal\arch_order\Entity\OrderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base order mail.
 *
 * @package Drupal\arch_order\OrderMail
 */
abstract class OrderMailBase extends PluginBase implements OrderMailInterface, ContainerFactoryPluginInterface {

  /**
   * Site default language.
   *
   * @var \Drupal\Core\Language\LanguageInterface
   */
  private $defaultLanguage;

  /**
   * Mail key-value store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $store;

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
   * A fallback send-to value.
   *
   * @var string
   */
  protected $fallbackSendTo;

  /**
   * Constructs a \Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory
   *   Key value factory.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    KeyValueFactoryInterface $key_value_factory,
    LanguageManagerInterface $language_manager,
    ConfigFactoryInterface $config_factory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->store = $key_value_factory->get('arch_order_mail.' . $this->getPluginId());
    $this->languageManager = $language_manager;
    $this->defaultLanguage = $this->languageManager->getDefaultLanguage();
    $this->configFactory = $config_factory;

    $this->fallbackSendTo = $this->configFactory->get('system.site')->get('mail');
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
      $container->get('keyvalue'),
      $container->get('language_manager'),
      $container->get('config.factory')
    );
  }

  /**
   * Translation existing check.
   *
   * @param string $langcode
   *   Language code.
   *
   * @return bool
   *   True if the translation exists.
   */
  public function translationIsExists($langcode) {
    $langcode = strtolower(trim($langcode));
    $languageList = $this->getLanguageList();

    return in_array($langcode, $languageList);
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguageList() {
    return $this->store->get('languages', []);
  }

  /**
   * {@inheritdoc}
   */
  public function getSubject($langcode = NULL) {
    return $this->getPart('subject', $langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function getBody($langcode = NULL) {
    $body = $this->getPart('body', $langcode);
    if (empty($body)) {
      $body = [
        'format' => '',
        'value' => '',
      ];
    }
    return $body;
  }

  /**
   * {@inheritdoc}
   */
  public function setSubject($langcode, $text) {
    $this->setPart('subject', $langcode, $text);
  }

  /**
   * {@inheritdoc}
   */
  public function setBody($langcode, array $text) {
    $this->setPart('body', $langcode, $text);
  }

  /**
   * {@inheritdoc}
   */
  public function setTranslation($langcode, $subject, array $body) {
    $this->addLanguage($langcode);
    $this->setPart('subject', $langcode, $subject);
    $this->setPart('body', $langcode, $body);
  }

  /**
   * {@inheritdoc}
   */
  public function removeTranslation($langcode) {
    $langcode = strtolower(trim($langcode));
    $list = $this->getLanguageList();

    $index = array_search($langcode, $list);
    if ($index === FALSE) {
      return FALSE;
    }

    unset($list[$index]);
    sort($list);
    $this->store->set('languages', $list);

    $this->deletePart('body', $langcode);
    $this->deletePart('subject', $langcode);

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->store->set('enabled', (bool) $status);
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return $this->store->get('enabled', TRUE);
  }

  /**
   * Set part of mail.
   *
   * @param string $part
   *   Mail part name (subject, body).
   * @param string $langcode
   *   Language code.
   * @param string|array $text
   *   Content.
   */
  private function setPart($part, $langcode, $text) {
    $langcode = strtolower(trim($langcode));

    $this->store->set($part . '_' . $langcode, $text);
  }

  /**
   * Delete part of mail.
   *
   * @param string $part
   *   Mail part name (subject, body).
   * @param string $langcode
   *   Language code.
   */
  private function deletePart($part, $langcode) {
    $this->store->delete($part . '_' . $langcode);
  }

  /**
   * Get part of mail.
   *
   * @param string $part
   *   Mail part name (subject, body).
   * @param string|null $langcode
   *   Language code. Use the site default language if null,
   *   or the part is not available in the given language.
   *
   * @return mixed|null
   *   Part of mail.
   */
  private function getPart($part, $langcode = NULL) {
    $text = NULL;

    if (!empty($langcode)) {
      $langcode = strtolower(trim($langcode));
      $text = $this->store->get($part . '_' . $langcode);
    }

    if (empty($text)) {
      $text = $this->store->get($part . '_' . $this->defaultLanguage->getId());
    }

    return $text;
  }

  /**
   * Add language code to available languages list.
   *
   * @param string $langcode
   *   Language code.
   *
   * @return bool
   *   Return with false if the language code available in the list,
   *   or the langcode is not valid.
   */
  protected function addLanguage($langcode) {
    $langcode = strtolower(trim($langcode));
    $list = $this->getLanguageList();

    if (
      in_array($langcode, $list)
      || $this->languageManager->getLanguage($langcode) === NULL
    ) {
      return FALSE;
    }

    $list[] = $langcode;
    sort($list);
    $this->store->set('languages', $list);

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function sendTo(OrderInterface $order): string {
    return $this->fallbackSendTo;
  }

}
