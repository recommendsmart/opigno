<?php

namespace Drupal\eca_views\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eca\Service\Conditions;
use Drupal\views\Plugin\views\display\DisplayPluginBase;

/**
 * Run views query and export result.
 *
 * @Action(
 *   id = "eca_views_export",
 *   label = @Translation("Views: Export query into file")
 * )
 */
class ViewsExport extends ViewsQuery {

  /**
   * The filename being prepared by ::access() and used by ::execute().
   *
   * @var string
   */
  private string $filename;

  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL): void {
    if (!($display = $this->getDisplay())) {
      return;
    }
    if ($this->configuration['load_results_into_token'] === Conditions::OPTION_YES) {
      parent::execute();
    }
    else {
      $display->execute();
    }
    $this->view->display_handler->buildRenderable($this->view->args, FALSE);
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');
    $output = (string) $renderer->renderRoot($build);
    file_put_contents($this->filename, $output);
    $token_name = trim($this->configuration['token_for_filename']);
    if ($token_name === '') {
      $token_name = 'eca-view-output-filename';
    }
    $this->tokenServices->addTokenData($token_name, $this->filename);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = parent::access($object, $account, TRUE);
    if ($result->isAllowed() && $display = $this->getDisplay()) {
      if (empty($display->getPluginDefinition()['returns_response'])) {
        $result = AccessResult::forbidden('The given display is not meant to export.');
      }
      else {
        $this->filename = $this->getFilename($display);
        /** @var \Drupal\Core\File\FileSystem $fs */
        $fs = \Drupal::service('file_system');
        $dirname = $fs->dirname($this->filename);
        if (!$fs->prepareDirectory($dirname, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
          $result = AccessResult::forbidden('The given filename is not writable.');
        }
      }
    }
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
        'filename' => '',
        'token_for_filename' => '',
        'load_results_into_token' => FALSE,
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['load_results_into_token'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Store results also in a token?'),
      '#default_value' => $this->configuration['load_results_into_token'],
      '#weight' => -11,
    ];
    $form['token_for_filename'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Token name for file name'),
      '#default_value' => $this->configuration['token_for_filename'],
      '#weight' => -3,
    ];
    $form['filename'] = [
      '#type' => 'textfield',
      '#title' => $this->t('File name'),
      '#default_value' => $this->configuration['filename'],
      '#weight' => -2,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['load_results_into_token'] = $form_state->getValue('load_results_into_token');
    $this->configuration['token_for_filename'] = $form_state->getValue('token_for_filename');
    $this->configuration['filename'] = $form_state->getValue('filename');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * Returns the filename being used for the export.
   *
   * If the plugin configuration provides a filename, this will be used.
   * Otherwise the filename configured for the display plugin will be used
   * or a default of temporary://view.output otherwise.
   *
   * @param \Drupal\views\Plugin\views\display\DisplayPluginBase $display
   *   The display plugin for which to determine the filename.
   *
   * @return string
   *   The filename.
   */
  protected function getFilename(DisplayPluginBase $display): string {
    if (!empty($this->configuration['filename'])) {
      return $this->configuration['filename'];
    }
    if ($filename = $display->getOption('filename')) {
      return $this->tokenServices->replaceClear($filename, ['view' => $this->view]);
    }
    return 'temporary://' . uniqid('eca.view.output', TRUE);
  }

}
