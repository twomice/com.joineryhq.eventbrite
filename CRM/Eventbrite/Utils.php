<?php


/**
 * Utilities for Evenbrite extension.
 *
 */
class CRM_Eventbrite_Utils {

  public static function getWebhookListenerUrl() {
    return CRM_Utils_System::url('civicrm/eventbrite/webhook', NULL, TRUE, NULL, NULL, TRUE, NULL);
  }

}
