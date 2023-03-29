<?php
namespace Civietl\Projects\Uaf;

use Civietl\Transforms as T;

class CommunicationPreferences {

  /**
   * Do all the transforms associated with this step.
   */
  public function transforms(array $rows) : array {
    // Remove columns that will not be imported.
    $rows = T\Columns::deleteAllColumnsExcept($rows, [
      'LGL Constituent ID',
      'Communication Tags',
      'Acknowledgment Preference',
    ]);
    // Rename some columns that are one-to-one with Civi.
    $rows = T\Columns::renameColumns($rows, [
      'LGL Constituent ID' => 'external_identifier',
      'Acknowledgment Preference' => 'preferred_communication_method:label',
    ]);
    // Remap Acknowledgment Preference.
    $rows = T\ValueTransforms::valueMapper($rows, 'preferred_communication_method:label', ['Prefers email' => 'Email']);
    // Create any missing preferred communication methods in the option values table.
    $commMethods = T\RowFilters::getUniqueValues($rows, 'preferred_communication_method:label');
    T\CiviCRM::createOptionValues('preferred_communication_method', $commMethods);
    // Split 'Communication Tags' into relevant Civi fields.
    $rows = T\Transform::splitFieldToFields($rows, 'Communication Tags', ';');

    return $rows;
  }

}