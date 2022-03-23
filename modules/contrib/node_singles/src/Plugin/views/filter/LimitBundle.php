<?php

namespace Drupal\node_singles\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\Bundle;
use Drupal\views\ViewExecutable;

/**
 * Base class for filters that limit the bundles to a fixed list
 */
abstract class LimitBundle extends Bundle
{
    public function init(ViewExecutable $view, DisplayPluginBase $display, ?array &$options = null): void
    {
        parent::init($view, $display, $options);
        $this->value = $this->getBundles();
        $this->options['value'] = array_intersect_key(
            $this->getValueOptions(),
            array_flip($this->getBundles())
        );
        $this->options['expose']['reduce'] = 1;

        $this->definition['allow empty'] = false;
    }

    public function buildExposeForm(&$form, FormStateInterface $form_state)
    {
        parent::buildExposeForm($form, $form_state);
        // Reducing the allowed bundles is the whole point of this plugin
        $form['expose']['reduce']['#disabled'] = true;

        return $form;
    }

    public function adminSummary()
    {
        if ($this->isAGroup()) {
            return $this->t('grouped');
        }
        if (!empty($this->options['exposed'])) {
            return $this->t('exposed');
        }

        if ($this->operator !== 'in') {
            return t('inverted');
        }

        return null;
    }

    protected function valueForm(&$form, FormStateInterface $form_state): void
    {
        parent::valueForm($form, $form_state);

        // Disable the checkboxes on the config form
        if ($form_state->getFormObject()->getFormId() === 'views_ui_config_item_form') {
            $form['value']['#disabled'] = true;
        }
    }

    protected function valueSubmit($form, FormStateInterface $form_state): void
    {
        // Don't actually store the selected values
        $form_state->setValue(['options', 'value'], []);
    }

    /** @return string[] */
    abstract protected function getBundles(): array;
}
