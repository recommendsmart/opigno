<?php

namespace Drupal\node_singles\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

class SingleNodeTypeDeleteConfirm extends EntityDeleteForm
{
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form = parent::buildForm($form, $form_state);

        if ($this->entity->getThirdPartySetting('node_singles', 'is_single', false)) {
            $caption = '<p>' . $this->t('Deleting this single node type will also delete the associated node and its data. Are you sure?') . '</p>';
        } else {
            $numNodes = $this->entityTypeManager->getStorage('node')->getQuery()
                ->accessCheck(false)
                ->condition('type', $this->entity->id())
                ->count()
                ->execute();

            if ($numNodes) {
                $caption = '<p>' . $this->formatPlural($numNodes, '%type is used by 1 piece of content on your site. You can not remove this content type until you have removed all of the %type content.', '%type is used by @count pieces of content on your site. You may not remove %type until you have removed all of the %type content.', ['%type' => $this->entity->label()]) . '</p>';
            }
        }

        $form['#title'] = $this->getQuestion();
        if (isset($caption)) {
            $form['description'] = ['#markup' => $caption];
        }

        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        $storage = $this->entityTypeManager->getStorage('node');
        $ids = $storage->getQuery()
            ->condition('type', $this->entity->id())
            ->execute();

        if (!empty($ids)) {
            $nodes = $storage->loadMultiple($ids);

            // Delete existing nodes
            $storage->delete($nodes);
            $this->messenger()->addMessage($this->formatPlural(count($nodes), 'Entity is successfully deleted.', 'All @count entities are successfully deleted.'));
        }

        // Delete the node type
        parent::submitForm($form, $form_state);
    }
}
