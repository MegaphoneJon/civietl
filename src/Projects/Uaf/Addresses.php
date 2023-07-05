<?php
namespace Civietl\Projects\Uaf;

use Civietl\Transforms as T;

class Addresses {

  public function transforms(array $rows) : array {
    // Introduce a second set of rows, those manually fixed by Gail.
    $manuallyFixedCsv = new \Civietl\Reader\CsvReader([
      'file_path' => $GLOBALS['workroot'] . '/raw data/Bad_Addresses FOR IMPORT gail corrected errors.csv',
      'data_primary_key' => 'LGL Address ID',
    ]);
    $manuallyFixedRows = $manuallyFixedCsv->getRows();
    // Remove rows in the original addresses that are in Gail's group.
    $rows = T\Joins::removeRowsInBoth($rows, $manuallyFixedRows, 'LGL Constituent ID', 'LGL Constituent ID');
    // Get Contact ID.
    $rows = T\CiviCRM::lookup($rows, 'Contact', ['LGL Constituent ID' => 'external_identifier'], ['id']);
    $rows = T\Columns::renameColumns($rows, [
      'id' => 'contact_id',
      'Address Type' => 'location_type_id:label',
      'City' => 'city',
      'Postal Code/ZIP' => 'postal_code',
      'Is Preferred?' => 'is_primary',
    ]);
    // Split Address into two fields when there's a carriage return.
    $rows = T\Transform::splitFieldToFields($rows, 'Street', "\n");
    $rows = T\Columns::renameColumns($rows, [
      'Street_0' => 'street_address',
      'Street_1' => 'supplemental_address_1',
      'Street_2' => 'supplemental_address_2',
      'Street_3' => 'supplemental_address_3',
    ]);
    $rows = T\Columns::deleteColumns($rows, ['Constituent Name', 'LGL Address ID', 'County', 'Seasonal from', 'Seasonal to', 'Is Valid?', 'Street']);

    $rows = T\ValueTransforms::valueMapper($rows, 'is_primary', ['false' => 0, 'true' => 1]);
    // CLEANUP
    // Trim every field.
    $rows = T\Text::trim($rows, array_keys(reset($rows)));
    // Move obvious states to countries.
    $rows = T\Cleanup::moveStatesToCountries($rows, 'State', 'Country');
    // Clean up and map countries. First the ones that are special to this data, then all the rest.
    $rows = T\ValueTransforms::valueMapper($rows, 'Country', \Civietl\Maps::COUNTRY_MAP + self::UAF_COUNTRY_MAP, NULL, FALSE);
    $rows = T\ValueTransforms::valueMapper($rows, 'State', \Civietl\Maps::STATE_MAP + self::UAF_STATE_MAP, NULL, FALSE);

    // COUNTRY LOOKUPS
    // $completedCoutryRows is first records with no country, then adding lookups by ISO code, then lookups by name.
    $completedRows = [];
    $completedRows += array_filter($rows, function($row) {
      return !$row['Country'];
    });
    $completedRows = T\Columns::newColumnWithConstant($completedRows, 'id', '');
    $rows = array_diff_key($rows, $completedRows);
    $rows = T\CiviCRM::lookup($rows, 'Country', ['Country' => 'iso_code'], ['id'], FALSE);
    $completedRows += array_filter($rows, function($row) {
      return $row['id'];
    });
    $rows = array_diff_key($rows, $completedRows);
    $rows = T\Columns::deleteColumns($rows, ['id']);
    $rows = T\CiviCRM::lookup($rows, 'Country', ['Country' => 'name'], ['id'], FALSE);
    $completedRows += array_filter($rows, function($row) {
      return $row['id'];
    });
    $rows = array_diff_key($rows, $completedRows);
    // rejoin the rows.
    $rows += $completedRows;
    $rows = T\Columns::renameColumns($rows, ['id' => 'country_id']);

    // STATE LOOKUPS
    // Let's add the default country column for doing lookups both with and without it.
    $defaultCountryId = 1228;
    $rows = T\ValueTransforms::valueMapper($rows, 'country_id', ['' => $defaultCountryId], 'country_id_with_default');
    $completedRows = [];
    $completedRows += array_filter($rows, function($row) {
      return !$row['State'];
    });
    $completedRows = T\Columns::newColumnWithConstant($completedRows, 'id', '');
    $rows = array_diff_key($rows, $completedRows);

    // We do a bunch of different Civi lookups (state + country, state + default country, state alone - each for both abbreviation and name)
    $stateLookups = [
      ['State' => 'abbreviation', 'country_id' => 'country_id'],
      ['State' => 'name', 'country_id' => 'country_id'],
      ['State' => 'abbreviation', 'country_id_with_default' => 'country_id'],
      ['State' => 'name', 'country_id_with_default' => 'country_id'],
      ['State' => 'abbreviation'],
      ['State' => 'name'],
    ];
    foreach ($stateLookups as $stateLookup) {
      $rows = T\CiviCRM::lookup($rows, 'StateProvince', $stateLookup, ['id'], FALSE);
      $completedRows += array_filter($rows, function($row) {
        return $row['id'];
      });
      $rows = array_diff_key($rows, $completedRows);
      // If we completed all our rows, just continue.
      if (empty($rows)) {
        break;
      }
      $rows = T\Columns::deleteColumns($rows, ['id']);
    }
    $rows = T\Columns::newColumnWithConstant($rows, 'id', '');

    // rejoin the rows.
    $rows += $completedRows;
    $rows = T\Columns::renameColumns($rows, ['id' => 'state_province_id']);
    // $rows = T\Columns::deleteColumns($rows, ['id']);
    $rows = T\Columns::deleteColumns($rows, ['country_id_with_default']);

    // Validate addresses, log bad addresses to errors.
    $rows = T\Cleanup::validateAddresses($rows, [
      'state_province_id' => 'State',
      'country_id' => 'Country',
    ]);
    // We saved this for the error list, now let's delete it.
    $rows = T\Columns::deleteColumns($rows, ['LGL Constituent ID']);

    return $rows;
  }

  const UAF_COUNTRY_MAP = [
    '10023-5565' => '',
    'S. America' => '',
    'B- 1049 Brussels' => 'Belgium',
    'Harare, Zimbabwe' => 'Zimbabwe',
    '10032' => 'United States',
    '94559' => 'United States',
    '91436-4487' => 'United States',
    '70115' => 'United States',
    'Czechia' => 'Czech Republic',
    'khyber pakhtunkhwa pakistan' => 'Pakistan',
    'Delhi' => 'India',
    'Kinsahsa' => 'CD',
    'Basque Country' => '',
    'Cypress' => '',
    'Kurdistan' => '',
    'Kashmir' => 'Pakistan',
  ];

  // We need a better system for these countries-as-states and vice versa..
  const UAF_STATE_MAP = [
    'England' => '',
    'Scotland' => '',
    'Wales' => '',
    'Россия' => '',
    'россия' => '',
    'Bayreuth' => '',
  ];

}
