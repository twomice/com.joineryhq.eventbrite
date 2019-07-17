<?php


/**
 * Utilities for Evenbrite extension.
 *
 */
class CRM_Eventbrite_Utils {
  static public function getWebhookListenerUrl() {
    $url = CRM_Utils_System::url('civicrm/eventbrite/webhook',
      NULL,
      TRUE,
      NULL,
      FALSE,
      FALSE,
      FALSE);
    if (strpos($url, 'wp-admin/admin.php') !== FALSE) {
      Civi::log()->info("Eventbrite: fix URL for WordPress");
      $url = str_replace('/wp-admin/admin.php', '', $url);
    }
    Civi::log()->info("Eventbrite webhook url: " . $url);
    return $url;
  }

}
