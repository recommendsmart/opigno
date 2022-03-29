<?php

namespace Drupal\Tests\eca_misc\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Kernel tests for the "eca_token_load_route_param" action plugin.
 *
 * @group eca
 * @group eca_misc
 */
class TokenLoadRouteParameterTest extends KernelTestBase {

  protected static $modules = [
    'system',
    'user',
    'field',
    'filter',
    'text',
    'node',
    'eca',
    'eca_content',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig(static::$modules);
    User::create(['uid' => 1, 'name' => 'admin'])->save();
  }

  /**
   * Tests putting node from route to token system.
   */
  public function testNodeRoute(): void {
  }

}
