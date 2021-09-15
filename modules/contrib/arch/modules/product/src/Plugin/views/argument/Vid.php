<?php

namespace Drupal\arch_product\Plugin\views\argument;

use Drupal\arch_product\Entity\Storage\ProductStorageInterface;
use Drupal\Core\Database\Connection;
use Drupal\views\Plugin\views\argument\NumericArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler to accept a product revision id.
 *
 * @ViewsArgument("product_vid")
 */
class Vid extends NumericArgument {

  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The product storage.
   *
   * @var \Drupal\arch_product\Entity\Storage\ProductStorageInterface
   */
  protected $productStorage;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $database
   *   Database Service Object.
   * @param \Drupal\arch_product\Entity\Storage\ProductStorageInterface $product_storage
   *   The product storage.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Connection $database,
    ProductStorageInterface $product_storage
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->database = $database;
    $this->productStorage = $product_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('entity_type.manager')->getStorage('product')
    );
  }

  /**
   * Override the behavior of title(). Get the title of the revision.
   */
  public function titleQuery() {
    $titles = [];

    $results = $this->database->query('SELECT nr.vid, nr.pid, npr.title FROM {arch_product_revision} nr WHERE nr.vid IN ( :vids[] )', [':vids[]' => $this->value])->fetchAllAssoc('vid', \PDO::FETCH_ASSOC);
    $pids = [];
    foreach ($results as $result) {
      $pids[] = $result['pid'];
    }

    $products = $this->productStorage->loadMultiple(array_unique($pids));

    foreach ($results as $result) {
      $products[$result['pid']]->set('title', $result['title']);
      $titles[] = $products[$result['pid']]->label();
    }

    return $titles;
  }

}
