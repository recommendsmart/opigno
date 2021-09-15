<?php

namespace Drupal\arch_product\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for product type deletion.
 *
 * @internal
 */
class ProductTypeDeleteConfirm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $num_products = $this->entityTypeManager->getStorage('product')->getQuery()
      ->condition('type', $this->entity->id())
      ->count()
      ->execute();
    if ($num_products) {
      $caption = '<p>' . $this->formatPlural(
        $num_products,
        '%type is used by 1 piece of product on your site. You can not remove this product type until you have removed all of the %type products.',
        '%type is used by @count pieces of product on your site. You may not remove %type until you have removed all of the %type products.',
        ['%type' => $this->entity->label()]
        ) . '</p>';
      $form['#title'] = $this->getQuestion();
      $form['description'] = ['#markup' => $caption];
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

}
