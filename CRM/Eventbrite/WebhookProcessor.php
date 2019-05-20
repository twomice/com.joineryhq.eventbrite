<?php

/**
 * Processor for Eventbrite webhook messages.
 */
class CRM_Eventbrite_WebhookProcessor {

  protected $data = array();
  private $entityType;
  protected $entityId;

  /**
   * Initialize the processor.
   *
   * @param String $data JSON webhook payload, as received from Eventbrite
   *   webhook.
   */
  public function __construct($data) {
    $this->data = $data;
    $this->setEntityIdentifiers();
  }

  private function setEntityIdentifiers() {
    if (!$apiUrl = CRM_utils_array::value('api_url', $this->data)) {
      throw new CRM_Exception('Bad data. Missing parameter "api_url" in message');
    }
    $path = rtrim(parse_url($apiUrl, PHP_URL_PATH), '/');
    $pathElements = array_reverse(explode('/', $path));
    $this->entityId = $pathElements[0];
    $this->entityType = $pathElements[1];
  }

  public function process() {}

  public function getEntityIdentifier() {
    return "{$this->entityType}_{$this->entityId}";
  }

}
