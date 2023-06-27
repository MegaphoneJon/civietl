<?php
namespace Civietl\Writer;

use Civietl\Logging;

class CivicrmApi3 implements WriterInterface {
  private string $primaryEntity;

  /**
   * @var bool
   * This is so we know whether to print the row headers.
   */
  private bool $errorFound = FALSE;
  private Logging $logger;

  public function __construct($options) {
    $this->primaryEntity = $options['civi_primary_entity'];
    $this->matchFields = $options['match_fields'] ?? ['id'];
    $this->logger = new Logging($this->primaryEntity . '-api3writer');
  }

  public function writeOne($row) : void {
    try {
      civicrm_api3($this->primaryEntity, 'create', $row);
    }
    catch (\CRM_Core_Exception $e) {
      if (!$this->errorFound) {
        $this->errorFound = TRUE;
        $this->logger->log("Error, " . implode(', ', array_keys($row)));
      }
      $this->logger->log("\"Error: " . $e->getMessage() . "\", " . json_encode($row));
    }
  }

  public function writeAll($rows) : void {
    foreach ($rows as $row) {
      $this->writeOne($row);
    }
  }

}
