<?php
namespace Civietl\Writer;

use Civietl\Logging;

class CivicrmApi4 implements WriterInterface {
  private string $primaryEntity;

  public function __construct($options) {
    $this->primaryEntity = $options['civi_primary_entity'];
  }

  public function writeOne($row) : array {
    $logEntry = ['Error' => FALSE];
    $result = civicrm_api4($this->primaryEntity, 'create', [
      'checkPermissions' => FALSE,
      'values' => $row,
    ]);
    if ($result['error_message'] ?? FALSE) {
      Logging::log("Failed to import: $row");
      Logging::log("Error: $result");
    }
    return $logEntry;
  }

  public function writeAll($rows) : array {
    foreach ($rows as $row) {
      $logEntries[] = $this->writeOne($row);
    }
    return $logEntries;
  }

}
