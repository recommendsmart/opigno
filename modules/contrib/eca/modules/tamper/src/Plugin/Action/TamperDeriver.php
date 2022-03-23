<?php

namespace Drupal\eca_tamper\Plugin\Action;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\tamper\TamperManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Derive any Tamper plugin into an ECA action.
 */
class TamperDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The tamper plugin manager.
   *
   * @var \Drupal\tamper\TamperManagerInterface
   */
  protected TamperManagerInterface $tamperManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    $plugin = new static();
    $plugin->tamperManager = $container->get('plugin.manager.tamper');
    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition): array {
    $this->derivatives = [];
    foreach ($this->tamperManager->getDefinitions() as $defintion) {
      $this->derivatives[$defintion['id']] = [
        'id' => 'eca_tamper:' . $defintion['id'],
        'label' => $this->t('Tamper: @label', ['@label' => $defintion['label']->render()]),
        'description' => $defintion['description'],
        'category' => $defintion['category'],
        'tamper_plugin' => $defintion['id'],
      ] + $base_plugin_definition;
    }
    return $this->derivatives;
  }

}
