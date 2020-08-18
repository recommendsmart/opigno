<?php

namespace Drupal\contact_default_fields_override\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class MessageForm extends \Drupal\contact\MessageForm {

  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $this->addBundleNested($form, $this->entity->bundle());

    return $form;
  }

  protected function addBundleNested(&$element, $bundle) {
    foreach (Element::children($element) as $key) {
      $element[$key]['#contact_default_fields_override_bundle'] = $bundle;

      $this->addBundleNested($element[$key], $bundle);
    }
  }


}