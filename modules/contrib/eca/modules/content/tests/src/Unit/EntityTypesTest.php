<?php

namespace Drupal\Tests\eca_content\Unit;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eca_content\Service\EntityTypes;
use PHPUnit\Framework\TestCase;

/**
 * Tests the entity type trait.
 *
 * @group eca
 * @group eca_content
 */
class EntityTypesTest extends TestCase {

  /**
   * The entity type manager mock.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The entity type bundle info mock.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected EntityTypeBundleInfoInterface $entityTypeBundleInfo;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeBundleInfo = $this->createMock(EntityTypeBundleInfoInterface::class);
  }

  /**
   * Tests the method bundleField without content entity types.
   */
  public function testBundleFieldWithoutTypes(): void {
    $this->entityTypeManager->expects($this->once())
      ->method('getDefinitions')->willReturn([]);
    $entityTypeHelper = new EntityTypes($this->entityTypeManager, $this->entityTypeBundleInfo);
    $this->assertEquals([], $entityTypeHelper->bundleField());
  }

  /**
   * Tests the method bundleField without content entity types and include any.
   */
  public function testBundleFieldWithoutTypesIncludeAny(): void {
    $expected = [
      'name' => 'type',
      'label' => 'Type (and bundle)',
      'type' => 'Dropdown',
      'value' => '_all',
      'extras' => [
        'choices' => [
          0 => [
            'name' => '- any -',
            'value' => '_all',
          ],
        ],
      ],
    ];
    $this->entityTypeManager->expects($this->once())
      ->method('getDefinitions')->willReturn([]);
    $entityTypeHelper = new EntityTypes($this->entityTypeManager, $this->entityTypeBundleInfo);
    $this->assertEquals($expected, $entityTypeHelper->bundleField(TRUE));
  }

  /**
   * Tests the method bundleField with entity types and include any bundles.
   */
  public function testBundleFieldWithTypesIncludeAnyBundles(): void {
    $expected = [
      'name' => 'type',
      'label' => 'Type (and bundle)',
      'type' => 'Dropdown',
      'value' => 'Comment _all',
      'extras' => [
        'choices' => [
          0 => [
            'name' => 'Comment: - any -',
            'value' => 'Comment _all',
          ],
          1 => [
            'name' => 'Comment: Article',
            'value' => 'Comment bundleKey2',
          ],
          2 => [
            'name' => 'Comment: Node',
            'value' => 'Comment bundleKey1',
          ],
          3 => [
            'name' => 'Content: - any -',
            'value' => 'Content _all',
          ],
          4 => [
            'name' => 'Content: Article',
            'value' => 'Content bundleKey2',
          ],
          5 => [
            'name' => 'Content: Node',
            'value' => 'Content bundleKey1',
          ],
        ],
      ],
    ];
    $labels = [
      'Content' => 'Content',
      'Comment' => 'Comment',
    ];

    $this->entityTypeManager->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($this->getContentEntityTypesByLabels($labels));
    $entityTypeHelper = new EntityTypes($this->entityTypeManager, $this->entityTypeBundleInfo);
    $this->assertEquals($expected, $entityTypeHelper->bundleField());
  }

  /**
   * Tests the method bundleField.
   *
   * <p>Include content entity types and without the flag any bundles.</p>
   */
  public function testBundleFieldWithoutAnyBundlesFlag(): void {
    $expected = [
      'name' => 'type',
      'label' => 'Type (and bundle)',
      'type' => 'Dropdown',
      'value' => 'Comment bundleKey2',
      'extras' => [
        'choices' => [
          0 => [
            'name' => 'Comment: Article',
            'value' => 'Comment bundleKey2',
          ],
          1 => [
            'name' => 'Comment: Node',
            'value' => 'Comment bundleKey1',
          ],
          2 => [
            'name' => 'Content: Article',
            'value' => 'Content bundleKey2',
          ],
          3 => [
            'name' => 'Content: Node',
            'value' => 'Content bundleKey1',
          ],
        ],
      ],
    ];
    $labels = [
      'Content' => 'Content',
      'Comment' => 'Comment',
    ];

    $this->entityTypeManager->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($this->getContentEntityTypesByLabels($labels));
    $entityTypeHelper = new EntityTypes($this->entityTypeManager, $this->entityTypeBundleInfo);
    $this->assertEquals($expected, $entityTypeHelper->bundleField(FALSE, FALSE));
  }

