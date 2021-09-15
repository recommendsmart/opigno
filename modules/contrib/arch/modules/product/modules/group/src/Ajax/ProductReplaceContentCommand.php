<?php

namespace Drupal\arch_product_group\Ajax;

use Drupal\Core\Ajax\CommandInterface;
use Drupal\Core\Url;

/**
 * Provides an AJAX command for replacing the page title.
 *
 * This command is implemented in
 * Drupal.AjaxCommands.prototype.productReplaceTitle.
 */
class ProductReplaceContentCommand implements CommandInterface {

  /**
   * Url.
   *
   * @var \Drupal\Core\Url
   */
  protected $url;

  /**
   * The page title to replace.
   *
   * @var string
   */
  protected $title;

  /**
   * The page content to replace.
   *
   * @var string
   */
  protected $content;


  /**
   * The page content selector.
   *
   * @var string
   */
  protected $selector;

  /**
   * Extra data.
   *
   * @var array
   */
  protected $data;

  /**
   * Constructs a \Drupal\arch_product_group\Ajax\ProductReplaceUrlTitleCommand.
   *
   * @param \Drupal\Core\Url $url
   *   New URL.
   * @param string $title
   *   The title of the page.
   * @param string $selector
   *   Selector.
   * @param string $content
   *   Page content.
   * @param array $data
   *   Extra data.
   */
  public function __construct(
    Url $url,
    $title,
    $selector,
    $content,
    array $data = []
  ) {
    $this->url = $url;
    $this->title = $title;
    $this->content = $content;
    $this->selector = $selector;
    $this->data = $data;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $site_config = \Drupal::config('system.site');
    $this->url->setAbsolute(TRUE);
    return [
      'command' => 'productReplaceUrlTitle',
      'siteName' => $site_config->get('name'),
      'url' => $this->url->toString(),
      'title' => $this->title,
      'selector' => $this->selector,
      'content' => $this->content,
      'data' => $this->data,
    ];
  }

}
