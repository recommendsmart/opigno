<?php

namespace Drupal\flow_ui\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\flow\Flow;
use Drupal\flow\FlowCompatibility;
use Drupal\flow\FlowTaskMode;
use Drupal\flow\Plugin\FlowSubjectManager;
use Drupal\flow\Plugin\FlowTaskManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for a Flow configuration.
 */
class FlowForm extends EntityForm {

  /**
   * The Flow task manager.
   *
   * @var \Drupal\flow\Plugin\FlowTaskManager
   */
  protected FlowTaskManager $taskManager;

  /**
   * The Flow subject manager.
   *
   * @var \Drupal\flow\Plugin\FlowSubjectManager
   */
  protected FlowSubjectManager $subjectManager;

  /**
   * Constructs a new FlowForm.
   *
   * @param \Drupal\flow\Plugin\FlowTaskManager $task_manager
   *   The Flow task manager.
   * @param \Drupal\flow\Plugin\FlowSubjectManager $subject_manager
   *   The Flow subject manager.
   */
  public function __construct(FlowTaskManager $task_manager, FlowSubjectManager $subject_manager) {
    $this->taskManager = $task_manager;
    $this->subjectManager = $subject_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static($container->get('plugin.manager.flow.task'), $container->get('plugin.manager.flow.subject'));
    $instance->setEntityTypeManager($container->get('entity_type.manager'));
    $instance->setModuleHandler($container->get('module_handler'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\flow\Entity\FlowInterface $flow */
    $flow = $this->entity;
    $target_type = $this->entityTypeManager->getDefinition($flow->getTargetEntityTypeId());
    $bundle_type_id = $target_type->getBundleEntityType() ?: 'bundle';

    $form = parent::buildForm($form, $form_state);
    $weight = 0;

    $execution_options = [
      'now' => $this->t('On @mode', ['@mode' => $flow->getTaskMode()]),
      'after' => $this->t('After @mode', ['@mode' => $flow->getTaskMode()]),
      'queue' => $this->t('Background queue'),
    ];

    $form['active'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Active tasks'),
      '#weight' => $weight++,
    ];
    $grouped_tasks = [
      'active' => $flow->getTasks(Flow::$filter),
      'inactive' => $flow->getTasks(['active' => FALSE]),
    ];
    $grouped_subjects = [
      'active' => $flow->getSubjects(Flow::$filter),
      'inactive' => $flow->getSubjects(['active' => FALSE]),
    ];
    foreach ($grouped_tasks as $group => $tasks) {
      $subjects = $grouped_subjects[$group];
      if ($tasks->count()) {
        if ($group === 'inactive') {
          $form['inactive'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Tasks not active'),
            '#weight' => $weight++,
          ];
        }
        $form[$group]['tasks'] = [
          '#attributes' => ['id' => Html::getUniqueId('flow-' . $group . '-tasks')],
          '#type' => 'table',
          '#parents' => ['tasks'],
          '#header' =>
            [
              $this->t('Weight'),
              $this->t('Task'),
              $this->t('Subject'),
              $this->t('Execution'),
              $this->t('Operations'),
            ],
          '#weight' => 10,
          '#tabledrag' => [
            [
              'action' => 'order',
              'relationship' => 'sibling',
              'group' => 'flow-task-weight',
            ],
          ],
        ];
        /** @var \Drupal\flow\Plugin\FlowTaskBase $task */
        foreach ($tasks as $i => $task) {
          $subject = $subjects->get($i);
          $task_configuration = $task->getConfiguration();
          $form[$group]['tasks'][$i] = [
            '#attributes' => ['class' => ['draggable']],
            '#weight' => $i,
          ];
          $form[$group]['tasks'][$i]['weight'] = [
            '#type' => 'weight',
            '#title' => $this->t('Weight'),
            '#title_display' => 'invisible',
            '#default_value' => $task_configuration['weight'],
            '#attributes' => ['class' => ['flow-task-weight']],
            '#delta' => 50,
            '#weight' => 10,
          ];
          $form[$group]['tasks'][$i]['task'] = [
            '#type' => 'markup',
            '#markup' => $task->getPluginDefinition()['label'],
            '#weight' => 20,
          ];
          $form[$group]['tasks'][$i]['subject'] = [
            '#type' => 'markup',
            '#markup' => $subject->getPluginDefinition()['label'],
            '#weight' => 30,
          ];
          $form[$group]['tasks'][$i]['execution'] = [
            '#type' => 'markup',
            '#markup' => $execution_options[$task->configuration()['execution']['start'] ?? 'now'],
            '#weight' => 40,
          ];
          $operations = [];
          if ($flow->access('update')) {
            $operations['edit'] = [
              'title' => $this->t('Edit'),
              'url' => Url::fromRoute("flow.task.{$target_type->id()}.edit", [
                'entity_type_id' => $target_type->id(),
                $bundle_type_id => $flow->getTargetBundle(),
                'flow_task_mode' => $flow->getTaskMode(),
                'flow_task_index' => $i,
              ]),
              'weight' => 10,
            ];
            if ($group === 'active') {
              $operations['disable'] = [
                'title' => $this->t('Disable'),
                'url' => Url::fromRoute("flow.task.{$target_type->id()}.disable", [
                  'entity_type_id' => $target_type->id(),
                  $bundle_type_id => $flow->getTargetBundle(),
                  'flow_task_mode' => $flow->getTaskMode(),
                  'flow_task_index' => $i,
                ]),
                'weight' => 20,
              ];
            }
            elseif ($group === 'inactive') {
              $operations['enable'] = [
                'title' => $this->t('Enable'),
                'url' => Url::fromRoute("flow.task.{$target_type->id()}.enable", [
                  'entity_type_id' => $target_type->id(),
                  $bundle_type_id => $flow->getTargetBundle(),
                  'flow_task_mode' => $flow->getTaskMode(),
                  'flow_task_index' => $i,
                ]),
                'weight' => 20,
              ];
            }
          }
          if ($flow->access('delete') && $group === 'inactive') {
            $operations['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute("flow.task.{$target_type->id()}.delete", [
                'entity_type_id' => $target_type->id(),
                $bundle_type_id => $flow->getTargetBundle(),
                'flow_task_mode' => $flow->getTaskMode(),
                'flow_task_index' => $i,
              ]),
              'weight' => 30,
            ];
          }
          $form[$group]['tasks'][$i]['operations'] = [
            '#type' => 'operations',
            '#links' => $operations,
            '#weight' => 50,
          ];
        }
      }
      elseif ($group === 'active') {
        $form[$group]['tasks']['empty'] = [
          '#type' => 'markup',
          '#markup' => $this->t('No tasks have been added yet.'),
          '#weight' => 10,
        ];
      }
    }

    $weight += 100;
    $wrapper_id = Html::getUniqueId('flow-add-task');
    $form['add_task'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Add new task'),
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
      '#weight' => $weight++,
    ];
    $form['add_task']['table'] = [
      '#type' => 'table',
      '#weight' => 10,
      '#header' => [$this->t('Task'), $this->t('Subject'), ''],
      '#attributes' => [
        'class' => ['flow-form-add-task'],
      ],
    ];
    // As the select lists may be large, use the select2 widget when available.
    $select_widget = $this->moduleHandler->moduleExists('select2') ? 'select2' : 'select';
    $form['add_task']['table'][0] = [
      '#parents' => ['add_task'],
    ];
    $form['add_task']['table'][0]['task'] = [
      '#type' => $select_widget,
      '#options' => $this->getTaskOptions($form, $form_state),
      '#default_value' => '_none',
      '#empty_value' => '_none',
      '#weight' => 10,
      '#ajax' => [
        'callback' => [static::class, 'addTaskAjax'],
        'wrapper' => $wrapper_id,
      ],
    ];
    $form['add_task']['table'][0]['subject'] = [
      '#type' => $select_widget,
      '#options' => $this->getSubjectOptions($form, $form_state),
      '#default_value' => '_none',
      '#empty_value' => '_none',
      '#weight' => 20,
      '#ajax' => [
        'callback' => [static::class, 'addTaskAjax'],
        'wrapper' => $wrapper_id,
      ],
    ];
    if ($form_state->getValue(['add_task', 'task'], '_none') !== '_none'
      && $form_state->getValue(['add_task', 'subject'], '_none') !== '_none') {
      $form['add_task']['table'][0]['link'] = [
        '#type' => 'link',
        '#attributes' => [
          'class' => ['button', 'button-action', 'button--primary'],
        ],
        '#title' => $this->t('Add task'),
        '#url' => Url::fromRoute("flow.task.{$target_type->id()}.add", [
          'entity_type_id' => $target_type->id(),
          $bundle_type_id => $flow->getTargetBundle(),
          'flow_task_mode' => $flow->getTaskMode(),
          'flow_task_plugin' => $form_state->getValue(['add_task', 'task']),
          'flow_subject_plugin' => $form_state->getValue(['add_task', 'subject']),
        ]),
      ];
    }

    $form['actions']['#weight'] = $weight++;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (!$this->entity->access('update')) {
      return;
    }
    // Remove button and internal Form API values from submitted values.
    $form_state->cleanValues();
    /** @var \Drupal\flow\Entity\FlowInterface $flow */
    $flow = $this->entity;
    // The only thing that can be done within this form is changing the weight
    // order of the configured task plugins. Update the new weights accordingly.
    $tasks_array = $flow->get('tasks');
    foreach ($form_state->getValue('tasks', []) as $i => $task_values) {
      $tasks_array[$i]['weight'] = $task_values['weight'];
    }
    $flow->setTasks($tasks_array);
  }

