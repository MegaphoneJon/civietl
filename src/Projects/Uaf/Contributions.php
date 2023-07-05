<?php
namespace Civietl\Projects\Uaf;

use Civietl\Transforms as T;

class Contributions {

  /**
   * Do all the transforms associated with this step.
   */
  public function transforms(array $rows) : array {
    // Remove columns that will not be imported.
    $rows = T\Columns::deleteAllColumnsExcept($rows, [
      'LGL Constituent ID',
      'LGL Gift ID',
      'LGL Campaign ID',
      'Fund',
      'LGL Appeal ID',
      'Gift Category',
      'Gift Amount',
      'Deductible amount',
      'Gift date',
      'Deposit Date',
      'Payment type',
      'Check/Reference No.',
      'Anonymous gift?',
      'Vehicle Name',
    ]);
    // Rename the columns that will be imported to match CiviCRM fields.
    $rows = T\Columns::renameColumns($rows, [
      'LGL Constituent ID' => 'contact_external_identifier',
      'LGL Gift ID' => 'Legacy_Contribution_Data.LGL_Gift_ID',
      'LGL Campaign ID' => 'campaign_external_identifier',
      'Fund' => 'financial_type_id:label',
      'LGL Appeal ID' => 'appeal_external_identifier',
      'Gift Category' => 'Additional_Contribution_Data.Contribution_type:label',
      'Gift Amount' => 'total_amount',
      'Gift date' => 'receive_date',
      'Deposit Date' => 'Additional_Contribution_Data.Deposited_Date',
      'Payment type' => 'payment_instrument_id:label',
      'Check/Reference No.' => 'check_number',
      'Anonymous gift?' => 'Additional_Contribution_Data.Anonymous_gift',
      'Vehicle Name' => 'vehicle_name',
    ]);
    // Get random sampe of rows to test. (REMOVE FOR FINAL VERSION)
    // $rows = T\RowFilters::randomSample($rows, 50);

    // Cleanup
    // Remap blanks to 'Unknown' for payment instrument.
    $rows = T\ValueTransforms::valueMapper($rows, 'payment_instrument_id:label', ['' => 'Unknown']);
    // Create any missing payment methods in the OptionValues table.
    $paymentMethods = T\RowFilters::getUniqueValues($rows, 'payment_instrument_id:label');
    T\CiviCRM::createOptionValues('payment_instrument', $paymentMethods);
    // Add a column that gives these Contributions a 'Completed' status.
    $rows = T\Columns::newColumnWithConstant($rows, 'contribution_status_id:label', 'Completed');
    // Remap true to 1 and false to 0 for Anonymous gift.
    $rows = T\ValueTransforms::valueMapper($rows, 'Additional_Contribution_Data.Anonymous_gift', ['FALSE' => 0, 'TRUE' => '1']);
    // Remap 'Donation' to 'Direct Donation' for Contribution type.
    $rows = T\ValueTransforms::valueMapper($rows, 'Additional_Contribution_Data.Contribution_type:label', ['Donation' => 'Direct Donation']);
    // Remap null to '' for Contribution type.
    $rowsWithCategory = T\RowFilters::filterBlanks($rows, 'Additional_Contribution_Data.Contribution_type:label');
    $rowsWithNoCategory = array_diff_key($rows, $rowsWithCategory);
    if ($rowsWithNoCategory) {
      $rowsWithNoCategory = T\ValueTransforms::valueMapper($rows, 'Additional_Contribution_Data.Contribution_type:label', ['' => 'Uncategorized']);
    }
    // Merge the two types of rows back into one.
    $rows = $rowsWithCategory + $rowsWithNoCategory;

    // Remap (i.e. clean up) Vehcile Name.
    $rows = T\ValueTransforms::valueMapper($rows, 'vehicle_name', ['2022-10-24 16:00:56 UTC' => 'Shell Oil Company Foundation']);
    // Map empty 'Fund' to 'Unrestricted/ General Support'
    $rows = T\ValueTransforms::valueMapper($rows, 'financial_type_id:label', ['' => 'Unrestricted/ General Support']);
    // Get the value needed for 'non_deductible_amount' from 'Deductible amount'.
    array_walk($rows, function(&$row) {
      $row['non_deductible_amount'] = $row['total_amount'] - $row['Deductible amount'];
    });

    // Contacts
    // Look up and return the external_identifier of the Vehicle.
    $rows = T\CiviCRM::lookup($rows, 'Contact', ['vehicle_name' => 'organization_name'], ['external_identifier']);
    $rows = T\Columns::renameColumns($rows, ['external_identifier' => 'vehicle_external_identifier']);
    // If the Contribution has a Vehicle, use that, if not, use the LGL Constituent ID.
    $rows = T\Columns::coalesceColumns($rows, ['vehicle_external_identifier', 'contact_external_identifier'], 'constituent_or_vehicle');
    // Look up and return the id of the Contact this Contribution is connected to.
    $rows = T\CiviCRM::lookup($rows, 'Contact', ['constituent_or_vehicle' => 'external_identifier'], ['id']);
    $rows = T\Columns::renameColumns($rows, ['id' => 'contact_id']);

    // Handle DAF Contributions and *not* Third Party Giving Contirbutions.
    // Split rows into those with a Vehicle and those without.
    $rowsWithVehicle = T\RowFilters::filterBlanks($rows, 'vehicle_name');
    $rowsWithNoVehicle = array_diff_key($rows, $rowsWithVehicle);
    // Look up the Contact Subtype of rows with a Vehicle.
    if ($rowsWithVehicle) {
      $rowsWithVehicle = T\CiviCRM::lookup($rowsWithVehicle, 'Contact', ['contact_id' => 'id'], ['contact_sub_type']);
    }
    // Separate the rows in which the Contact is a Third Part Giving Vehicle. These Contributions will not be imported by the civietl.
    $rowsWithThirdParty = array_filter($rowsWithVehicle, function($row) {
      return isset($row['contact_sub_type']) && $row['contact_sub_type'][0] === 'Third_Party_Giving_Vehicle';
    });
    if ($rowsWithThirdParty) {
      $rowsWithThirdParty = T\Columns::deleteAllColumnsExcept($rowsWithThirdParty, ['Legacy_Contribution_Data.LGL_Gift_ID']);
      $rowsWithThirdParty = T\Columns::renameColumns($rowsWithThirdParty, ['Legacy_Contribution_Data.LGL_Gift_ID' => 'LGL_Gift_ID']);
      $thirdPartyWriter = new \Civietl\Writer\CsvWriter(['file_path' => $GLOBALS['workroot'] . '/data/gifts_not_imported.csv']);
      $thirdPartyWriter->writeAll($rowsWithThirdParty);
    }
    // The remaining rows with a Vehicle will be imported and treated as DAF Contributions.
    $rowsWithDAF = array_diff_key($rowsWithVehicle, $rowsWithThirdParty);
    // Assign a Donor Advisor for DAF Contributions if the Contact is an Individual or a Donor Advised Fund if the Contact is an Organization.
    if ($rowsWithDAF) {
      $rowsWithDAF = T\Columns::newColumnWithConstant($rowsWithDAF, 'Donor_Advised_Fund.Donor_Advisor', '');
      $rowsWithDAF = T\Columns::newColumnWithConstant($rowsWithDAF, 'Donor_Advised_Fund.Donor_Advised_Fund', '');
      $rowsWithDAF = T\CiviCRM::lookup($rowsWithDAF, 'Contact', ['contact_external_identifier' => 'external_identifier'], ['contact_type', 'id']);
      $rowsWithDAFIndividual = array_filter($rowsWithDAF, function($row) {
        return isset($row['contact_type']) && $row['contact_type'] === 'Individual';
      });
      $rowsWithDAFOrganization = array_filter($rowsWithDAF, function($row) {
        return isset($row['contact_type']) && $row['contact_type'] === 'Organization';
      });
      if ($rowsWithDAFIndividual) {
        $rowsWithDAFIndividual = T\Columns::renameColumns($rowsWithDAFIndividual, ['id' => 'Donor_Advised_Fund.Donor_Advisor']);
      }
      if ($rowsWithDAFOrganization) {
        $rowsWithDAFOrganization = T\Columns::renameColumns($rowsWithDAFOrganization, ['id' => 'Donor_Advised_Fund.Donor_Advised_Fund']);
      }
      $rowsWithDAF = $rowsWithDAFIndividual + $rowsWithDAFOrganization;
    }
    // Merge the two types of rows back into one.
    $rows = $rowsWithDAF + $rowsWithNoVehicle;
    $rows = T\Columns::deleteColumns($rows, ['contact_sub_type']);

    // Campaigns
    // Remap 0 to an empty string for the camapaign and/or appeal external ids.
    $rows = T\ValueTransforms::valueMapper($rows, 'campaign_external_identifier', ['0' => '', '497' => '']);
    $rows = T\ValueTransforms::valueMapper($rows, 'appeal_external_identifier', ['0' => '', '2772' => '', '2662' => '']);
    // If the Contribution has an Appeal id, use that, if not, use the Campaign id if not null.
    $rows = T\Columns::coalesceColumns($rows, ['appeal_external_identifier', 'campaign_external_identifier'], 'campaign_or_appeal');
    // Look up and return the id of the Campaign this Contribution is connected to.
    $rows = T\CiviCRM::lookup($rows, 'Campaign', ['campaign_or_appeal' => 'external_identifier'], ['id']);
    $rows = T\Columns::renameColumns($rows, ['id' => 'campaign_id']);

    return $rows;
  }

}
