<?php
namespace Civietl\Writer;

use Civietl\Logging;

class CivicrmApi4 implements WriterInterface {
  private string $primaryEntity;
  private bool $allowDuplicates;
  private array $matchFields;
  /**
   * @var bool This is so we know whether to print the row headers.
   */
  private bool $errorFound = FALSE;

  public function __construct($options) {
    $this->primaryEntity = $options['civi_primary_entity'];
    $this->allowDuplicates = $options['allow_duplicates'] ?? FALSE;
    $this->matchFields = $options['match_fields'] ?? ['id'];
  }

  public function writeOne($row) : void {
    $logEntry = ['Error' => FALSE];
    try {
      if ($this->allowDuplicates) {
        $result = civicrm_api4($this->primaryEntity, 'create', [
          'checkPermissions' => FALSE,
          'values' => $row,
        ]);
      }
      else {
        $result = civicrm_api4($this->primaryEntity, 'save', [
          'checkPermissions' => FALSE,
          'records' => [$row],
          'match' => $this->matchFields,
        ]);
      }
      if ($result['error_message'] ?? FALSE) {
        Logging::log("Failed to import: $row");
        Logging::log("Error: $result");
      }
    }
    catch (\CRM_Core_Exception $e) {
      if (!$this->errorFound) {
        $this->errorFound = TRUE;
        Logging::log("Failed to import $this->primaryEntity");
        Logging::log(implode(', ', array_keys($row)));
      }
      Logging::log(implode(', ', $row));
      Logging::log("Error: " . $e->getMessage());
    }
  }

  public function writeAll($rows) : void {
    foreach ($rows as $row) {
      $this->writeOne($row);
    }
  }

}
