<?php

namespace Drupal\aggrid;

use Drupal\aggrid\Entity\Aggrid;

/**
 * Helper functions for agGrid config Entities
 *
 */
class AggridConfigHelpers {

  public function getDefaults($aggrid_id) {
    $aggridDefault = [];

    // Get config for aggrid.
    $config = \Drupal::config('aggrid.general');
    // Set the aggrid setting variable
    $aggridgsjson = json_decode($config->get('aggridgsjson'));
    // Get the global aggrid row settings
    if(isset($aggridgsjson->rowSettings)) {
      $gsRowSettings = json_decode(json_encode($aggridgsjson->rowSettings));
    }

    // Fetch aggrid entity
    $aggridEntity = Aggrid::load($aggrid_id);

    if (!empty($aggridEntity)) {
      $aggridDefault['default'] = json_decode($aggridEntity->get('aggridDefault'));

      $aggridDefault['addOptions'] = @json_decode($aggridEntity->get('addOptions'));

      $aggridRowSettings = @json_decode(json_encode($aggridDefault['default']->rowSettings), true);
      if (isset($gsRowSettings) && isset($aggridRowSettings)) {
        $aggridRowSettings = array_replace_recursive($gsRowSettings, $aggridRowSettings);
      }
      $aggridDefault['aggridRowSettings'] = $aggridRowSettings;
    }

    return $aggridDefault;
  }

  public function getHeaders($columnDefs) {

    $getHeaders = [];

    $columns = [];
    $columnFields = "";

    // Build table.
    $rowIndex = 0;
    $colIndex = 0;

    foreach ($columnDefs as $column) {
      $rowIndex = $rowIndex > 1 ? $rowIndex : 1;
      $colIndex++;

      $columns[1][$colIndex][$colIndex][$column->headerName] = [];
      $columns[1][$colIndex][$colIndex][$column->headerName]['headerName'] = $column->headerName;
      $columns[1][$colIndex][$colIndex][$column->headerName]['field'] = isset($column->field) ? $column->field : NULL;
      $columns[1][$colIndex][$colIndex][$column->headerName]['width'] = isset($column->width) ? $column->width : NULL;
      $columns[1][$colIndex][$colIndex][$column->headerName]['minWidth'] = isset($column->minWidth) ? $column->minWidth : NULL;

      // Set the field if available.
      if (isset($column->field)) {
        $columnFields .= $column->field . ",";
      }

      // If children, then dive down for headers, otherwise establish column.
      if (isset($column->children)) {
        $columns[1][$colIndex][$colIndex][$column->headerName]['colspan'] = count($column->children);
        $count2 = 0;
        foreach ($column->children as $child) {
          $rowIndex = $rowIndex > 2 ? $rowIndex : 2;
          $count2++;

          $columns[2][$colIndex][$count2][$column->headerName . ' - ' . $child->headerName] = [];
          $columns[2][$colIndex][$count2][$column->headerName . ' - ' . $child->headerName]['headerName'] = $child->headerName;
          $columns[2][$colIndex][$count2][$column->headerName . ' - ' . $child->headerName]['field'] = isset($child->field) ? $child->field : NULL;
          $columns[2][$colIndex][$count2][$column->headerName . ' - ' . $child->headerName]['width'] = isset($child->width) ? $child->width : NULL;
          $columns[2][$colIndex][$count2][$column->headerName . ' - ' . $child->headerName]['minWidth'] = isset($child->minWidth) ? $child->minWidth : NULL;

          // Set the field if available.
          if (isset($child->field)) {
            $columnFields .= $child->field . ",";
          }

          if (isset($child->children)) {
            $columns[2][$colIndex][$count2][$column->headerName . ' - ' . $column->headerName]['colspan'] = count($child->children);
            $count3 = 0;
            foreach ($child->children as $subchild) {
              $rowIndex = $rowIndex > 3 ? $rowIndex : 3;
              $count3++;

              $columns[3][$colIndex][$count3][$column->headerName . ' - ' . $child->headerName . ' - ' . $subchild->headerName] = [];
              $columns[3][$colIndex][$count3][$column->headerName . ' - ' . $child->headerName . ' - ' . $subchild->headerName]['headerName'] = $subchild->headerName;
              $columns[3][$colIndex][$count3][$column->headerName . ' - ' . $child->headerName . ' - ' . $subchild->headerName]['field'] = isset($subchild->field) ? $subchild->field : NULL;
              $columns[3][$colIndex][$count3][$column->headerName . ' - ' . $child->headerName . ' - ' . $subchild->headerName]['colspan'] = 1;
              $columns[3][$colIndex][$count3][$column->headerName . ' - ' . $child->headerName . ' - ' . $subchild->headerName]['width'] = isset($subchild->width) ? $subchild->width : NULL;
              $columns[3][$colIndex][$count3][$column->headerName . ' - ' . $child->headerName . ' - ' . $subchild->headerName]['minWidth'] = isset($subchild->minWidth) ? $subchild->minWidth : NULL;

              // Set the field if available.
              if (isset($subchild->field)) {
                $columnFields .= $subchild->field . ",";
              }
            }
          }
          else {
            // Just one for colspan.
            $columns[2][$colIndex][$count2][$column->headerName . ' - ' . $child->headerName]['colspan'] = 1;
          }
        }
      }
      else {
        // Just one for colspan.
        $columns[1][$colIndex][$colIndex][$column->headerName]['colspan'] = 1;
      }
    }

    // Put columnFields to headers, trim comma, and put to array.
    $headers = $columnFields;
    $headers = substr($headers, 0, strlen($headers) - 1);
    $headers = str_getcsv($headers);

    // Set return
    $getHeaders['rowIndex'] = $rowIndex;
    $getHeaders['colIndex'] = $colIndex;
    $getHeaders['headers'] = $headers;
    $getHeaders['columns'] = $columns;

    return $getHeaders;
  }

  public function getRowSettings($aggridRowSettings, $headers, $rowData, $rowPrefix) {
    // Set global complimentary switch
    // Set the rest of the row settings
    $rowSettings[][] = '';
    if (is_array($rowData)) {
      for ($i = 0; $i < count($rowData); $i++) {
        foreach ($headers as $field) {
          $rowSettings[$i][$field] = [];
          if (isset($aggridRowSettings[$rowPrefix . $i][$field])) {
            $rowSettings[$i][$field] = $rowSettings[$i][$field] + $aggridRowSettings[$rowPrefix . $i][$field];
          }
          if (isset($aggridRowSettings[$rowPrefix . $i]['rowDefault'])) {
            $rowSettings[$i][$field] = $rowSettings[$i][$field] + $aggridRowSettings[$rowPrefix . $i]['rowDefault'];
          }
          if (isset($aggridRowSettings['default'][$field])) {
            $rowSettings[$i][$field] = $rowSettings[$i][$field] + $aggridRowSettings['default'][$field];
          }
          if (isset($aggridRowSettings['default']['rowDefault'])) {
            $rowSettings[$i][$field] = $rowSettings[$i][$field] + $aggridRowSettings['default']['rowDefault'];
          }
        }
      }
    }

    return $rowSettings;
  }

}
