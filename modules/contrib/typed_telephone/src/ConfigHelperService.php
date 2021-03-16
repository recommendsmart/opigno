<?php

namespace Drupal\typed_telephone;
use Drupal\webprofiler\Config\ConfigFactoryWrapper;

/**
 * Class ConfigHelperService.
 */
class ConfigHelperService {

  /**
   * Drupal\webprofiler\Config\ConfigFactoryWrapper definition.
   *
   * @var \Drupal\webprofiler\Config\ConfigFactoryWrapper
   */
  protected $configFactory;

  /**
   * Constructs a new ConfigHelperService object.
   */
  public function __construct(ConfigFactoryWrapper $config_factory) {
    $this->configFactory = $config_factory;
  }

  public function getTypes() {
    $final_types = [];
    $teltypes = $this->configFactory->listAll('typed_telephone.telephone_type');
    foreach($teltypes as $teltype) {
      $type_config = $this->configFactory->get($teltype);
      $final_types[$type_config->get('id')] = [
        'label' => $type_config->get('label'),
        'uuid'  => $type_config->get('uuid')
      ];
    }

    return $final_types;
  }

  public function getLabelFromShortname($name) {
    return $this->configFactory->get('typed_telephone.telephone_type.'.$name)->get('label');
  }

  public function getTypesAsOptions($filter = []) {
    $types = $this->getTypes();

    // Filter out options not selected
    if(!empty($filter)) {
      $types = array_intersect_key($types, array_flip($filter));
    }

    // Reformat remaining array items to checkboxes #options format.
    foreach($types as &$type) {
      $type = $type['label'];
    }

    return $types;
  }



}
