<?php

namespace Drupal\arch_order;

/**
 * Order address data.
 *
 * @package Drupal\arch_order
 */
class OrderAddressData implements OrderAddressDataInterface {

  /**
   * Address data.
   *
   * @var array
   */
  protected $values = [
    'order_id' => NULL,
    'address_type' => NULL,
    'country_code' => NULL,
    'administrative_area' => NULL,
    'locality' => NULL,
    'dependent_locality' => NULL,
    'postal_code' => NULL,
    'sorting_code' => NULL,
    'address_line1' => NULL,
    'address_line2' => NULL,
    'organization' => NULL,
    'given_name' => NULL,
    'additional_name' => NULL,
    'family_name' => NULL,
    'tax_id' => NULL,
    'phone' => NULL,
  ];

  /**
   * OrderAddressData constructor.
   *
   * @param array $data
   *   Order address data.
   */
  public function __construct(array $data = []) {
    foreach ($data as $field_name => $value) {
      $this->set($field_name, $value);
    }
  }

  /**
   * Magic getter.
   *
   * @param string $name
   *   Field name.
   *
   * @return mixed
   *   Value.
   */
  public function __get($name) {
    return $this->get($name);
  }

  /**
   * Magic setter.
   *
   * Required because when we use this class as target class for DB fetch
   * it will try to set values as public properties.
   *
   * @param string $name
   *   Field name.
   * @param mixed $value
   *   Field value.
   */
  public function __set($name, $value) {
    $this->set($name, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    return $this->values;
  }

  /**
   * Camelize string.
   *
   * @param string $str
   *   String to camelize.
   *
   * @return string
   *   Camelized string.
   */
  protected static function camelize($str) {
    $cleaned_string = strtr($str, ['_' => ' ', '.' => '_ ', '\\' => '_ ']);
    $normalized_words = ucwords($cleaned_string);

    return strtr($normalized_words, [' ' => '']);
  }

  /**
   * Get getter method name for field.
   *
   * @param string $field_name
   *   Field name.
   *
   * @return string
   *   Method name.
   */
  protected static function getGetterMethod($field_name) {
    return self::camelize('get_' . $field_name);
  }

  /**
   * Get setter method name for field.
   *
   * @param string $field_name
   *   Field name.
   *
   * @return string
   *   Method name.
   */
  protected static function getSetterMethod($field_name) {
    return self::camelize('set_' . $field_name);
  }

  /**
   * {@inheritdoc}
   */
  public function get($field) {
    $method = self::getGetterMethod($field);
    if (method_exists($this, $method)) {
      return $this->{$method}();
    }
    return isset($this->values[$field]) ? $this->values[$field] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function set($field_name, $value) {
    $method = self::getSetterMethod($field_name);
    if (method_exists($this, $method)) {
      return $this->{$method}($value);
    }

    if (array_key_exists($field_name, $this->values)) {
      $this->values[$field_name] = $value;
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderId() {
    return $this->values['order_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getAddressType() {
    return $this->values['address_type'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCountryCode() {
    return $this->values['country_code'];
  }

  /**
   * {@inheritdoc}
   */
  public function getAdministrativeArea() {
    return $this->values['administrative_area'];
  }

  /**
   * {@inheritdoc}
   */
  public function getLocality() {
    return $this->values['locality'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDependentLocality() {
    return $this->values['dependent_locality'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPostalCode() {
    return $this->values['postal_code'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSortingCode() {
    return $this->values['sorting_code'];
  }

  /**
   * {@inheritdoc}
   */
  public function getAddressLine1() {
    return $this->values['address_line1'];
  }

  /**
   * {@inheritdoc}
   */
  public function getAddressLine2() {
    return $this->values['address_line2'];
  }

  /**
   * {@inheritdoc}
   */
  public function getOrganization() {
    return $this->values['organization'];
  }

  /**
   * {@inheritdoc}
   */
  public function getGivenName() {
    return $this->values['given_name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getAdditionalName() {
    return $this->values['additional_name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFamilyName() {
    return $this->values['family_name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getTaxId() {
    return $this->values['tax_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPhone() {
    return $this->values['phone'];
  }

  /**
   * {@inheritdoc}
   */
  public function setOrderId($value) {
    if (empty($value)) {
      throw new \InvalidArgumentException('Order ID is required!');
    }
    $this->values['order_id'] = (int) $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setAddressType($value = NULL) {
    $this->values['address_type'] = isset($value) ? (string) $value : NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setCountryCode($value = NULL) {
    $this->values['country_code'] = isset($value) ? (string) $value : NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setAdministrativeArea($value = NULL) {
    $this->values['administrative_area'] = isset($value) ? (string) $value : NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLocality($value = NULL) {
    $this->values['locality'] = isset($value) ? (string) $value : NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setDependentLocality($value = NULL) {
    $this->values['dependent_locality'] = isset($value) ? (string) $value : NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPostalCode($value = NULL) {
    $this->values['postal_code'] = isset($value) ? (string) $value : NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSortingCode($value = NULL) {
    $this->values['sorting_code'] = isset($value) ? (string) $value : NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setAddressLine1($value = NULL) {
    $this->values['address_line1'] = isset($value) ? (string) $value : NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setAddressLine2($value = NULL) {
    $this->values['address_line2'] = isset($value) ? (string) $value : NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOrganization($value = NULL) {
    $this->values['organization'] = isset($value) ? (string) $value : NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setGivenName($value = NULL) {
    $this->values['given_name'] = isset($value) ? (string) $value : NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setAdditionalName($value = NULL) {
    $this->values['additional_name'] = isset($value) ? (string) $value : NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setFamilyName($value = NULL) {
    $this->values['family_name'] = isset($value) ? (string) $value : NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setTaxId($value = NULL) {
    $this->values['tax_id'] = isset($value) ? (string) $value : NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPhone($value = NULL) {
    $this->values['phone'] = isset($value) ? (string) $value : NULL;
    return $this;
  }

}
