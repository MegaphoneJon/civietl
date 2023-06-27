<?php
namespace Civietl\Projects\Uaf;

use Civietl\Transforms as T;

class Attachments {

  /**
   * Do all the transforms associated with this step.
   */
  public function transforms(array $rows) : array {
    // Filter to just gifts.
    $rows = array_filter($rows, function($row) {
      return $row['Related Item Type'] == 'Gift';
    });
    // Get the contribution ID.
    $rows = T\CiviCRM::lookup($rows, 'Contribution', ['Related Item ID' => 'Legacy_Contribution_Data.LGL_Gift_ID'], ['id']);
    // Rename some columns that are one-to-one with Civi.
    $rows = T\Columns::renameColumns($rows, [
      'id' => 'entity_id',
      'Document Name' => 'name',
    ]);
    // Deleted contributions don't get deleted attachments, filter them out.
    $rows = array_filter($rows, function($row) {
      return $row['entity_id'];
    });
    // Get the file extension.
    foreach ($rows as $key => $row) {
      $rows[$key]['extension'] = substr($row['name'], strrpos($row['name'], '.') + 1);
    }
    // Get MIME type from extension.
    $rows = T\ValueTransforms::valueMapper($rows, 'extension', $this->mimeTypesMap(), 'mime_type');
    // Set up the move-file option (which is the source of the ).
    foreach ($rows as $key => $row) {
      $rows[$key]['options'] = ['move-file' => $GLOBALS['workroot'] . '/raw data/attachments/' . $row['name']];
    }
    $rows = T\Columns::deleteAllColumnsExcept($rows, ['entity_id', 'name', 'mime_type', 'options']);
    // Remove duplicates - attachment API deletes files, so identical files throw an error.
    $rows = array_unique($rows, SORT_REGULAR);
    // Get the field name.
    // The first file for a given contribution should go on custom_16.  Subsequent files should go on custom_17 and custom_18.
    // $entityCount's keys are the unique entity IDs.  The value is how many times we've seen it in the following loop.
    $entityCount = array_fill_keys(array_unique(array_column($rows, 'entity_id')), 0);
    $customFieldId = 16;
    foreach ($rows as $key => $row) {
      $rows[$key]['field_name'] = 'custom_' . (string) ($customFieldId + $entityCount[$row['entity_id']]);
      $entityCount[$row['entity_id']]++;
    }
    return $rows;
  }

  private function mimeTypesMap() : array {
    return [
      'pdf' => 'application/pdf',
      'png' => 'image/png',
      'jpg' => 'image/jpeg',
      'jpeg' => 'image/jpeg',
      'doc' => 'application/msword',
      'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      'numbers' => 'application/vnd.apple.numbers',
      'html' => 'text/html',
      'csv' => 'text/csv',
    ];
  }

}
