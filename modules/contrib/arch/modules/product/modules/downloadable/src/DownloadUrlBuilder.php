<?php

namespace Drupal\arch_downloadable_product;

use Drupal\arch_product\Entity\ProductInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\PrivateKey;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Download URL builder.
 *
 * @package Drupal\arch_downloadable_product
 */
class DownloadUrlBuilder implements DownloadUrlBuilderInterface, ContainerInjectionInterface {

  /**
   * Private key.
   *
   * @var \Drupal\Core\PrivateKey
   */
  protected $privateKey;

  /**
   * DownloadUrlBuilder constructor.
   *
   * @param \Drupal\Core\PrivateKey $private_key
   *   Private key.
   */
  public function __construct(
    PrivateKey $private_key
  ) {
    $this->privateKey = $private_key;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('private_key')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getToken(ProductInterface $product, FileInterface $file, UserInterface $account) {
    $data = implode(':', [
      $product->id(),
      $file->id(),
      $account->getEmail(),
    ]);

    $key = $this->privateKey->get() . $this->getHashSalt();

    // Return the first 8 characters.
    return substr(Crypt::hmacBase64($data, $key), 0, 8);

  }

  /**
   * Gets a salt useful for hardening against SQL injection.
   *
   * @return string
   *   A salt based on information in settings.php, not in the database.
   *
   * @throws \RuntimeException
   */
  protected function getHashSalt() {
    return Settings::getHashSalt();
  }

  /**
   * {@inheritdoc}
   */
  public function getDownloadUrl(ProductInterface $product, FileInterface $file, UserInterface $account) {
    $route_name = 'arch_downloadable_product.download';
    $route_params = [
      'product_id' => $product->id(),
      'file_uuid' => $file->uuid(),
      'user_uuid' => $account->uuid(),
    ];

    $options = [
      'query' => [
        'pdtok' => $this->getToken($product, $file, $account),
      ],
    ];
    return Url::fromRoute($route_name, $route_params, $options);
  }

}
