<?php

namespace Drupal\arch_product\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form for rebuilding permissions.
 *
 * @internal
 */
class RebuildPermissionsForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'product_configure_rebuild_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to rebuild the permissions on products?', [], ['context' => 'arch_product']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('system.status');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Rebuild product permissions', [], ['context' => 'arch_product']);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This action rebuilds all permissions on products, and may be a lengthy process. This action cannot be undone.', [], ['context' => 'arch_product']);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    product_access_rebuild(TRUE);
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
