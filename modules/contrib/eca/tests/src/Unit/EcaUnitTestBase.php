<?php

namespace Drupal\Tests\eca\Unit;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\UnitTestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * The test base call for unit tests in module Eca.
 */
abstract class EcaUnitTestBase extends UnitTestCase {

  /**
   * Mock of an entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
  }

  /**
   * Get private or protected method.
   *
   * @param string $class
   * @param string $method
   *
   * @return  ReflectionMethod
   * @throws \ReflectionException
   */
  protected function getPrivateMethod(string $class, string $method): ReflectionMethod {
    $reflector = new ReflectionClass($class);
    $method = $reflector->getMethod($method);
    $method->setAccessible(TRUE);
    return $method;
  }

  /**
   * Get private or protected property.
   *
   * @param string $className
   * @param string $propertyName
   *
   * @return  ReflectionProperty
   * @throws \ReflectionException
   */
  protected function getPrivateProperty(string $className, string $propertyName): ReflectionProperty {
    $reflector = new ReflectionClass($className);
    $property = $reflector->getProperty($propertyName);
    $property->setAccessible(TRUE);
    return $property;
  }

}