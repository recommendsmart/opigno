<?php

namespace Drupal\flow\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\flow\Helpers\ConfigurablePluginTrait;
use Drupal\flow\Helpers\PluginWithSettingsTrait;
use Drupal\flow\Helpers\ThirdPartySettingsTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Flow task plugins.
 */
abstract class FlowTaskBase extends PluginBase implements FlowTaskInterface, ContainerFactoryPluginInterface {

  use ConfigurablePluginTrait;
  use DependencySerializationTrait;
  use PluginWithSettingsTrait;
  use ThirdPartySettingsTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
    if (!isset($this->configuration['settings'])) {
      $this->configuration['settings'] = [];
    }
    if (!isset($this->configuration['third_party_settings'])) {
      $this->configuration['third_party_settings'] = [];
    }
    $this->settings = &$this->configuration['settings'];
    $this->thirdPartySettings = &$this->configuration['third_party_settings'];
  }

}