  /**
   * {@inheritdoc}
   */
  public function afterBuild(array $element, FormStateInterface $form_state) {
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\flow\Entity\FlowInterface $config */
    $config = $this->entity;

    $config->save();

    $task_modes = FlowTaskMode::service()->getAvailableTaskModes();

    $t_args = [
      '%task_mode' => $task_modes[$config->getTaskMode()],
      '%type' => \Drupal::entityTypeManager()->getDefinition($config->getTargetEntityTypeId())->getLabel(),
    ];
    $message = $this->t('The %task_mode flow configuration for %type has been saved.', $t_args);

    $this->messenger()->addStatus($message);
  }

  /**
   * Get available subject options.
   *
   * @param array $form
   *   The current form build array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The according form state.
   *
   * @return array
   *   The subject options.
   */
  protected function getSubjectOptions(array $form, FormStateInterface $form_state): array {
    /** @var \Drupal\flow\Entity\FlowInterface $config */
    $config = $this->entity;
    $flow_keys = [
      'entity_type_id' => $config->getTargetEntityTypeId(),
      'bundle' => $config->getTargetBundle(),
      'task_mode' => $config->getTaskMode(),
    ];

    $options = ['_none' => $this->t('- Select a subject -')];
    $task_plugin_id = $form_state->getValue(['add_task', 'task'], '_none');
    $task_plugins = [];
    if ($task_plugin_id !== '_none') {
      $task_plugins[] = $this->taskManager->createInstance($task_plugin_id, $flow_keys);
    }
    else {
      foreach (array_keys($this->taskManager->getDefinitions()) as $task_plugin_id) {
        $task_plugins[] = $this->taskManager->createInstance($task_plugin_id, $flow_keys);
      }
    }
    foreach ($this->subjectManager->getDefinitions() as $id => $definition) {
      $plugin = $this->subjectManager->createInstance($id, $flow_keys);
      $is_compatible = FALSE;
      foreach ($task_plugins as $task_plugin) {
        if (FlowCompatibility::validate($this->entity, $plugin, $task_plugin)) {
          $is_compatible = TRUE;
          break;
        }
      }
      if ($is_compatible) {
        $options[$id] = $definition['label'];
      }
    }
    return $options;
  }

