<?php

namespace Drupal\Tests\eca\Unit;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\eca\EcaState;

/**
 * Tests to EcaState class.
 *
 * @group eca
 */
class EcaStateTest extends EcaUnitTestBase {

  private const TEST_KEY = 'test_key';

  /**
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  protected KeyValueFactoryInterface $keyValueFactory;

  /**
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected KeyValueStoreInterface $keyValueStore;

  /**
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected TimeInterface $time;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->keyValueFactory = $this->createMock(KeyValueFactoryInterface::class);
    $this->keyValueStore = $this->createMock(KeyValueStoreInterface::class);
    $this->time = $this->createMock(TimeInterface::class);
  }

  /**
   * Tests if the timestamp has expired.
   *
   * @return void
   */
  public function testIfTimestampHasExpired(): void {
    $storedTimestamp = 1515506400; //2018/01/09 15:00:00
    $currentTimestamp = 1515510000; //2018/01/09 16:00:00

    $this->keyValueStore->expects($this->once())->method('getMultiple')
      ->willReturn([
        'timestamp.' . self::TEST_KEY => $storedTimestamp
      ]);
    $this->keyValueFactory->expects($this->once())->method('get')
      ->with('eca')->willReturn($this->keyValueStore);

    $this->time->expects($this->exactly(3))->method('getRequestTime')
      ->willReturn($currentTimestamp);

    $ecaState = new EcaState($this->keyValueFactory, $this->time);
    $this->assertEquals($currentTimestamp, $ecaState->getCurrentTimestamp());
    $this->assertTrue($ecaState->hasTimestampExpired(self::TEST_KEY, 3599));
    $this->assertFalse($ecaState->hasTimestampExpired(self::TEST_KEY, 3600));
  }

  /**
   * Tests timestampKey method.
   *
   * @throws \ReflectionException
   */
  public function testTimestampKey(): void {
    $ecaState = new EcaState($this->keyValueFactory, $this->time);
    $result = $this->getPrivateMethod(EcaState::class, 'timestampKey')
      ->invokeArgs($ecaState, [self::TEST_KEY]);

    $this->assertEquals('timestamp.test_key', $result);
  }

  /**
   * Tests the get and set methods.
   *
   * @return void
   */
  public function testGetterAndSetter(): void {
    $currentTimestamp = 1515510000; //2018/01/09 16:00:00
    $this->time->expects($this->once())->method('getRequestTime')
      ->willReturn($currentTimestamp);
    $this->keyValueFactory->expects($this->once())->method('get')
      ->with('eca')->willReturn($this->keyValueStore);
    $ecaState = new EcaState($this->keyValueFactory, $this->time);
    $ecaState->setTimestamp(self::TEST_KEY);
    $this->assertEquals($currentTimestamp, $ecaState->getTimestamp(self::TEST_KEY));
  }

}
