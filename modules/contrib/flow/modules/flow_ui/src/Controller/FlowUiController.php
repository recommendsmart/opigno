<?php

namespace Drupal\flow_ui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\flow\Entity\Flow;
use Drupal\flow\FlowCompatibility;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for managing flow configurations via UI.
 */
class FlowUiController extends ControllerBase {

  /**
   * Returns a form for a Flow configuration.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle.
   * @param string $flow_task_mode
   *   The task mode.
   */
  public function flowForm(string $entity_type_id, string $bundle, string $flow_task_mode): array {
    $config = Flow::getFlow($entity_type_id, $bundle, $flow_task_mode);

    return [
      'form' => $this->entityFormBuilder()->getForm($config, 'default'),
    ];
  }

  /**
   * Returns a form for adding a new task to a Flow configuration.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle.
   * @param string $flow_task_mode
   *   The task mode.
   * @param string $flow_task_plugin
   *   The ID that identifies the type of task plugin to use.
   * @param string $flow_subject_plugin
   *   The ID that identifies the type of subject plugin to use.
   */
  public function taskAddForm(string $entity_type_id, string $bundle, string $flow_task_mode, string $flow_task_plugin, string $flow_subject_plugin): array {
    $config = Flow::getFlow($entity_type_id, $bundle, $flow_task_mode);

    /** @var \Drupal\flow\Plugin\FlowTaskManager $task_manager */
    $task_manager = \Drupal::service('plugin.manager.flow.task');
    if (!$task_manager->hasDefinition($flow_task_plugin)) {
      throw new NotFoundHttpException();
    }

    /** @var \Drupal\flow\Plugin\FlowSubjectManager $subject_manager */
    $subject_manager = \Drupal::service('plugin.manager.flow.subject');
    if (!$subject_manager->hasDefinition($flow_subject_plugin)) {
      throw new NotFoundHttpException();
    }

    $flow_keys = [
      'entity_type_id' => $entity_type_id,
      'bundle' => $bundle,
      'task_mode' => $flow_task_mode,
    ];
    $task = $task_manager->createInstance($flow_task_plugin, $flow_keys);
    $subject = $subject_manager->createInstance($flow_subject_plugin, $flow_keys);

    // We don't allow incompatible components.
    if (!FlowCompatibility::validate($config, $task, $subject)) {
      throw new NotFoundHttpException();
    }

    return [
      'form' => $this->formBuilder()->getForm('Drupal\flow_ui\Form\TaskForm', $config, $task, $subject, $config->getTasks()->count()),
    ];
  }

  /**
   * Returns a form for editing a task plugin configuration.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle.
   * @param string $flow_task_mode
   *   The task mode.
   * @param int $flow_task_index
   *   The index that is the position of the task plugin in the Flow config.
   */
  public function taskEditForm(string $entity_type_id, string $bundle, string $flow_task_mode, int $flow_task_index): array {
    $config = Flow::getFlow($entity_type_id, $bundle, $flow_task_mode);

    $tasks = $config->getTasks();
    $subjects = $config->getSubjects();
    if (!$tasks->has($flow_task_index) || !$subjects->has($flow_task_index)) {
      throw new NotFoundHttpException();
    }
    $task = $tasks->get($flow_task_index);
    $subject = $subjects->get($flow_task_index);

    return [
      'form' => $this->formBuilder()->getForm('Drupal\flow_ui\Form\TaskForm', $config, $task, $subject, $flow_task_index),
    ];
  }

  /**
   * Returns a form for enabling a configured task plugin.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle.
   * @param string $flow_task_mode
   *   The task mode.
   * @param int $flow_task_index
   *   The index that is the position of the task plugin in the Flow config.
   */
  public function taskEnableForm(string $entity_type_id, string $bundle, string $flow_task_mode, int $flow_task_index): array {
    $config = Flow::getFlow($entity_type_id, $bundle, $flow_task_mode);

    $tasks = $config->getTasks();
    $subjects = $config->getSubjects();
    if (!$tasks->has($flow_task_index) || !$subjects->has($flow_task_index)) {
      throw new NotFoundHttpException();
    }
    $task = $tasks->get($flow_task_index);
    $subject = $subjects->get($flow_task_index);

    return [
      'form' => $this->formBuilder()->getForm('Drupal\flow_ui\Form\TaskEnableForm', $config, $task, $subject, $flow_task_index),
    ];
  }

  /**
   * Returns a form for disabling a configured task plugin.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle.
   * @param string $flow_task_mode
   *   The task mode.
   * @param int $flow_task_index
   *   The index that is the position of the task plugin in the Flow config.
   */
  public function taskDisableForm(string $entity_type_id, string $bundle, string $flow_task_mode, int $flow_task_index): array {
    $config = Flow::getFlow($entity_type_id, $bundle, $flow_task_mode);

    $tasks = $config->getTasks();
    $subjects = $config->getSubjects();
    if (!$tasks->has($flow_task_index) || !$subjects->has($flow_task_index)) {
      throw new NotFoundHttpException();
    }
    $task = $tasks->get($flow_task_index);
    $subject = $subjects->get($flow_task_index);

    return [
      'form' => $this->formBuilder()->getForm('Drupal\flow_ui\Form\TaskDisableForm', $config, $task, $subject, $flow_task_index),
    ];
  }

  /**
   * Returns a form for deleting a configured task plugin.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle.
   * @param string $flow_task_mode
   *   The task mode.
   * @param int $flow_task_index
   *   The index that is the position of the task plugin in the Flow config.
   */
  public function taskDeleteForm(string $entity_type_id, string $bundle, string $flow_task_mode, int $flow_task_index): array {
    $config = Flow::getFlow($entity_type_id, $bundle, $flow_task_mode);

    $tasks = $config->getTasks();
    $subjects = $config->getSubjects();
    if (!$tasks->has($flow_task_index) || !$subjects->has($flow_task_index)) {
      throw new NotFoundHttpException();
    }
    $task = $tasks->get($flow_task_index);
    $subject = $subjects->get($flow_task_index);

    return [
      'form' => $this->formBuilder()->getForm('Drupal\flow_ui\Form\TaskDeleteForm', $config, $task, $subject, $flow_task_index),
    ];
  }

}
