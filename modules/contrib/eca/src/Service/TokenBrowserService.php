<?php

namespace Drupal\eca\Service;


use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Service class for Token Browser in ECA.
 */
class TokenBrowserService {

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * @var array
   */
  protected array $tokenTypes = [];

  /**
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   */
  public function __construct(ModuleHandlerInterface $moduleHandler, EntityTypeManagerInterface $entityTypeManager) {
    $this->moduleHandler = $moduleHandler;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Returns the markup needed for the token browser.
   *
   * @return array the markup
   */
  public function getTokenBrowserMarkup(): array {
    if (!$this->moduleHandler->moduleExists('token')) {
      return [];
    }

    if(!$this->tokenTypes) {
      $this->tokenTypes = $this->getTokenTypes();
    }

    return [
      'tb' => [
        '#type' => 'container',
        '#theme' => 'token_tree_link',
        '#token_types' => $this->tokenTypes,
      ],
    ];
  }

  /**
   * Gets all token types based on all available entity types.
   *
   * @return array
   */
  private function getTokenTypes(): array {
    $tokenTypes = [];
    foreach($this->entityTypeManager->getDefinitions() as $definition) {
      $tokenTypes[] = $definition->get('id');
    }
    return $tokenTypes;
  }

}
