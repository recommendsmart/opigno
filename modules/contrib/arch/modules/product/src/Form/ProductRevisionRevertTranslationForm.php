<?php

namespace Drupal\arch_product\Form;

use Drupal\arch_product\Entity\ProductInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for reverting a product revision for a single translation.
 *
 * @internal
 */
class ProductRevisionRevertTranslationForm extends ProductRevisionRevertForm {

  /**
   * The language to be reverted.
   *
   * @var string
   */
  protected $langcode;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new ProductRevisionRevertTranslationForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $product_storage
   *   The product storage.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    EntityStorageInterface $product_storage,
    DateFormatterInterface $date_formatter,
    LanguageManagerInterface $language_manager,
    TimeInterface $time
  ) {
    parent::__construct($product_storage, $date_formatter, $time);
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('product'),
      $container->get('date.formatter'),
      $container->get('language_manager'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'product_revision_revert_translation_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t(
      'Are you sure you want to revert @language translation to the revision from %revision-date?',
      [
        '@language' => $this->languageManager->getLanguageName($this->langcode),
        '%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime()),
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $product_revision = NULL, $langcode = NULL) {
    $this->langcode = $langcode;
    $form = parent::buildForm($form, $form_state, $product_revision);

    // Unless untranslatable fields are configured to affect only the default
    // translation, we need to ask the user whether they should be included in
    // the revert process.
    $default_translation_affected = $this->revision->isDefaultTranslationAffectedOnly();
    $form['revert_untranslated_fields'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Revert product shared among translations', [], ['context' => 'arch_product']),
      '#default_value' => $default_translation_affected && $this->revision->getTranslation($this->langcode)->isDefaultTranslation(),
      '#access' => !$default_translation_affected,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareRevertedRevision(ProductInterface $revision, FormStateInterface $form_state) {
    $revert_untranslated_fields = (bool) $form_state->getValue('revert_untranslated_fields');
    $translation = $revision->getTranslation($this->langcode);
    return $this->productStorage->createRevision($translation, TRUE, $revert_untranslated_fields);
  }

}
