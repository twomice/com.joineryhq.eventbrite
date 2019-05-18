<?php
use CRM_Eventbrite_ExtensionUtil as E;

/**
 * Eventbrite.Runqueue API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_eventbrite_Runqueue_spec(&$spec) {
  $spec['limit'] = array(
    'description' => E::ts('Maxiumum number of queued items to process per invocation.'),
    'api.default' => 0
  );
}

/**
 * Eventbrite.Runqueue API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_eventbrite_Runqueue($params) {
  $apiParams = array(
    'status_id' => CRM_Eventbrite_BAO_EventbriteQueue::STATUS_ID_NEW,
    'options' => array(
      'limit' => $params['limit'],
    )
  );
  $result = _eventbrite_civicrmapi('EventbriteQueue', 'get', $apiParams);

  $processed = $errors = array();
  foreach ($result['values'] as $value) {
    $valueProcessed = FALSE;
    try {
      $processor = new CRM_Eventbrite_WebhookProcessor(CRM_Utils_Array::value('message', $value));
      $processor->process();
      $processed[] = $value['id'];
      $valueProcessed = TRUE;
    } catch (CiviCRM_API3_Exception $e) {
      $errors[$value['id']] = $e->getMessage();
    }

    if ($valueProcessed) {
      $apiParams = array(
        'id' => $value['id'],
        'status_id' => CRM_Eventbrite_BAO_EventbriteQueue::STATUS_ID_PROCESSED,
      );
      _eventbrite_civicrmapi('EventbriteQueue', 'create', $apiParams);
    }
  }

  $returnValues = array(
    array(
      'Processed' => $processed,
      'Errors' => $errors,
    )
  );
  return civicrm_api3_create_success($returnValues, $params, 'Eventbrite', 'runqueue');
  
}
