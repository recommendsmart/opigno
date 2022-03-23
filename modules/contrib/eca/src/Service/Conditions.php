<?php

namespace Drupal\eca\Service;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\eca\Plugin\ECA\Condition\ConditionInterface;
use Drupal\eca\PluginManager\Condition;
use Drupal\eca\Token\TokenInterface;
use Drupal\user\Entity\User;

/**
 * Service class for Drupal core conditions in ECA.
 */
class Conditions {

  use ServiceTrait;

  public const OPTION_NO = 'no';
  public const OPTION_YES = 'yes';

  public const GATEWAY_TYPE_EXCLUSIVE = 0;
  public const GATEWAY_TYPE_PARALLEL = 1;
  public const GATEWAY_TYPE_INCLUSIVE = 2;
  public const GATEWAY_TYPE_COMPLEX = 3;
  public const GATEWAY_TYPE_EVENTBASED = 4;

  /**
   * @var \Drupal\eca\PluginManager\Condition
   */
  protected Condition $conditionManager;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $currentUser;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * @var \Drupal\eca\Token\TokenInterface
   */
  protected TokenInterface $token;

  /**
   * Conditions constructor.
   *
   * @param \Drupal\eca\PluginManager\Condition $condition_manager
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Session\AccountProxyInterface $account_proxy
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   * @param \Drupal\eca\Token\TokenInterface $token
   */
  public function __construct(Condition $condition_manager, LoggerChannelInterface $logger, EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $account_proxy, LanguageManagerInterface $language_manager, TokenInterface $token) {
    $this->conditionManager = $condition_manager;
    $this->logger = $logger;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = User::load($account_proxy->id());
    $this->languageManager = $language_manager;
    $this->token = $token;
  }

  /**
   * Returns a sorted list of condition plugins.
   *
   * @return \Drupal\eca\Plugin\ECA\Condition\ConditionInterface[]
   *   The sorted list of consitions.
   */
  public function conditions(): array {
    static $actions;
    if ($actions === NULL) {
      $actions = [];
      foreach ($this->conditionManager->getDefinitions() as $plugin_id => $definition) {
        try {
          $actions[] = $this->conditionManager->createInstance($plugin_id);
        } catch (PluginException $e) {
          // Can be ignored.
        }
      }
    }
    $this->sortPlugins($actions);
    return $actions;
  }

  /**
   * Prepares all the fields of an action plugin for modellers.
   *
   * @param \Drupal\eca\Plugin\ECA\Condition\ConditionInterface $condition
   *   The condition plugin for which the fields need to be prepared.
   *
   * @return array
   *   The list of fields for this condition.
   */
  public function fields(ConditionInterface $condition): array {
    $fields = [];
    if ($config = $condition->defaultConfiguration()) {
      $this->prepareConfigFields($fields, $config, $condition);
    }

    /** @var \Drupal\Core\Plugin\Context\ContextDefinition $definition */
    foreach ($condition->getPluginDefinition()['context_definitions'] ?? [] as $key => $definition) {
      $fields[] = [
        'name' => $key,
        'label' => $definition->getLabel(),
        'type' => 'String',
        'value' => '',
      ];
    }

    return $fields;
  }

  /**
   * @param \Drupal\Component\EventDispatcher\Event $event
   * @param string|bool $condition_id
   * @param array|null $condition
   * @param array $context
   *
   * @return bool
   */
  public function assertCondition(Event $event, $condition_id, ?array $condition, array $context): bool {
    if (empty($condition_id)) {
      $this->logger->info('Unconditional %successorlabel (%successorid) from ECA %ecalabel (%ecaid) for event %event.', $context);
      return TRUE;
    }
    if ($condition === NULL) {
      $this->logger->error('Non existant condition %conditionid for %successorlabel from ECA %ecalabel (%ecaid) for event %event.', $context);
      return FALSE;
    }
    try {
      /** @var \Drupal\eca\Plugin\ECA\Condition\ConditionInterface $plugin */
      $plugin = $this->conditionManager->createInstance($condition['plugin'], $condition['fields']);
    }
    catch (PluginException $e) {
      // Deliberately ignored, handled below already.
   }
    if (isset($plugin)) {
      // If a config value is an array, we may receive a string from the
      // modeller and have to convert this into an array.
      $pluginConfig = $plugin->getConfiguration();
      $defaultConfig = $plugin->defaultConfiguration();
      foreach ($pluginConfig as $key => $value) {
        if (isset($defaultConfig[$key]) && is_array($defaultConfig[$key])) {
          $pluginConfig[$key] = explode(',', $value);
        }
      }
      $plugin->setConfiguration($pluginConfig);

      if ($plugin instanceof ConditionInterface) {
        $plugin->setEvent($event);
      }
      /** @var \Drupal\Core\Plugin\Context\ContextDefinition $definition */
      foreach ($plugin->getPluginDefinition()['context_definitions'] ?? [] as $key => $definition) {
        // If the field for this context is filled by the model, then use that.
        // Otherwise fall back to the entity of the original event of the
        // current process.
        if (empty($pluginConfig[$key])) {
          switch ($definition->getDataType()) {
            case 'entity:user':
              $token = $this->currentUser;
              break;

            case 'language':
              $token = $this->languageManager->getCurrentLanguage();
              break;

            default:
              $token = 'entity';
          }
        }
        else {
          $token = $pluginConfig[$key];
        }
        if (is_string($token)) {
          $data = $this->token->getTokenData($token);
        }
        else {
          $data = $token;
        }
        try {
          $plugin->setContextValue($key, $data);
        }
        catch (ContextException $e) {
          $this->logger->error('Invalid context data for condition %conditionid for %successorlabel from ECA %ecalabel (%ecaid) for event %event.', $context);
        }
      }
      if ($plugin->reset()->evaluate()) {
        $this->logger->info('Asserted condition %conditionid for %successorlabel from ECA %ecalabel (%ecaid) for event %event.', $context);
        return TRUE;
      }
      $this->logger->info('Not asserting condition %conditionid for %successorlabel from ECA %ecalabel (%ecaid) for event %event.', $context);
    }
    else {
      $this->logger->error('Invalid condition %conditionid for %successorlabel from ECA %ecalabel (%ecaid) for event %event.', $context);
    }
    return FALSE;
  }

}
