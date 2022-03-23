<?php

namespace Drupal\Tests\eca_content\Unit;

use Drupal\Core\Entity\EntityInterface;
use Drupal\eca_content\EntityTypeTrait;
use PHPUnit\Framework\TestCase;

/**
 * Tests the entity type trait.
 *
 * @group eca
 * @group eca_content
 */
class EntityTypeTraitTest extends TestCase {

  use EntityTypeTrait;

  /**
   * Tests method with all types.
   *
   * @return void
   */
  public function testBundleFieldAppliesAllTypes(): void {
    $this->assertTrue($this->bundleFieldApplies($this->createMock(EntityInterface::class),
      '_all'));
  }

  /**
   * Tests method with all bundles.
   *
   * @return void
   */
  public function testBundleFieldAppliesAllBundle(): void {
    $entity = $this->createMock(EntityInterface::class);
    $entity->expects($this->once())->method('getEntityTypeId')
      ->willReturn('test');
    $this->assertTrue($this->bundleFieldApplies($entity,'test _all'));
  }

  /**
   * Tests method with equal types and bundle.
   *
   * @return void
   */
  public function testBundleFieldApplies(): void {
    $entity = $this->createMock(EntityInterface::class);
    $entity->expects($this->once())->method('getEntityTypeId')
      ->willReturn('test_id');
    $entity->expects($this->once())->method('bundle')
      ->willReturn('test_bundle');
    $this->assertTrue($this->bundleFieldApplies($entity,'test_id test_bundle'));
  }

  /**
   * Tests method with non-equal bundle.
   *
   * @return void
   */
  public function testBundleFieldAppliesFalse(): void {
    $entity = $this->createMock(EntityInterface::class);
    $entity->expects($this->once())->method('getEntityTypeId')
      ->willReturn('test_id');
    $entity->expects($this->once())->method('bundle')
      ->willReturn('bundle');
    $this->assertFalse($this->bundleFieldApplies($entity,'test_id test_bundle'));
  }
}
