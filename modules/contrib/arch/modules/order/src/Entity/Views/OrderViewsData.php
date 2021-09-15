<?php

namespace Drupal\arch_order\Entity\Views;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the order entity type.
 */
class OrderViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['arch_order_field_data']['table']['base']['weight'] = -10;
    $data['arch_order_field_data']['table']['base']['access query tag'] = 'arch_order_access';
    $data['arch_order_field_data']['table']['wizard_id'] = 'order';

    $data['arch_order_field_data']['oid']['argument'] = [
      'id' => 'order_oid',
      'name field' => 'order_number',
      'numeric' => TRUE,
      'validate type' => 'oid',
    ];

    $data['arch_order_field_data']['oid']['field']['default_formatter_settings'] = ['link_to_entity' => TRUE];
    $data['arch_order_field_data']['oid']['field']['link_to_order default'] = TRUE;

    $data['arch_order_field_data']['sku']['field']['default_formatter_settings'] = ['link_to_entity' => TRUE];
    $data['arch_order_field_data']['sku']['field']['link_to_order default'] = TRUE;

    $data['arch_order_field_data']['type']['argument']['id'] = 'order_type';

    $data['arch_order_field_data']['langcode']['help'] = $this->t('The language of the order or translation.', [], ['context' => 'arch_order']);

    $data['arch_order']['status']['filter']['label'] = $this->t('Order status', [], ['context' => 'arch_order']);
    $data['arch_order']['status']['filter']['type'] = 'order_status';
    $data['arch_order']['status']['filter']['id'] = 'order_status';

    $data['arch_order_field_data']['status_extra'] = [
      'title' => $this->t('Published status or admin user'),
      'help' => $this->t('Filters out unpublished order if the current user cannot view it.', [], ['context' => 'arch_order']),
      'filter' => [
        'field' => 'status',
        'id' => 'order_status',
        'label' => $this->t('Published status or admin user'),
      ],
    ];

    $data['arch_order']['order_bulk_form'] = [
      'title' => $this->t('Order operations bulk form', [], ['context' => 'arch_order']),
      'help' => $this->t('Add a form element that lets you run operations on multiple orders.', [], ['context' => 'arch_order']),
      'field' => [
        'id' => 'order_bulk_form',
      ],
    ];

    // Bogus fields for aliasing purposes.
    // @todo Add similar support to any date field
    // @see https://www.drupal.org/node/2337507
    $data['arch_order_field_data']['created_fulldate'] = [
      'title' => $this->t('Created date'),
      'help' => $this->t('Date in the form of CCYYMMDD.'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_fulldate',
      ],
    ];

    $data['arch_order_field_data']['created_year_month'] = [
      'title' => $this->t('Created year + month'),
      'help' => $this->t('Date in the form of YYYYMM.'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_year_month',
      ],
    ];

    $data['arch_order_field_data']['created_year'] = [
      'title' => $this->t('Created year'),
      'help' => $this->t('Date in the form of YYYY.'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_year',
      ],
    ];

    $data['arch_order_field_data']['created_month'] = [
      'title' => $this->t('Created month'),
      'help' => $this->t('Date in the form of MM (01 - 12).'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_month',
      ],
    ];

    $data['arch_order_field_data']['created_day'] = [
      'title' => $this->t('Created day'),
      'help' => $this->t('Date in the form of DD (01 - 31).'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_day',
      ],
    ];

    $data['arch_order_field_data']['created_week'] = [
      'title' => $this->t('Created week'),
      'help' => $this->t('Date in the form of WW (01 - 53).'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_week',
      ],
    ];

    $data['arch_order_field_data']['changed_fulldate'] = [
      'title' => $this->t('Updated date'),
      'help' => $this->t('Date in the form of CCYYMMDD.'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_fulldate',
      ],
    ];

    $data['arch_order_field_data']['changed_year_month'] = [
      'title' => $this->t('Updated year + month'),
      'help' => $this->t('Date in the form of YYYYMM.'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_year_month',
      ],
    ];

    $data['arch_order_field_data']['changed_year'] = [
      'title' => $this->t('Updated year'),
      'help' => $this->t('Date in the form of YYYY.'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_year',
      ],
    ];

    $data['arch_order_field_data']['changed_month'] = [
      'title' => $this->t('Updated month'),
      'help' => $this->t('Date in the form of MM (01 - 12).'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_month',
      ],
    ];

    $data['arch_order_field_data']['changed_day'] = [
      'title' => $this->t('Updated day'),
      'help' => $this->t('Date in the form of DD (01 - 31).'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_day',
      ],
    ];

    $data['arch_order_field_data']['changed_week'] = [
      'title' => $this->t('Updated week'),
      'help' => $this->t('Date in the form of WW (01 - 53).'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_week',
      ],
    ];

    $data['arch_order_field_data']['uid']['help'] = $this->t('The creator user of the order. If you need more fields than the uid add the order: author relationship', [], ['context' => 'arch_order']);
    $data['arch_order_field_data']['uid']['filter']['id'] = 'user_name';
    $data['arch_order_field_data']['uid']['relationship']['title'] = $this->t('Customer', [], ['context' => 'arch_order']);
    $data['arch_order_field_data']['uid']['relationship']['help'] = $this->t('Relate order to the user who created it.', [], ['context' => 'arch_order']);
    $data['arch_order_field_data']['uid']['relationship']['label'] = $this->t('author', [], ['context' => 'arch_order']);

    $data['arch_order_field_data']['uid_revision']['title'] = $this->t('User has a revision');
    $data['arch_order_field_data']['uid_revision']['help'] = $this->t('All orders where a certain user has a revision', [], ['context' => 'arch_order']);
    $data['arch_order_field_data']['uid_revision']['real field'] = 'oid';
    $data['arch_order_field_data']['uid_revision']['filter']['id'] = 'order_uid_revision';
    $data['arch_order_field_data']['uid_revision']['argument']['id'] = 'order_uid_revision';

    $data['arch_order_field_revision']['table']['wizard_id'] = 'order_revision';

    // Advertise this table as a possible base table.
    $data['arch_order_field_revision']['table']['base']['help'] = $this->t('Order revision is a history of changes to order.', [], ['context' => 'arch_order']);
    $data['arch_order_field_revision']['table']['base']['defaults']['title'] = 'title';

    $data['arch_order_field_revision']['oid']['argument'] = [
      'id' => 'order_oid',
      'numeric' => TRUE,
    ];
    // @todo the PID field needs different behaviour on revision/non-revision
    //   tables. It would be neat if this could be encoded in the base field
    //   definition.
    $data['arch_order_field_revision']['oid']['relationship']['id'] = 'standard';
    $data['arch_order_field_revision']['oid']['relationship']['base'] = 'arch_order_field_data';
    $data['arch_order_field_revision']['oid']['relationship']['base field'] = 'oid';
    $data['arch_order_field_revision']['oid']['relationship']['title'] = $this->t('Order', [], ['context' => 'arch_order']);
    $data['arch_order_field_revision']['oid']['relationship']['label'] = $this->t('Get the actual order from a order revision.', [], ['context' => 'arch_order']);

    $data['arch_order_field_revision']['langcode']['help'] = $this->t('The language the original order is in.', [], ['context' => 'arch_order']);

    $data['arch_order_revision']['revision_uid']['help'] = $this->t('Relate a order revision to the user who created the revision.', [], ['context' => 'arch_order']);
    $data['arch_order_revision']['revision_uid']['relationship']['label'] = $this->t('revision user');

    $data['arch_order_field_revision']['table']['wizard_id'] = 'order_field_revision';

    $data['arch_order_field_revision']['table']['join']['arch_order_field_data']['left_field'] = 'vid';
    $data['arch_order_field_revision']['table']['join']['arch_order_field_data']['field'] = 'vid';

    $data['arch_order_field_revision']['link_to_revision'] = [
      'field' => [
        'title' => $this->t('Link to revision'),
        'help' => $this->t('Provide a simple link to the revision.'),
        'id' => 'order_revision_link',
        'click sortable' => FALSE,
      ],
    ];

    $data['arch_order_field_revision']['revert_revision'] = [
      'field' => [
        'title' => $this->t('Link to revert revision'),
        'help' => $this->t('Provide a simple link to revert to the revision.'),
        'id' => 'order_revision_link_revert',
        'click sortable' => FALSE,
      ],
    ];

    $data['arch_order_field_revision']['delete_revision'] = [
      'field' => [
        'title' => $this->t('Link to delete revision'),
        'help' => $this->t('Provide a simple link to delete the order revision.'),
        'id' => 'order_revision_link_delete',
        'click sortable' => FALSE,
      ],
    ];

    // Define the base group of this table. Fields that don't have a group
    // defined will go into this field by default.
    $data['arch_order_access']['table']['group'] = $this->t('Content access');

    // For other base tables, explain how we join.
    $data['arch_order_access']['table']['join'] = [
      'arch_order_field_data' => [
        'left_field' => 'oid',
        'field' => 'oid',
      ],
    ];
    $data['arch_order_access']['oid'] = [
      'title' => $this->t('Access'),
      'help' => $this->t('Filter by access.'),
      'filter' => [
        'id' => 'order_access',
        'help' => $this->t('Filter for order by view access. <strong>Not necessary if you are using order as your base table.</strong>', [], ['context' => 'arch_order']),
      ],
    ];

    // Add search table, fields, filters, etc., but only if a page using the
    // order_search plugin is enabled.
    if (\Drupal::hasService('search.search_page_repository')) {
      $enabled = FALSE;
      $search_page_repository = \Drupal::service('search.search_page_repository');
      foreach ($search_page_repository->getActiveSearchpages() as $page) {
        if ($page->getPlugin()->getPluginId() == 'order_search') {
          $enabled = TRUE;
          break;
        }
      }

      if ($enabled) {
        $data['arch_order_search_index']['table']['group'] = $this->t('Search');

        // Automatically join to the order table (or actually,
        // order_field_data). Use a Views table alias to allow other modules
        // to use this table too, if they use the search index.
        $data['arch_order_search_index']['table']['join'] = [
          'arch_order_field_data' => [
            'left_field' => 'oid',
            'field' => 'sid',
            'table' => 'search_index',
            'extra' => "arch_order_search_index.type = 'order_search' AND arch_order_search_index.langcode = arch_order_field_data.langcode",
          ],
        ];

        $data['arch_order_search_total']['table']['join'] = [
          'arch_order_search_index' => [
            'left_field' => 'word',
            'field' => 'word',
          ],
        ];

        $data['arch_order_search_dataset']['table']['join'] = [
          'arch_order_field_data' => [
            'left_field' => 'sid',
            'left_table' => 'arch_order_search_index',
            'field' => 'sid',
            'table' => 'search_dataset',
            'extra' => 'arch_order_search_index.type = arch_order_search_dataset.type AND arch_order_search_index.langcode = arch_order_search_dataset.langcode',
            'type' => 'INNER',
          ],
        ];

        $data['arch_order_search_index']['score'] = [
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

        $data['arch_order_search_index']['keys'] = [
          'title' => $this->t('Search Keywords'),
          'help' => $this->t('The keywords to search for.'),
          'filter' => [
            'id' => 'search_keywords',
            'no group by' => TRUE,
            'search_type' => 'order_search',
          ],
          'argument' => [
            'id' => 'search',
            'no group by' => TRUE,
            'search_type' => 'order_search',
          ],
        ];

      }
    }

    return $data;
  }

}
