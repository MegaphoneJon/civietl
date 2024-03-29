<?php
namespace Civietl\Projects\Uaf;

use Civietl\Transforms as T;

class FixedAddresses {

  public function transforms(array $rows) : array {
    $rows = T\Columns::deleteColumns($rows, ['Bad_Addresses', 'country_id', 'state_province_id', 'errors ']);
    // Clean up and map countries. First the ones that are special to this data, then all the rest.
    $rows = T\ValueTransforms::valueMapper($rows, 'Country', \Civietl\Maps::COUNTRY_MAP + Addresses::UAF_COUNTRY_MAP, NULL, FALSE);
    $rows = T\ValueTransforms::valueMapper($rows, 'State', \Civietl\Maps::STATE_MAP + Addresses::UAF_STATE_MAP, NULL, FALSE);

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
  ];

  // We need a better system for these countries-as-states and vice versa..
  const UAF_STATE_MAP = [
    'England' => '',
    'Scotland' => '',
    'Wales' => '',
  ];

}
