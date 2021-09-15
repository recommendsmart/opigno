<?php

namespace Drupal\arch_product\ConfigTranslation;

use Drupal\config_translation\ConfigEntityMapper;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides a configuration mapper for product types.
 */
class ProductTypeMapper extends ConfigEntityMapper {

  /**
   * {@inheritdoc}
   */
  public function setEntity(ConfigEntityInterface $entity) {
    parent::setEntity($entity);

    // Adds the title label to the translation form.
    $product_type = $entity->id();
    $config = $this->configFactory->get("core.base_field_override.product.$product_type.title");
    if (!$config->isNew()) {
      $this->addConfigName($config->getName());
    }
  }

}
