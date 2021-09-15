<?php

namespace Drupal\arch_downloadable_product\Controller;

use Drupal\arch_downloadable_product\ProductFileAccessInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Product download controller.
 *
 * @package Drupal\arch_downloadable_product\Controller
 */
class ProductDownloadController extends ControllerBase {

  /**
   * Product file access.
   *
   * @var \Drupal\arch_downloadable_product\ProductFileAccessInterface
   */
  protected $productFileAccess;

  /**
   * Product storage.
   *
   * @var \Drupal\arch_product\Entity\Storage\ProductStorageInterface
   */
  protected $productStorage;

  /**
   * File storage.
   *
   * @var \Drupal\file\FileStorageInterface
   */
  protected $fileStorage;

  /**
   * Current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * ProductDownloadController constructor.
   *
   * @param \Drupal\arch_downloadable_product\ProductFileAccessInterface $product_file_access
   *   Product file access.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Request stack.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    ProductFileAccessInterface $product_file_access,
    EntityTypeManagerInterface $entity_type_manager,
    RequestStack $request_stack,
    AccountInterface $current_user
  ) {
    $this->productFileAccess = $product_file_access;
    $this->productStorage = $entity_type_manager->getStorage('product');
    $this->fileStorage = $entity_type_manager->getStorage('file');
    $this->currentUser = $entity_type_manager->getStorage('user')->load($current_user->id());

    $this->currentRequest = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('downloadable_product.access'),
      $container->get('entity_type.manager'),
      $container->get('request_stack'),
      $container->get('current_user')
    );
  }

  /**
   * Access callback.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account.
   * @param int $product_id
   *   Product ID.
   * @param string $file_uuid
   *   File UUID.
   * @param string $user_uuid
   *   User UUID.
   *
   * @return \Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden
   *   Access check result.
   */
  public function downloadAccess(AccountInterface $account, $product_id, $file_uuid, $user_uuid) {
    if (
      $account->isAnonymous()
      || $user_uuid !== $this->currentUser->uuid()
    ) {
      return AccessResult::forbidden();
    }

    $request_token = $this->currentRequest->query->get('pdtok');
    if (!$this->productFileAccess->checkByIdsWithToken($product_id, $file_uuid, $user_uuid, $request_token)) {
      return AccessResult::forbidden();
    }

    return AccessResult::allowed();
  }

  /**
   * Handles private file transfers.
   *
   * Call modules that implement hook_file_download() to find out if a file is
   * accessible and what headers it should be transferred with. If one or more
   * modules returned headers the download will start with the returned headers.
   * If a module returns -1 an AccessDeniedHttpException will be thrown. If the
   * file exists but no modules responded an AccessDeniedHttpException will be
   * thrown. If the file does not exist a NotFoundHttpException will be thrown.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param int $product_id
   *   Product ID.
   * @param string $file_uuid
   *   File UUID.
   * @param string $user_uuid
   *   User UUID.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   *   The transferred file as response.
   *
   * @see hook_file_download()
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Thrown when the requested file does not exist.
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when the user does not have access to the file.
   */
  public function download(Request $request, $product_id, $file_uuid, $user_uuid) {
    $file = $this->loadFileByUuid($file_uuid);
    if (!$file) {
      throw new NotFoundHttpException();
    }

    $uri = $file->getFileUri();
    if (!file_exists($uri)) {
      throw new NotFoundHttpException();
    }

    $hooks = [
      'product_file_download',
      'file_download',
    ];

    $headers = [];
    foreach ($hooks as $hook) {
      // Let other modules provide headers and controls access to the file.
      $headers = $this->moduleHandler()->invokeAll($hook, [$uri]);
      foreach ($headers as $result) {
        if ($result == -1) {
          throw new AccessDeniedHttpException();
        }
        $headers[] = $result;
      }
    }

    if (count($headers)) {
      // \Drupal\Core\EventSubscriber\FinishResponseSubscriber::onRespond()
      // sets response as not cacheable if the Cache-Control header is not
      // already modified. We pass in FALSE for non-private schemes for the
      // $public parameter to make sure we don't change the headers.
      return new BinaryFileResponse($uri, 200, $headers, FALSE);
    }

    throw new AccessDeniedHttpException();
  }

  /**
   * Load file by UUID.
   *
   * @param string $file_uuid
   *   File UUID.
   *
   * @return \Drupal\file\FileInterface|null
   *   File instance or NULL on failure.
   */
  protected function loadFileByUuid($file_uuid) {
    /** @var \Drupal\file\FileInterface[] $files */
    $files = $this->fileStorage->loadByProperties([
      'uuid' => $file_uuid,
    ]);
    if (empty($files)) {
      return NULL;
    }

    return current($files);
  }

}
