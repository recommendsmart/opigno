<?php

namespace Drupal\node_singles\EventSubscriber;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeTypeInterface;

class NodeTypeFormEventSubscriber
{
    use StringTranslationTrait;

    public function alterNodeTypeForm(array &$form, FormStateInterface $formState): void
    {
        /** @var NodeTypeInterface $type */
        $type = $formState->getFormObject()->getEntity();

        $form['node_singles'] = [
            '#type' => 'details',
            '#title' => $this->t('Singles'),
            '#group' => 'additional_settings',
        ];

        $form['node_singles']['is-single'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('This is a content type with a single entity.'),
            '#default_value' => $type->getThirdPartySetting('node_singles', 'is_single', false),
            '#description' => $this->t('The entity will be created after you save this content type.'),
        ];

        $form['#entity_builders'][] = [static::class, 'formBuilder'];
    }

    public static function formBuilder($entity_type, NodeTypeInterface $type, &$form, FormStateInterface $form_state): void
    {
        $type->setThirdPartySetting('node_singles', 'is_single', $form_state->getValue('is-single'));
    }
}
