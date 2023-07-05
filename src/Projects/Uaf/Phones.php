<?php
namespace Civietl\Projects\Uaf;

use Civietl\Transforms as T;

class Phones {

  /**
   * Do all the transforms associated with this step.
   */
  public function transforms(array $rows) : array {
    // Delete unused columns. Not necessary but easier (and marginally faster) to work with.
    $rows = T\Columns::deleteColumns($rows, ['LGL Phone ID', 'Constituent Name', 'Is Valid?']);
    // Remove blanks.
    $rows = T\RowFilters::filterBlanks($rows, 'Number');
    // Rename some columns that are one-to-one with Civi.
    $rows = T\Columns::renameColumns($rows, [
      'LGL Constituent ID' => 'external_identifier',
      'Number' => 'phone',
      'Phone Type' => 'location_type_id:label',
      'Is Preferred?' => 'is_primary',
    ]);
    $rows = T\ValueTransforms::valueMapper($rows, 'is_primary', ['false' => 0, 'true' => 1]);
    $rows = T\Columns::newColumnWithConstant($rows, 'phone_type_id:label', 'Phone');
    $rows = T\ValueTransforms::valueMapper($rows, 'location_type_id:label', ['Fax' => 'Fax', 'Mobile' => 'Mobile'], 'phone_type_id:label');
    $rows = T\ValueTransforms::valueMapper($rows, 'location_type_id:label', ['Fax' => 'Work', 'Mobile' => 'Main']);
    // Create any missing location/phone types.
    $locationTypes = T\RowFilters::getUniqueValues($rows, 'location_type_id:label');
    T\CiviCRM::createLocationTypes($locationTypes);
    $phoneTypes = T\RowFilters::getUniqueValues($rows, 'phone_type_id:label');
    T\CiviCRM::createOptionValues('phone_type', $phoneTypes);
    $rows = T\CiviCRM::lookup($rows, 'Contact', ['external_identifier' => 'external_identifier'], ['id']);
    $rows = T\Columns::renameColumns($rows, ['id' => 'contact_id']);
    return $rows;
  }

}