  /**
   * Tests the method bundleField.
   *
   * <p>Include content entity types and without the flag any bundles.</p>
   */
  public function testBundleFieldNoBundles(): void {
    $expected = [
      'name' => 'type',
      'label' => 'Type (and bundle)',
      'type' => 'Dropdown',
      'value' => 'Comment _all',
      'extras' => [
        'choices' => [
          0 => [
            'name' => 'Comment: - any -',
            'value' => 'Comment _all',
          ],
          1 => [
            'name' => 'Content: - any -',
            'value' => 'Content _all',
          ],
        ],
      ],
    ];
    $labels = [
      'Content' => 'Content',
      'Comment' => 'Comment',
    ];

    $this->entityTypeManager->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($this->getContentEntityTypesByLabels($labels, FALSE));
    $entityTypeHelper = new EntityTypes($this->entityTypeManager, $this->entityTypeBundleInfo);
    $this->assertEquals($expected, $entityTypeHelper->bundleField());
  }

  /**
   * Gets the content types.
   *
   * @param array $labels
   *   The labels.
   * @param bool $includeBundleKey
   *   The key.
   *
   * @return array
   *   The content entity types.
   */
  private function getContentEntityTypesByLabels(array $labels, bool $includeBundleKey = TRUE): array {
    $entityTypes = [];
    foreach ($labels as $key => $label) {
      $entityType = $this->createMock(ContentEntityTypeInterface::class);
      $entityType->method('id')->willReturn($key);
      $entityType->method('getLabel')->willReturn($label);
      $bundles = [
        'bundleKey1' => [
          'label' => 'Node',
        ],
        'bundleKey2' => [
          'label' => 'Article',
        ],
      ];
      $entityKeys = [];
      if ($includeBundleKey) {
        $entityKeys = ['bundle' => 'test'];
      }
      $entityType->method('get')->with('entity_keys')
        ->willReturn($entityKeys);
      $this->entityTypeBundleInfo->method('getBundleInfo')
        ->willReturn($bundles);

      $entityTypes[] = $entityType;
    }
    return $entityTypes;
  }

  /**
   * Tests method with all types.
   */
  public function testBundleFieldAppliesAllTypes(): void {
    $entityTypeHelper = new EntityTypes($this->entityTypeManager, $this->entityTypeBundleInfo);
    $this->assertTrue($entityTypeHelper->bundleFieldApplies($this->createMock(EntityInterface::class),
      '_all'));
  }

  /**
   * Tests method with all bundles.
   */
  public function testBundleFieldAppliesAllBundle(): void {
    $entityTypeHelper = new EntityTypes($this->entityTypeManager, $this->entityTypeBundleInfo);
    $entity = $this->createMock(EntityInterface::class);
    $entity->expects($this->once())->method('getEntityTypeId')
      ->willReturn('test');
    $this->assertTrue($entityTypeHelper->bundleFieldApplies($entity, 'test _all'));
  }

  /**
   * Tests method with equal types and bundle.
   */
  public function testBundleFieldApplies(): void {
    $entityTypeHelper = new EntityTypes($this->entityTypeManager, $this->entityTypeBundleInfo);
    $entity = $this->createMock(EntityInterface::class);
    $entity->expects($this->once())->method('getEntityTypeId')
      ->willReturn('test_id');
    $entity->expects($this->once())->method('bundle')
      ->willReturn('test_bundle');
    $this->assertTrue($entityTypeHelper->bundleFieldApplies($entity, 'test_id test_bundle'));
  }

  /**
   * Tests method with non-equal bundle.
   */
  public function testBundleFieldAppliesFalse(): void {
    $entityTypeHelper = new EntityTypes($this->entityTypeManager, $this->entityTypeBundleInfo);
    $entity = $this->createMock(EntityInterface::class);
    $entity->expects($this->once())->method('getEntityTypeId')
      ->willReturn('test_id');
    $entity->expects($this->once())->method('bundle')
      ->willReturn('bundle');
    $this->assertFalse($entityTypeHelper->bundleFieldApplies($entity, 'test_id test_bundle'));
  }

}
