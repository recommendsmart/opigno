<?php

namespace Drupal\Tests\eca_misc\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Kernel tests for the "eca_route_match" condition plugin.
 *
 * @group eca
 * @group eca_misc
 */
class RouteMatchTest extends KernelTestBase {

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
  public function testRoutes(): void {
  }

}
