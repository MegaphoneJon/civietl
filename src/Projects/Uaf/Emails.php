<?php
namespace Civietl\Projects\Uaf;

use Civietl\Transforms as T;

class Emails {

  /**
   * Do all the transforms associated with this step.
   */
  public function transforms(array $rows) : array {
    // Trim and lowercase emails.
    $rows = T\Text::lowercase($rows, ['Email']);
    $rows = T\Text::trim($rows, ['Email']);
    // Log and remove invalid email addresses.
    $rows = T\Cleanup::filterInvalidEmails($rows, 'Email');
    // Delete unused columns. Not necessary but easier (and marginally faster) to work with.
    $rows = T\Columns::deleteColumns($rows, ['LGL Email ID', 'Constituent Name', 'Is Valid?']);
    // Rename some columns that are one-to-one with Civi.
    $rows = T\Columns::renameColumns($rows, [
      'LGL Constituent ID' => 'external_identifier',
      'Email' => 'email',
      'Email Type' => 'location_type_id:label',
      'Is Preferred?' => 'is_primary',
    ]);
    $rows = T\ValueTransforms::valueMapper($rows, 'is_primary', ['false' => 0, 'true' => 1]);
    // Create any missing website types in the option values table.
    $locationTypes = T\RowFilters::getUniqueValues($rows, 'location_type_id:label');
    T\CiviCRM::createLocationTypes($locationTypes);
    $rows = T\CiviCRM::lookup($rows, 'Contact', ['external_identifier' => 'external_identifier'], ['id']);
    $rows = T\Columns::renameColumns($rows, ['id' => 'contact_id']);
    return $rows;
  }

}
