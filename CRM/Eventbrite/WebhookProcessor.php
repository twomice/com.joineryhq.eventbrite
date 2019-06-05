<?php

use CRM_Participantletter_ExtensionUtil as E;

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
   * @param array $data Webhook payload, as received from Eventbrite webhook
   *  OR Eventbrite entity as received from Eventbrite API.
   */
  public function __construct($data) {
    $this->data = $data;
    $this->setEntityIdentifiers();
    $this->loadData();
  }

  private function setEntityIdentifiers() {
    if (
      !($apiUrl = CRM_utils_array::value('api_url', $this->data))
      && !($apiUrl = CRM_utils_array::value('resource_uri', $this->data))
    ) {
      throw new CRM_Exception('Bad data. Missing parameter "api_url" in message');
    }
    $path = rtrim(parse_url($apiUrl, PHP_URL_PATH), '/');
    $pathElements = array_reverse(explode('/', $path));
    $this->entityId = $pathElements[0];
    $this->entityType = $pathElements[1];
  }

  protected function loadData() {

  }

  public function process() {

  }

  public function getEntityIdentifier() {
    return "{$this->entityType}_{$this->entityId}";
  }

  public function get($property) {
    return $this->$property;
  }

}
