<?php

namespace Drupal\entity_extra_field\Plugin\ExtraFieldType;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\entity_extra_field\ExtraFieldTypePluginBase;

/**
 * Define the extra field entity link type.
 *
 * @ExtraFieldType(
 *   id = "entity_link",
 *   label = @Translation("Entity link")
 * )
 */
class ExtraFieldEntityLinkPlugin extends ExtraFieldTypePluginBase {

  /**
   * {@inheritDoc}
   */
  public function defaultConfiguration() {
    return [
        'link_text' => NULL,
        'link_template' => NULL,
        'link_target' => NULL,
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $configuration = $this->getConfiguration();

    $form['link_template'] = [
      '#type' => 'select',
      '#title' => $this->t('Link Template'),
      '#require' => TRUE,
      '#options' => $this->getEntityLinkTemplateOptions(),
      '#empty_option' => $this->t('- Select -'),
      '#required' => TRUE,
      '#default_value' => $configuration['link_template']
    ];
    $form['link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link Text'),
      '#default_value' => $configuration['link_text'],
      '#size' => 25,
      '#required' => TRUE,
    ];
    $form['link_target'] = [
      '#type' => 'select',
      '#title' => $this->t('Link Target'),
      '#options' => [
        '_blank'
      ],
      '#empty_option' => $this->t('- Default -'),
      '#default_value' => $configuration['link_target'],
    ];

    return $form;
  }

  /**
   * Build the render array of the extra field type contents.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity type the extra field is being attached too.
   * @param \Drupal\Core\Entity\Display\EntityDisplayInterface $display
   *   The entity display the extra field is apart of.
   *
   * @return array
   *   The extra field renderable array.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function build(EntityInterface $entity, EntityDisplayInterface $display) {
    $link = $this->buildEntityLink($entity);

    // Link and Url seem not to have convenience methods for access including
    // cacheability. So inlining a variant of \Drupal\Core\Url::access
    $accessResult = $this->urlAccessResult($link->getUrl());
    $build = $accessResult->isAllowed() ? $link->toRenderable() : [];
    BubbleableMetadata::createFromObject($accessResult)->applyTo($build);
    return $build;
  }

  /**
   * A copy of \Drupal\Core\Url::access that returns cacheability.
   *
   * @param \Drupal\Core\Url $url
   *   The url.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) Run access checks for this account. Defaults to the current
   *   user.
   *
   * @return \Drupal\Core\Access\AccessResultInterface Returns url access result object.
   *   Returns url access result object.
   */
  public function urlAccessResult(Url $url, AccountInterface $account = NULL) {
    if ($url->isRouted()) {
      /** @var \Drupal\Core\Access\AccessManagerInterface $accessManager */
      $accessManager = \Drupal::service('access_manager');
      return $accessManager->checkNamedRoute($url->getRouteName(), $url->getRouteParameters(), $account, TRUE);
    }
    return AccessResult::allowed();
  }

  /**
   * Build the entity link.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity instance.
   *
   * @return \Drupal\Core\Link
   *   The entity link instance.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  protected function buildEntityLink(EntityInterface $entity) {
    $configuration = $this->getConfiguration();

    return $entity->toLink(
      $configuration['link_text'],
      $configuration['link_template'],
      $this->getEntityLinkOptions()
    );
  }

  /**
   * Get the entity link options.
   *
   * @return array
   *   An array of the link options.
   */
  protected function getEntityLinkOptions() {
    $options = [];
    $configuration = $this->getConfiguration();

    if ($target = $configuration['link_target']) {
      $options['attributes']['target'] = $target;
    }

    return $options;
  }

  /**
   * Get entity link template options.
   *
   * @return array
   *   An array of the entity template options.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getEntityLinkTemplateOptions() {
    $templates = array_keys(
      $this->getTargetEntityTypeDefinition()->getLinkTemplates()
    );

    return array_combine($templates, $templates);
  }
}
