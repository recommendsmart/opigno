<?php

namespace Drupal\arch_payment;

use Drupal\Core\Url;
use Drupal\plugin\PluginType\DefaultPluginTypeOperationsProvider;

/**
 * Provides operations for the payment methods plugin type.
 */
class PaymentMethodOperationsProvider extends DefaultPluginTypeOperationsProvider {

  /**
   * {@inheritdoc}
   */
  public function getOperations($plugin_type_id) {
    $operations = parent::getOperations($plugin_type_id);
    $operations['configure'] = [
      'title' => $this->t('Configure'),
      'url' => new Url('currency.amount_formatting'),
    ];

    return $operations;
  }

}