  /**
   * Get available task options.
   *
   * @param array $form
   *   The current form build array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The according form state.
   *
   * @return array
   *   The task options.
   */
  protected function getTaskOptions(array $form, FormStateInterface $form_state): array {
    /** @var \Drupal\flow\Entity\FlowInterface $config */
    $config = $this->entity;
    $flow_keys = [
      'entity_type_id' => $config->getTargetEntityTypeId(),
      'bundle' => $config->getTargetBundle(),
      'task_mode' => $config->getTaskMode(),
    ];

    $options = ['_none' => $this->t('- Select a task -')];
    $subject_plugin_id = $form_state->getValue(['add_task', 'subject'], '_none');
    $subject_plugins = [];
    if ($subject_plugin_id !== '_none') {
      $subject_plugins[] = $this->subjectManager->createInstance($subject_plugin_id, $flow_keys);
    }
    else {
      foreach (array_keys($this->subjectManager->getDefinitions()) as $subject_plugin_id) {
        $subject_plugins[] = $this->subjectManager->createInstance($subject_plugin_id, $flow_keys);
      }
    }
    foreach ($this->taskManager->getDefinitions() as $id => $definition) {
      $plugin = $this->taskManager->createInstance($id, $flow_keys);
      $is_compatible = FALSE;
      foreach ($subject_plugins as $subject_plugin) {
        if (FlowCompatibility::validate($this->entity, $plugin, $subject_plugin)) {
          $is_compatible = TRUE;
          break;
        }
      }
      if ($is_compatible) {
        $options[$id] = $definition['label'];
      }
    }
    return $options;
  }

  /**
   * Ajax callback for adding a new task.
   *
   * @param array $form
   *   The current form build array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The according form state.
   *
   * @return array
   *   The part of the form that got refreshed via Ajax.
   */
  public static function addTaskAjax(array $form, FormStateInterface $form_state): array {
    return $form['add_task'];
  }

}
