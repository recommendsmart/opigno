<?php

namespace Drupal\flow_action\Plugin\flow\Derivative\Task;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\flow\Plugin\flow\Derivative\ContentDeriverBase;
use Drupal\flow_action\Helpers\ActionManagerTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Task plugin deriver for Action plugins.
 *
 * @see \Drupal\flow_action\Plugin\flow\Task\Action
 */
class ActionDeriver extends ContentDeriverBase {

  use ActionManagerTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    /** @var \Drupal\flow_action\Plugin\flow\Derivative\Task\ActionDeriver $instance */
    $instance = parent::create($container, $base_plugin_id);
    $instance->setStringTranslation($container->get('string_translation'));
    $instance->setActionManager($container->get(self::$actionManagerServiceName));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $content_derivatives = parent::getDerivativeDefinitions($base_plugin_definition);
    $action_definitions = $this->actionManager->getDefinitions();
    $action_derivatives = [];
    foreach ($content_derivatives as $content_id => &$content_derivative) {
      foreach ($action_definitions as $action_plugin_id => $action_plugin_definition) {
        $derivative_id = $content_id . '::' . $action_plugin_id;
        $action_derivatives[$derivative_id] = [
          'label' => $this->t('Execute @action on @content', [
            '@action' => $action_plugin_definition['label'],
            '@content' => $content_derivative['label'],
          ]),
        ] + $content_derivative;
      }
    }
    return $action_derivatives;
  }

}
