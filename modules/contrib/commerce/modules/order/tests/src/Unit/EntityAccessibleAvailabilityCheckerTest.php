<?php

namespace Drupal\Tests\commerce_order\Unit;

use Drupal\commerce\Context;
use Drupal\commerce_order\AvailabilityResult;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_order\EntityAccessibleAvailabilityChecker;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\commerce_order\EntityAccessibleAvailabilityChecker
 * @group commerce
 */
class EntityAccessibleAvailabilityCheckerTest extends UnitTestCase {

  /**
   * @covers ::applies
   */
  public function testApplies() {
    $checker = new EntityAccessibleAvailabilityChecker();

    $purchasable_entity = $this->prophesize(PurchasableEntityInterface::class);
    $order_item = $this->prophesize(OrderItemInterface::class);
    $order_item->hasPurchasedEntity()->willReturn(TRUE);
    $order_item->getPurchasedEntity()->willReturn($purchasable_entity);
    $this->assertTrue($checker->applies($order_item->reveal()));

    $order_item->hasPurchasedEntity()->willReturn(FALSE);
    $this->assertFalse($checker->applies($order_item->reveal()));

    $order_item->hasPurchasedEntity()->willReturn(TRUE);
    $order_item->getPurchasedEntity()->willReturn(NULL);
    $this->assertFalse($checker->applies($order_item->reveal()));
  }

  /**
   * @covers ::check
   */
  public function testCheck() {
    $checker = new EntityAccessibleAvailabilityChecker();
    $context = new Context(
      $this->prophesize(UserInterface::class)->reveal(),
      $this->prophesize(StoreInterface::class)->reveal()
    );

    // Purchasable entity doesn't implement the EntityPublishedInterface,
    // hence the isPublished() check will be skipped.
    $purchasable_entity = $this->prophesize(PurchasableEntityInterface::class);
    $purchasable_entity->access('view', Argument::type(AccountInterface::class))->willReturn(TRUE);
    $order_item = $this->prophesize(OrderItemInterface::class);
    $order_item->getPurchasedEntity()->willReturn($purchasable_entity);
    $this->assertEquals(AvailabilityResult::neutral(), $checker->check($order_item->reveal(), $context));
    $purchasable_entity->access('view', Argument::type(AccountInterface::class))->willReturn(FALSE);
    $order_item->getPurchasedEntity()->willReturn($purchasable_entity);
    $this->assertEquals(AvailabilityResult::unavailable(), $checker->check($order_item->reveal(), $context));

    $purchasable_entity = $this->prophesize(ProductVariationInterface::class);
    $purchasable_entity->access('view', Argument::type(AccountInterface::class))->willReturn(TRUE);
    $purchasable_entity->isPublished()->willReturn(TRUE);
    $order_item = $this->prophesize(OrderItemInterface::class);
    $order_item->getPurchasedEntity()->willReturn($purchasable_entity);
    $this->assertEquals(AvailabilityResult::neutral(), $checker->check($order_item->reveal(), $context));
    $purchasable_entity->isPublished()->willReturn(FALSE);
    $order_item->getPurchasedEntity()->willReturn($purchasable_entity);
    $this->assertEquals(AvailabilityResult::unavailable(), $checker->check($order_item->reveal(), $context));

    $purchasable_entity->isPublished()->willReturn(TRUE);
    $purchasable_entity->access('view', Argument::type(AccountInterface::class))->willReturn(FALSE);
    $this->assertEquals(AvailabilityResult::unavailable(), $checker->check($order_item->reveal(), $context));
  }

}
