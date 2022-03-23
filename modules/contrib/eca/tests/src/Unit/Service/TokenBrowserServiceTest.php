<?php

namespace Drupal\Tests\eca\Unit\Service;

use Drupal\Core\Entity\EntityType;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\eca\Service\TokenBrowserService;
use Drupal\Tests\eca\Unit\EcaUnitTestBase;

/**
 * Tests to token browser service.
 *
 * @group eca
 */
class TokenBrowserServiceTest extends EcaUnitTestBase {

  public function testTokenModuleNotInstalled(): void {
    $moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $tokenBrowserService = new TokenBrowserService($moduleHandler, $this->entityTypeManager);
    $this->assertEquals([], $tokenBrowserService->getTokenBrowserMarkup());
  }

  /**
   * Tests the method getTokenBrowserMarkup.
   *
   * @throws \Drupal\Core\Entity\Exception\EntityTypeIdLengthException
   */
  public function testGetMarkup(): void {
    $moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $moduleHandler->expects($this->once())->method('moduleExists')
      ->with('token')->willReturn(TRUE);

    $entityTypes[] = new EntityType(['id' => 'node',]);
    $entityTypes[] = new EntityType(['id' => 'comment',]);
    $entityTypes[] = new EntityType(['id' => 'file',]);

    $this->entityTypeManager->expects($this->once())->method('getDefinitions')
      ->willReturn($entityTypes);
    $tokenBrowserService = new TokenBrowserService($moduleHandler,  $this->entityTypeManager);

    $markup = [
      'tb' => [
        '#type' => 'container',
        '#theme' => 'token_tree_link',
        '#token_types' => ['node', 'comment', 'file'],
      ],
    ];
    $this->assertEquals($markup, $tokenBrowserService->getTokenBrowserMarkup());
  }

}