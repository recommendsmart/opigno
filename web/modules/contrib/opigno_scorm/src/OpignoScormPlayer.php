<?php

namespace Drupal\opigno_scorm;

use Drupal\Core\Database\Connection;

/**
 * Class OpignoScormPlayer.
 */
class OpignoScormPlayer {

  protected $database;

  protected $scorm_service;

  /**
   * OpignoScormPlayer constructor.
   */
  public function __construct(Connection $database, OpignoScorm $scorm_service) {
    $this->database = $database;
    $this->scorm_service = $scorm_service;
  }

  /**
   * Build rendarable array for scorm package output.
   */
  public function toRendarableArray($scorm) {
    $account = \Drupal::currentUser();
    $uid = $account->id();
    // Get SCORM API version.
    $metadata = unserialize($scorm->metadata);
    if (strpos($metadata['schemaversion'], '1.2') !== FALSE) {
      $scorm_version = '1.2';
    }
    else {
      $scorm_version = '2004';
    }

    // Get the SCO tree.
    $tree = $this->opignoScormPlayerScormTree($scorm);
    $flat_tree = $this->opignoScormPlayerFlattenTree($tree);

    // Get the start SCO.
    $start_sco = $this->opignoScormPlayerStartSco($flat_tree);

    /* @todo Replace with custom event subscriber implementation. */
    // Get implemented CMI paths.
    $paths = opigno_scorm_add_cmi_paths($scorm_version);

    // Get CMI data for each SCO.
    $data = opigno_scorm_add_cmi_data($scorm, $flat_tree, $scorm_version);

    // Disallow SCORM package resuming for anonymous.
    if ($uid === 0 && $scorm_version == '1.2') {
      $data['cmi.core.lesson_location'] = '';
      $data['cmi.core.lesson_status'] = '';
      $data['cmi.core.exit'] = '';
      $data['cmi.core.entry'] = '';
      $data['cmi.core.student_id'] = '';
      $data['cmi.core.student_name'] = '';
      $data['cmi.student_preference._children'] = '';
      $data['cmi.student_preference.audio'] = '';
      $data['cmi.student_preference.language'] = '';
      $data['cmi.student_preference.speed'] = '';
      $data['cmi.student_preference.text'] = '';
      $data['cmi.core.score._children'] = '';
      $data['cmi.suspend_data'] = '';
      if (!empty($data['cmi.objectives'])) {
        foreach ($data['cmi.objectives'] as &$objective) {
          $objective['score']->raw = 0;
          $objective['score']->min = 0;
          $objective['score']->max = 0;
          $objective['status'] = '';
        }
      }
    }
    if ($uid === 0 && $scorm_version == '2004') {
      $data['cmi.location'] = '';
      $data['cmi.completion_status'] = 'unknown';
      $data['cmi.exit'] = '';
      $data['cmi.entry'] = '';
      $data['cmi.learner_id'] = '';
      $data['cmi.learner_name'] = '';
      $data['cmi.learner_preference._children'] = '';
      $data['cmi.learner_preference.audio_level'] = '';
      $data['cmi.learner_preference.language'] = '';
      $data['cmi.learner_preference.delivery_speed'] = '';
      $data['cmi.learner_preference.audio_captioning'] = '';
      $data['cmi.success_status'] = '';
      $data['cmi.suspend_data'] = '';

      if (!empty($data['cmi.objectives'])) {
        foreach ($data['cmi.objectives'] as &$objective) {
          $objective['score']->scaled = 0;
          $objective['score']->raw = 0;
          $objective['score']->min = 0;
          $objective['score']->max = 0;
          $objective['success_status'] = '';
          $objective['completion_status'] = '';
          $objective['progress_measure'] = '';
        }
      }
    }

    $sco_identifiers = [];
    $scos_suspend_data = [];
    foreach ($flat_tree as $sco) {
      if ($sco->scorm_type == 'sco') {
        $sco_identifiers[$sco->identifier] = $sco->id;
        $scos_suspend_data[$sco->id] = opigno_scorm_scorm_cmi_get($uid, $scorm->id, 'cmi.suspend_data.' . $sco->id, '');
      }
    }
    $last_user_sco = opigno_scorm_scorm_cmi_get($uid, $scorm->id, 'user.sco', '');
    if ($last_user_sco != '') {
      foreach ($flat_tree as $sco) {
        if ($last_user_sco == $sco->id && !empty($sco->launch)) {
          $start_sco = $sco;
        }
      }
    }
    // Add base path for player link.
    global $base_path;
    $start_sco->base_path = $base_path;
    return [
      '#theme' => 'opigno_scorm__player',
      '#scorm_id' => $scorm->id,
      '#tree' => count($flat_tree) == 2 ? NULL : $tree,
      '#start_sco' => $start_sco,
      '#attached' => [
        'library' => ['opigno_scorm/opigno-scorm-player'],
        'drupalSettings' => [
          'opignoScormUIPlayer' => [
            'cmiPaths' => $paths,
            'cmiData' => $data,
            'scoIdentifiers' => $sco_identifiers,
            'cmiSuspendItems' => $scos_suspend_data,
          ],
          'scormVersion' => $scorm_version,
        ],
      ],
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Traverse the SCORM package data and construct a SCO tree.
   *
   * @param object $scorm
   *   Scorm object.
   * @param int $parent_identifier
   *   Parent identifier.
   *
   * @return array
   *   SCO tree.
   */
  private function opignoScormPlayerScormTree($scorm, $parent_identifier = 0) {
    $conenction = $this->database;
    $tree = [];

    $result = $conenction->select('opigno_scorm_package_scos', 'sco')
      ->fields('sco', ['id'])
      ->condition('sco.scorm_id', $scorm->id)
      ->condition('sco.parent_identifier', $parent_identifier)
      ->execute();

    while ($sco_id = $result->fetchField()) {
      $sco = $this->scorm_service->scormLoadSco($sco_id);

      $children = $this->opignoScormPlayerScormTree($scorm, $sco->identifier);

      $sco->children = $children;

      $tree[] = $sco;
    }

    return $tree;
  }

  /**
   * Helper function to flatten the SCORM tree.
   *
   * @param array $tree
   *   Tree.
   *
   * @return array
   *   SCORM tree.
   */
  private function opignoScormPlayerFlattenTree(array $tree) {
    $items = [];

    if (!empty($tree)) {
      foreach ($tree as $sco) {
        $items[] = $sco;
        if (!empty($sco->children)) {
          $items = array_merge($items, $this->opignoScormPlayerFlattenTree($sco->children));
        }
      }
    }

    return $items;
  }

  /**
   * Determine the start SCO for the SCORM package.
   *
   * @todo Get last viewed SCO.
   *
   * @param array $flat_tree
   *   Flat tree.
   *
   * @return object
   *   Start SCO.
   */
  private function opignoScormPlayerStartSco(array $flat_tree) {
    foreach ($flat_tree as $sco) {
      if (!empty($sco->launch)) {
        return $sco;
      }
    }

    // Failsafe. Just get the first element.
    return array_shift($flat_tree);
  }

}
