<?php

namespace Drupal\digital_signage_framework;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\AttachmentsInterface;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Render\HtmlResponseAttachmentsProcessor;
use Drupal\Core\Render\RendererInterface;

class Renderer {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * @var \Drupal\Core\Render\HtmlResponseAttachmentsProcessor
   */
  protected $attachmentProcessor;

  /**
   * Renderer constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Render\RendererInterface $renderer
   * @param \Drupal\Core\Render\HtmlResponseAttachmentsProcessor $attachment_processor
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer, HtmlResponseAttachmentsProcessor $attachment_processor) {
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
    $this->attachmentProcessor = $attachment_processor;
  }

  /**
   * @param string $entityType
   * @param string $entityId
   * @param \Drupal\digital_signage_framework\DeviceInterface $device
   *
   * @return array
   */
  public function buildEntityView($entityType, $entityId, $device): array {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    try {
      $entity = $this->entityTypeManager->getStorage($entityType)
        ->load($entityId);
      // We know the entity can be loaded because we checked the validity in the access callback.
      /** @noinspection NullPointerExceptionInspection */
      return $this->entityTypeManager->getViewBuilder($entityType)
        ->view($entity, 'digital_signage_' . $device->getOrientation());
    } catch (InvalidPluginDefinitionException $e) {
    } catch (PluginNotFoundException $e) {
    }
    return [];
  }

  /**
   * @param array $output
   *
   * @return \Drupal\Core\Render\AttachmentsInterface
   */
  public function buildHtmlResponse($output): AttachmentsInterface {
    $response = new HtmlResponse();
    $response->setContent([
      '#markup' => $this->renderer->renderRoot($output),
      '#attached' => [
        'html_response_attachment_placeholders' => [],
        'placeholders' => [],
        'drupalSettings' => [
        ],
        'library' => [
          'digital_signage/general',
        ],
      ],
    ]);
    #$response->getCacheableMetadata()->setCacheMaxAge(0);
    #$response->getCacheableMetadata()->setCacheTags([]);
    #$response->getCacheableMetadata()->setCacheContexts([]);
    return $this->attachmentProcessor->processAttachments($response);
  }

  /**
   * @param array $elements
   *
   * @return \Drupal\Component\Render\MarkupInterface
   */
  public function renderPlain(&$elements): MarkupInterface {
    return $this->renderer->renderPlain($elements);
  }

}
