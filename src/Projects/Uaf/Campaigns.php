<?php
namespace Civietl\Projects\Uaf;

use Civietl\Transforms as T;

class Campaigns {

  /**
   * Do all the transforms associated with this step.
   */
  public function transforms(array $rows) : array {
    // Remove columns that will not be imported.
    $rows = T\Columns::deleteAllColumnsExcept($rows, [
      'LGL Campaign ID',
      'Name',
      'Description',
      'Goal',
      'Start Date',
      'End Date',
      'Is Active?',
    ]);
    // Rename the columns that will be imported to match CiviCRM fields.
    $rows = T\Columns::renameColumns($rows, [
      'LGL Campaign ID' => 'external_identifier',
      'Name' => 'title',
      'Description' => 'description',
      'Goal' => 'goal_revenue',
      'Start Date' => 'start_date',
      'End Date' => 'end_date',
      'Is Active?' => 'is_active',
    ]);
    // Remap true to 1 and false to 0 for is_active.
    $rows = T\ValueTransforms::valueMapper($rows, 'is_active', ['false' => 0, 'true' => 1]);
    return $rows;
  }

}
