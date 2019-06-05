<?php

/**
 * Wrapper around Eventbrite API.
 */
class CRM_Eventbrite_EvenbriteApi {

  private static $_singleton;
  private $token;
  const EVENTBRITE_APIv3_URL = 'https://www.eventbriteapi.com/v3';

  /**
   * Constructor.
   * @param string $token Eventbrite private OAuth token.
   */
  private function __construct($token = NULL) {
    if (isset($token)) {
      $this->token = $token;
    }
    else {
      $this->token = _eventbrite_civicrmapi('Setting', 'getvalue', [
        'name' => "eventbrite_api_token",
      ]);
    }
  }

  /**
   * Singleton pattern.
   *
   * @see __construct()
   *
   * @param string $token
   * @return object This
   */
  public static function singleton($token = NULL) {
    if (self::$_singleton === NULL) {
      self::$_singleton = new CRM_Eventbrite_EvenbriteApi($token);
    }
    return self::$_singleton;
  }

  /**
   * Perform an HTTP request against the live Eventbrite API.
   *
   * @param string $path Endpoint, sans self::EVENTBRITE_APIv3_URL
   * @param array $body Optional body for POST and PUT requests. Array, will be
   *    json-encoded before sending.
   * @param array $expand Array of 'expand' options for Eventbrite API.
   *    See: https://www.eventbrite.com/platform/api#/introduction/expansions
   * @param string $method HTTP verb: GET, POST, etc.
   * @return array
   */
  public function request($path, $body = array(), $expand = array(), $method = 'GET') {
    $options = array(
      'http' => array(
        'method' => $method,
        'header' => "content-type: application/json\r\n",
        'ignore_errors' => TRUE,
      ),
    );
    if (
      $method == 'POST'
      || $method == 'PUT'
    ) {
      $options['http']['content'] = json_encode($body);
    }

    $path = '/' . trim($path, '/') . '/';
    $url = self::EVENTBRITE_APIv3_URL . $path . '?token=' . $this->token;

    if (!empty($expand)) {
      $url .= '&expand=' . implode(',', $expand);
    }

    $context = stream_context_create($options);
    $result = @file_get_contents($url, FALSE, $context);
    // Log error if $result is null, probably network is unreachable.
    if ($result == NULL) {
      $bt = debug_backtrace();
      $error_location = "{$bt[1]['file']}::{$bt[1]['line']}";

      $messageLines = array(
        "Eventbrite API error: No response returned. Suspect network connection is down.",
        "Request URL: $url",
        "Method: $method",
        "Body: ". json_encode($body),
        "API called from: $error_location",
      );
      CRM_Eventbrite_BAO_EventbriteLog::create(array(
        'message' => implode("\n", $messageLines),
        'message_type_id' => CRM_Eventbrite_BAO_EventbriteLog::MESSAGE_TYPE_ID_EVENTBRITE_API_ERROR,
      ));
      throw new CRM_Core_Exception("Eventbrite API error: No response returned. Suspect network connection is down.");
    }
    $response = json_decode($result, TRUE);
    if ($response == NULL) {
      $response = array();
    }

    if ($error = CRM_Utils_Array::value('error', $response)) {
      $error_message = CRM_Utils_Array::value('status_code', $response);
      $error_message .= ': ' . $error;
      $error_message .= ': ' . CRM_Utils_Array::value('error_description', $response);
      $messageLines = array(
        "Eventbrite API error: {$error_message}",
        "Request URL: $url",
        "Method: $method",
        "Body: ". json_encode($body),
      );
      CRM_Eventbrite_BAO_EventbriteLog::create(array(
        'message' => implode("\n", $messageLines),
        'message_type_id' => CRM_Eventbrite_BAO_EventbriteLog::MESSAGE_TYPE_ID_EVENTBRITE_API_ERROR,
      ));
    }

    return $response;
  }

}
