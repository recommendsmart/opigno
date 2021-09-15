<?php

namespace Drupal\arch_product\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\FieldPluginBase;

/**
 * Field handler to provide simple renderer that allows linking to a product.
 *
 * Definition terms:
 * - link_to_product default: Should this field have the checkbox
 *   "link to product" enabled by default.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("product")
 */
class Product extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    // Don't add the additional fields to groupby.
    if (!empty($this->options['link_to_product'])) {
      $this->additional_fields['pid'] = [
        'table' => 'arch_product_field_data',
        'field' => 'pid',
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['link_to_product'] = ['default' => isset($this->definition['link_to_product default']) ? $this->definition['link_to_product default'] : FALSE];
    return $options;
  }

  /**
   * Provide link to product option.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['link_to_product'] = [
      '#title' => $this->t('Link this field to the original piece of product', [], ['context' => 'arch_product']),
      '#description' => $this->t("Enable to override this field's links."),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link_to_product']),
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * Prepares link to the product.
   *
   * @param string $data
   *   The XSS safe string for the link text.
   * @param \Drupal\views\ResultRow $values
   *   The values retrieved from a single row of a view's query result.
   *
   * @return string
   *   Returns a string for the link text.
   */
  protected function renderLink($data, ResultRow $values) {
    if (!empty($this->options['link_to_product']) && !empty($this->additional_fields['pid'])) {
      if ($data !== NULL && $data !== '') {
        $this->options['alter']['make_link'] = TRUE;
        $this->options['alter']['url'] = Url::fromRoute('entity.product.canonical', [
          'product' => $this->getValue($values, 'pid'),
        ]);
        if (isset($this->aliases['langcode'])) {
          $languages = \Drupal::languageManager()->getLanguages();
          $langcode = $this->getValue($values, 'langcode');
          if (isset($languages[$langcode])) {
            $this->options['alter']['language'] = $languages[$langcode];
          }
          else {
            unset($this->options['alter']['language']);
          }
        }
      }
      else {
        $this->options['alter']['make_link'] = FALSE;
      }
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    return $this->renderLink($this->sanitizeValue($value), $values);
  }

}
