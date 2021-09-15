<?php

namespace Drupal\arch_order;

/**
 * Order address data interface.
 *
 * @package Drupal\arch_order
 */
interface OrderAddressDataInterface {

  /**
   * Get as array.
   *
   * @return array
   *   Order data as array.
   */
  public function toArray();

  /**
   * Get field value.
   *
   * @param string $field
   *   Field name.
   *
   * @return mixed
   *   Value.
   */
  public function get($field);

  /**
   * Set field value.
   *
   * @param string $field
   *   Field name.
   * @param mixed $value
   *   Field value.
   *
   * @return $this
   */
  public function set($field, $value);

  /**
   * Get order ID.
   *
   * @return int
   *   Order ID.
   */
  public function getOrderId();

  /**
   * Get address type.
   *
   * @return string|null
   *   Address type value.
   */
  public function getAddressType();

  /**
   * Get country code.
   *
   * @return string|null
   *   Country code.
   */
  public function getCountryCode();

  /**
   * Get administrative area.
   *
   * @return string|null
   *   Administrative area value.
   */
  public function getAdministrativeArea();

  /**
   * Get locality.
   *
   * @return string|null
   *   Locality.
   */
  public function getLocality();

  /**
   * Get dependent locality.
   *
   * @return string|null
   *   Dependent locality.
   */
  public function getDependentLocality();

  /**
   * Get postal code.
   *
   * @return string|null
   *   Postal code.
   */
  public function getPostalCode();

  /**
   * Get sorting code.
   *
   * @return string|null
   *   Sorting code.
   */
  public function getSortingCode();

  /**
   * Get address line 1.
   *
   * @return string|null
   *   Address line 1 value.
   */
  public function getAddressLine1();

  /**
   * Get address line 2.
   *
   * @return string|null
   *   Address line 2 value.
   */
  public function getAddressLine2();

  /**
   * Get organization value.
   *
   * @return string|null
   *   Organization value.
   */
  public function getOrganization();

  /**
   * Get given name.
   *
   * @return string|null
   *   Given name value.
   */
  public function getGivenName();

  /**
   * Get additional name.
   *
   * @return string|null
   *   Additional name value.
   */
  public function getAdditionalName();

  /**
   * Get family name value.
   *
   * @return string|null
   *   Family name value.
   */
  public function getFamilyName();

  /**
   * Get TAX ID.
   *
   * @return string|null
   *   TAX ID value.
   */
  public function getTaxId();

  /**
   * Get phone value.
   *
   * @return string|null
   *   Phone value.
   */
  public function getPhone();

  /**
   * Set order ID.
   *
   * @param string $value
   *   Order ID.
   *
   * @return $this
   */
  public function setOrderId($value);

  /**
   * Set address type value.
   *
   * @param string|null $value
   *   Address type value.
   *
   * @return $this
   */
  public function setAddressType($value = NULL);

  /**
   * Set country code value.
   *
   * @param string|null $value
   *   Country code value.
   *
   * @return $this
   */
  public function setCountryCode($value = NULL);

  /**
   * Set administrative area value.
   *
   * @param string|null $value
   *   Administrative area value.
   *
   * @return $this
   */
  public function setAdministrativeArea($value = NULL);

  /**
   * Set locality value.
   *
   * @param string|null $value
   *   Locality value.
   *
   * @return $this
   */
  public function setLocality($value = NULL);

  /**
   * Set dependent locality value.
   *
   * @param string|null $value
   *   Dependent locality value.
   *
   * @return $this
   */
  public function setDependentLocality($value = NULL);

  /**
   * Set postal code value.
   *
   * @param string|null $value
   *   Postal code value.
   *
   * @return $this
   */
  public function setPostalCode($value = NULL);

  /**
   * Set sorting code value.
   *
   * @param string|null $value
   *   Sorting code value.
   *
   * @return $this
   */
  public function setSortingCode($value = NULL);

  /**
   * Set first address line value.
   *
   * @param string|null $value
   *   First address line value.
   *
   * @return $this
   */
  public function setAddressLine1($value = NULL);

  /**
   * Set second address line value.
   *
   * @param string|null $value
   *   Second address line value.
   *
   * @return $this
   */
  public function setAddressLine2($value = NULL);

  /**
   * Set organization name value.
   *
   * @param string|null $value
   *   Organization name value.
   *
   * @return $this
   */
  public function setOrganization($value = NULL);

  /**
   * Set given name value.
   *
   * @param string|null $value
   *   Given name value.
   *
   * @return $this
   */
  public function setGivenName($value = NULL);

  /**
   * Set additional name value.
   *
   * @param string|null $value
   *   Additional name value.
   *
   * @return $this
   */
  public function setAdditionalName($value = NULL);

  /**
   * Set family name value.
   *
   * @param string|null $value
   *   Family name value.
   *
   * @return $this
   */
  public function setFamilyName($value = NULL);

  /**
   * Set TAX ID value.
   *
   * @param string|null $value
   *   TAX ID value.
   *
   * @return $this
   */
  public function setTaxId($value = NULL);

  /**
   * Set phone value.
   *
   * @param string|null $value
   *   Phone value.
   *
   * @return $this
   */
  public function setPhone($value = NULL);

}
