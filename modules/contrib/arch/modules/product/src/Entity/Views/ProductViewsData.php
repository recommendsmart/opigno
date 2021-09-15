<?php

namespace Drupal\arch_product\Entity\Views;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the product entity type.
 */
class ProductViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['arch_product_field_data']['table']['base']['weight'] = -10;
    $data['arch_product_field_data']['table']['base']['access query tag'] = 'arch_product_access';
    $data['arch_product_field_data']['table']['wizard_id'] = 'product';

    $data['arch_product_field_data']['pid']['argument'] = [
      'id' => 'product_pid',
      'name field' => 'title',
      'numeric' => TRUE,
      'validate type' => 'pid',
    ];

    $data['arch_product_field_data']['title']['field']['default_formatter_settings'] = ['link_to_entity' => TRUE];
    $data['arch_product_field_data']['title']['field']['link_to_product default'] = TRUE;

    $data['arch_product_field_data']['sku']['field']['default_formatter_settings'] = ['link_to_entity' => TRUE];
    $data['arch_product_field_data']['sku']['field']['link_to_product default'] = TRUE;

    $data['arch_product_field_data']['type']['argument']['id'] = 'product_type';

    $data['arch_product_field_data']['langcode']['help'] = $this->t('The language of the product or translation.', [], ['context' => 'arch_product']);

    $data['arch_product_field_data']['status']['filter']['label'] = $this->t('Published status');
    $data['arch_product_field_data']['status']['filter']['type'] = 'yes-no';
    // Use status = 1 instead of status <> 0 in WHERE statement.
    $data['arch_product_field_data']['status']['filter']['use_equal'] = TRUE;

    $data['arch_product_field_data']['status_extra'] = [
      'title' => $this->t('Published status or admin user'),
      'help' => $this->t('Filters out unpublished product if the current user cannot view it.', [], ['context' => 'arch_product']),
      'filter' => [
        'field' => 'status',
        'id' => 'product_status',
        'label' => $this->t('Published status or admin user'),
      ],
    ];

    $data['arch_product_field_data']['promote']['help'] = $this->t('A boolean indicating whether the product is visible on the front page.', [], ['context' => 'arch_product']);
    $data['arch_product_field_data']['promote']['filter']['label'] = $this->t('Promoted to front page status');
    $data['arch_product_field_data']['promote']['filter']['type'] = 'yes-no';

    $data['arch_product_field_data']['sticky']['help'] = $this->t('A boolean indicating whether the product should sort to the top of product lists.', [], ['context' => 'arch_product']);
    $data['arch_product_field_data']['sticky']['filter']['label'] = $this->t('Sticky status');
    $data['arch_product_field_data']['sticky']['filter']['type'] = 'yes-no';
    $data['arch_product_field_data']['sticky']['sort']['help'] = $this->t('Whether or not the product is sticky. To list sticky products first, set this to descending.', [], ['context' => 'arch_product']);

    $data['arch_product']['product_bulk_form'] = [
      'title' => $this->t('Product operations bulk form', [], ['context' => 'arch_product']),
      'help' => $this->t('Add a form element that lets you run operations on multiple products.', [], ['context' => 'arch_product']),
      'field' => [
        'id' => 'product_bulk_form',
      ],
    ];

    // Bogus fields for aliasing purposes.
    // @todo Add similar support to any date field
    // @see https://www.drupal.org/node/2337507
    $data['arch_product_field_data']['created_fulldate'] = [
      'title' => $this->t('Created date', [], ['context' => 'arch_product']),
      'help' => $this->t('Date in the form of CCYYMMDD.'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_fulldate',
      ],
    ];

    $data['arch_product_field_data']['created_year_month'] = [
      'title' => $this->t('Created year + month'),
      'help' => $this->t('Date in the form of YYYYMM.'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_year_month',
      ],
    ];

    $data['arch_product_field_data']['created_year'] = [
      'title' => $this->t('Created year'),
      'help' => $this->t('Date in the form of YYYY.'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_year',
      ],
    ];

    $data['arch_product_field_data']['created_month'] = [
      'title' => $this->t('Created month'),
      'help' => $this->t('Date in the form of MM (01 - 12).'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_month',
      ],
    ];

    $data['arch_product_field_data']['created_day'] = [
      'title' => $this->t('Created day'),
      'help' => $this->t('Date in the form of DD (01 - 31).'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_day',
      ],
    ];

    $data['arch_product_field_data']['created_week'] = [
      'title' => $this->t('Created week'),
      'help' => $this->t('Date in the form of WW (01 - 53).'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_week',
      ],
    ];

    $data['arch_product_field_data']['changed_fulldate'] = [
      'title' => $this->t('Updated date'),
      'help' => $this->t('Date in the form of CCYYMMDD.'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_fulldate',
      ],
    ];

    $data['arch_product_field_data']['changed_year_month'] = [
      'title' => $this->t('Updated year + month'),
      'help' => $this->t('Date in the form of YYYYMM.'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_year_month',
      ],
    ];

    $data['arch_product_field_data']['changed_year'] = [
      'title' => $this->t('Updated year'),
      'help' => $this->t('Date in the form of YYYY.'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_year',
      ],
    ];

    $data['arch_product_field_data']['changed_month'] = [
      'title' => $this->t('Updated month'),
      'help' => $this->t('Date in the form of MM (01 - 12).'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_month',
      ],
    ];

    $data['arch_product_field_data']['changed_day'] = [
      'title' => $this->t('Updated day'),
      'help' => $this->t('Date in the form of DD (01 - 31).'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_day',
      ],
    ];

    $data['arch_product_field_data']['changed_week'] = [
      'title' => $this->t('Updated week'),
      'help' => $this->t('Date in the form of WW (01 - 53).'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_week',
      ],
    ];

    $data['arch_product_field_data']['uid']['help'] = $this->t('The creator user of the product. If you need more fields than the uid add the product: author relationship', [], ['context' => 'arch_product']);
    $data['arch_product_field_data']['uid']['filter']['id'] = 'user_name';
    $data['arch_product_field_data']['uid']['relationship']['title'] = $this->t('Product creator', [], ['context' => 'arch_product']);
    $data['arch_product_field_data']['uid']['relationship']['help'] = $this->t('Relate product to the user who created it.', [], ['context' => 'arch_product']);
    $data['arch_product_field_data']['uid']['relationship']['label'] = $this->t('author');

    $data['arch_product']['product_listing_empty'] = [
      'title' => $this->t('Empty Product Frontpage behavior'),
      'help' => $this->t('Provides a link to the product add overview page.', [], ['context' => 'arch_product']),
      'area' => [
        'id' => 'product_listing_empty',
      ],
    ];

    $data['arch_product_field_data']['uid_revision']['title'] = $this->t('User has a revision');
    $data['arch_product_field_data']['uid_revision']['help'] = $this->t('All products where a certain user has a revision', [], ['context' => 'arch_product']);
    $data['arch_product_field_data']['uid_revision']['real field'] = 'pid';
    $data['arch_product_field_data']['uid_revision']['filter']['id'] = 'product_uid_revision';
    $data['arch_product_field_data']['uid_revision']['argument']['id'] = 'product_uid_revision';

    $data['arch_product_field_revision']['table']['wizard_id'] = 'product_revision';

    // Advertise this table as a possible base table.
    $data['arch_product_field_revision']['table']['base']['help'] = $this->t('Product revision is a history of changes to product.', [], ['context' => 'arch_product']);
    $data['arch_product_field_revision']['table']['base']['defaults']['title'] = 'title';

    $data['arch_product_field_revision']['pid']['argument'] = [
      'id' => 'product_pid',
      'numeric' => TRUE,
    ];
    // @todo the PID field needs different behaviour on revision/non-revision
    //   tables. It would be neat if this could be encoded in the base field
    //   definition.
    $data['arch_product_field_revision']['pid']['relationship']['id'] = 'standard';
    $data['arch_product_field_revision']['pid']['relationship']['base'] = 'arch_product_field_data';
    $data['arch_product_field_revision']['pid']['relationship']['base field'] = 'pid';
    $data['arch_product_field_revision']['pid']['relationship']['title'] = $this->t('Product', [], ['context' => 'arch_product']);
    $data['arch_product_field_revision']['pid']['relationship']['label'] = $this->t('Get the actual product from a product revision.', [], ['context' => 'arch_product']);

    $data['arch_product_field_revision']['vid'] = [
      'argument' => [
        'id' => 'product_vid',
        'numeric' => TRUE,
      ],
      'relationship' => [
        'id' => 'standard',
        'base' => 'arch_product_field_data',
        'base field' => 'vid',
        'title' => $this->t('Product', [], ['context' => 'arch_product']),
        'label' => $this->t('Get the actual product from a revision.', [], ['context' => 'arch_product']),
      ],
    ] + $data['arch_product_field_revision']['vid'];

    $data['arch_product_field_revision']['langcode']['help'] = $this->t('The language the original product is in.', [], ['context' => 'arch_product']);

    $data['arch_product_revision']['revision_uid']['help'] = $this->t('Relate a product revision to the user who created the revision.', [], ['context' => 'arch_product']);
    $data['arch_product_revision']['revision_uid']['relationship']['label'] = $this->t('revision user');

    $data['arch_product_field_revision']['table']['wizard_id'] = 'product_field_revision';

    $data['arch_product_field_revision']['table']['join']['arch_product_field_data']['left_field'] = 'vid';
    $data['arch_product_field_revision']['table']['join']['arch_product_field_data']['field'] = 'vid';

    $data['arch_product_field_revision']['status']['filter']['label'] = $this->t('Published');
    $data['arch_product_field_revision']['status']['filter']['type'] = 'yes-no';
    $data['arch_product_field_revision']['status']['filter']['use_equal'] = TRUE;

    $data['arch_product_field_revision']['promote']['help'] = $this->t('A boolean indicating whether the product is visible on the front page.', [], ['context' => 'arch_product']);

    $data['arch_product_field_revision']['sticky']['help'] = $this->t('A boolean indicating whether the product should sort to the top of product lists.', [], ['context' => 'arch_product']);

    $data['arch_product_field_revision']['langcode']['help'] = $this->t('The language of the product or translation.', [], ['context' => 'arch_product']);

    $data['arch_product_field_revision']['link_to_revision'] = [
      'field' => [
        'title' => $this->t('Link to revision'),
        'help' => $this->t('Provide a simple link to the revision.'),
        'id' => 'product_revision_link',
        'click sortable' => FALSE,
      ],
    ];

    $data['arch_product_field_revision']['revert_revision'] = [
      'field' => [
        'title' => $this->t('Link to revert revision'),
        'help' => $this->t('Provide a simple link to revert to the revision.'),
        'id' => 'product_revision_link_revert',
        'click sortable' => FALSE,
      ],
    ];

    $data['arch_product_field_revision']['delete_revision'] = [
      'field' => [
        'title' => $this->t('Link to delete revision'),
        'help' => $this->t('Provide a simple link to delete the product revision.'),
        'id' => 'product_revision_link_delete',
        'click sortable' => FALSE,
      ],
    ];

    // Define the base group of this table. Fields that don't have a group
    // defined will go into this field by default.
    $data['arch_product_access']['table']['group'] = $this->t('Content access');

    // For other base tables, explain how we join.
    $data['arch_product_access']['table']['join'] = [
      'arch_product_field_data' => [
        'left_field' => 'pid',
        'field' => 'pid',
      ],
    ];
    $data['arch_product_access']['pid'] = [
      'title' => $this->t('Access'),
      'help' => $this->t('Filter by access.'),
      'filter' => [
        'id' => 'product_access',
        'help' => $this->t('Filter for product by view access. <strong>Not necessary if you are using product as your base table.</strong>', [], ['context' => 'arch_product']),
      ],
    ];

    // Add search table, fields, filters, etc., but only if a page using the
    // product_search plugin is enabled.
    if (\Drupal::hasService('search.search_page_repository')) {
      $enabled = FALSE;
      $search_page_repository = \Drupal::service('search.search_page_repository');
      foreach ($search_page_repository->getActiveSearchpages() as $page) {
        if ($page->getPlugin()->getPluginId() == 'product_search') {
          $enabled = TRUE;
          break;
        }
      }

      if ($enabled) {
        $data['arch_product_search_index']['table']['group'] = $this->t('Search');

        // Automatically join to the product table (or actually,
        // product_field_data). Use a Views table alias to allow other modules
        // to use this table too, if they use the search index.
        $data['arch_product_search_index']['table']['join'] = [
          'arch_product_field_data' => [
            'left_field' => 'pid',
            'field' => 'sid',
            'table' => 'search_index',
            'extra' => "arch_product_search_index.type = 'product_search' AND arch_product_search_index.langcode = arch_product_field_data.langcode",
          ],
        ];

        $data['arch_product_search_total']['table']['join'] = [
          'arch_product_search_index' => [
            'left_field' => 'word',
            'field' => 'word',
          ],
        ];

        $data['arch_product_search_dataset']['table']['join'] = [
          'arch_product_field_data' => [
            'left_field' => 'sid',
            'left_table' => 'arch_product_search_index',
            'field' => 'sid',
            'table' => 'search_dataset',
            'extra' => 'arch_product_search_index.type = arch_product_search_dataset.type AND arch_product_search_index.langcode = arch_product_search_dataset.langcode',
            'type' => 'INNER',
          ],
        ];

        $data['arch_product_search_index']['score'] = [
          'title' => $this->t('Score'),
          'help' => $this->t('The score of the search item. This will not be used if the search filter is not also present.'),
          'field' => [
            'id' => 'search_score',
            'float' => TRUE,
            'no group by' => TRUE,
          ],
          'sort' => [
            'id' => 'search_score',
            'no group by' => TRUE,
          ],
        ];

        $data['arch_product_search_index']['keys'] = [
          'title' => $this->t('Search Keywords'),
          'help' => $this->t('The keywords to search for.'),
          'filter' => [
            'id' => 'search_keywords',
            'no group by' => TRUE,
            'search_type' => 'product_search',
          ],
          'argument' => [
            'id' => 'search',
            'no group by' => TRUE,
            'search_type' => 'product_search',
          ],
        ];

      }
    }

    return $data;
  }

}
