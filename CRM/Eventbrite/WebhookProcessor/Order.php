<?php

/**
 * Class for processing Eventbrite 'Order' webhook events.
 *
 */
class CRM_Eventbrite_WebhookProcessor_Order extends CRM_Eventbrite_WebhookProcessor {

  private $order;
  private $eventId = NULL;

  protected function loadData() {
    if (CRM_Utils_Array::value('resource_uri', $this->data)) {
      $this->order = $this->data;
    }
    else {
      $eb = CRM_Eventbrite_EvenbriteApi::singleton();
      $this->order = $eb->request("/orders/{$this->entityId}/", NULL, array('attendees', 'attendee-answers'));
    }
  }

  public function process() {

    // Ensure we have a link for the event; otherwise any received data would be nonsensical.
    $this->eventId = _eventbrite_civicrmapi('EventbriteLink', 'getValue', array(
      'eb_entity_type' => 'Event',
      'civicrm_entity_type' => 'Event',
      'eb_entity_id' => CRM_Utils_Array::value('event_id', $this->order),
      'return' => 'civicrm_entity_id',
    ), "Processing Order {$this->entityId}, attempting to get linked event for order.");
    if (!$this->eventId) {
      CRM_Eventbrite_BAO_EventbriteLog::create(array(
        'message' => "Could not find EventbriteLink record 'Event' for order {$this->entityId} with 'event_id': " . CRM_Utils_Array::value('event_id', $this->order) . "; cannot process Order. In method " . __METHOD__ . ", file " . __FILE__ . ", line " . __LINE__,
        'message_type_id' => CRM_Eventbrite_BAO_EventbriteLog::MESSAGE_TYPE_ID_GENERAL,
      ));
      throw new CRM_Exception("Could not find EventbriteLink 'Event' record for order {$this->entityId} with 'event_id': " . CRM_Utils_Array::value('event_id', $this->order) . "; cannot process Order.");
      return;
    }

    /*
     * Now that we have the latest Order data, the basic idea is to do this:
     *
     * Define contribution params based on Order.costs
     * Determine which contribution is linked to this order, if any.
     * If linked contribution exists:
     *   use that id as contributionParams.id
     *   Remove linked Contribution for this Order.
     *
     * Use contribution.create api to update/create contribution with Order data:
     *  - cost data as in order
     *  - If order status is 'deleted' or 'cancelled'/'refunded', contribution status = cancelled.
     *
     * Create new link between Order and ContributionId.
     *
     * Determine a list of OrderAttendeeIds.
     * Determine existingPrimaryParticipantId for order via eventbritelink, if any (must be an existing participant record)
     * if existingPrimaryParticipantId
     *  - get all participants where id = existingPrimaryParticipantId or registered_by_id = primaryParticipantId
     *   For each of these participants:
     *   - if the participant is not linked to an attendee that's in OrderAttendeeIds, then
     *     - set status 'removed from eventbrite'
     *     - set registered_by_id = null
     *
     * Start with an empty list of OrderParticipantIds
     * For each attendee in OrderAttendeeIds:
     *  - if linked via EventbriteLink to an existing Participant, add pid to OrderParticipantIds
     *  - else:
     *    - process Attendee (this will create a linked participant)
     *    - get the linked pid for that attendee, and add pid to OrderParticipantIds
     * Determine lowest AttendeeId in order; this is PrimaryAttendeeId.
     * Determine linked pid for PrimaryAttendeeId; this is PrimaryParticipantId
     * For each pid in OrderParticipantIds, set registered_by_id = PrimaryParticipantId
     *
     * Delete any existing link for existingPrimaryParticipantId
     * Create new link for PrimaryParticipantId
     */

    $existingPrimaryParticipantId = $existingPrimaryParticipantLinkId = NULL;

    // Determine a list of OrderAttendeeIds.
    $orderAttendees = CRM_Utils_Array::rekey($this->order['attendees'], 'id');
    $orderAttendeeIds = array_keys($orderAttendees);

    // Determine existingPrimaryParticipantId for order via eventbritelink, if any (must be an existing participant record)
    $existingParticipantIds = array();
    $link = _eventbrite_civicrmapi('EventbriteLink', 'get', array(
      'eb_entity_type' => 'Order',
      'civicrm_entity_type' => 'PrimaryParticipant',
      'eb_entity_id' => $this->entityId,
      'sequential' => 1,
      'api.participant.get' => array(
        'id' => '$value.civicrm_entity_id',
      ),
    ), "Processing Order {$this->entityId}, attempting to get linked PrimaryParticipant for the order.");
    if ($link['count']) {
      $existingPrimaryParticipantLinkId = $link['id'];
      $existingPrimaryParticipantId = CRM_Utils_Array::value('id', $link['values'][0]['api.participant.get']);
      $existingParticipantIds[] = $existingPrimaryParticipantId;
    }

    // if existingPrimaryParticipantId
    if ($existingPrimaryParticipantId) {
      // get all participants where registered_by_id = primaryParticipantId
      $participant = _eventbrite_civicrmapi('participant', 'get', array(
        'registered_by_id' => $existingParticipantIds,
        'options' => array(
          'limit' => 0,
        ),
      ), "Processing Order {$this->entityId}, attempting to get all participants currently associated with this order.");
      $existingParticipantIds += array_keys($participant['values']);

      foreach ($existingParticipantIds as $existingParticipantId) {
        $isOrderAttendee = FALSE;
        $link = _eventbrite_civicrmapi('EventbriteLink', 'get', array(
          'civicrm_entity_type' => 'Participant',
          'civicrm_entity_id' => $existingParticipantId,
          'eb_entity_type' => 'Attendee',
          'sequential' => 1,
        ), "Processing Order {$this->entityId}, attempting to get Attendee linked to participant '$existingParticipantId'.");
        if ($link['count']) {
          $linkedAttendeeId = $link['values'][0]['eb_entity_id'];
          $isOrderAttendee = in_array($linkedAttendeeId, $orderAttendeeIds);
        }
        if (!$isOrderAttendee) {
          // if the participant is not linked to an attendee that's in OrderAttendeeIds, then
          // set status 'removed from eventbrite'
          CRM_Eventbrite_WebhookProcessor_Attendee::setParticipantStatusRemoved($existingParticipantId);
          // set registered_by_id = null
          _eventbrite_civicrmapi('participant', 'create', array(
            'id' => $existingParticipantId,
            'registered_by_id' => 'null',
          ), "Processing Order {$this->entityId}, attempting to unset registered_by_id for participant id '$existingParticipantId', previously associated with this order.");
        }
      }
    }

    // Start with an empty list of OrderParticipantIds
    $orderParticipantIds = array();
    foreach ($orderAttendees as $orderAttendeeId => $orderAttendee) {
      // For each attendee in OrderAttendeeIds:
      // if linked via EventbriteLink to an existing Participant, add pid to OrderParticipantIds
      $link = _eventbrite_civicrmapi('EventbriteLink', 'get', array(
        'civicrm_entity_type' => 'Participant',
        'eb_entity_type' => 'Attendee',
        'eb_entity_id' => $orderAttendeeId,
        'sequential' => 1,
      ), "Processing Order {$this->entityId}, attempting to get participant linked for order Attendee '$orderAttendeeId'.");
      if ($link['count']) {
        $orderParticipantIds[] = $link['values'][0]['civicrm_entity_id'];
      }
      else {
        // process Attendee (this will create a linked participant)
        $attendeeProcessor = new CRM_Eventbrite_WebhookProcessor_Attendee($orderAttendee);
        $attendeeProcessor->process();
        // get the linked pid for that attendee, and add pid to OrderParticipantIds
        $orderParticipantIds[] = $attendeeProcessor->get('participantId');
      }
    }
    // Determine lowest AttendeeId in order; this is PrimaryAttendeeId.
    $primaryAttendeeId = min($orderAttendeeIds);
    // Determine linked pid for PrimaryAttendeeId; this is PrimaryParticipantId
    $primaryParticipantId = _eventbrite_civicrmapi('EventbriteLink', 'getValue', array(
      'return' => 'civicrm_entity_id',
      'civicrm_entity_type' => 'Participant',
      'eb_entity_type' => 'Attendee',
      'eb_entity_id' => $primaryAttendeeId,
    ), "Processing Order {$this->entityId}, attempting to determined linked participant ID for primary attendee with id '$primaryAttendeeId'.");
    foreach ($orderParticipantIds as $orderParticipantId) {
      // For each pid in OrderParticipantIds, set registered_by_id = PrimaryParticipantId
      _eventbrite_civicrmapi('participant', 'create', array(
        'id' => $orderParticipantId,
        'registered_by_id' => $primaryParticipantId,
      ), "Processing Order {$this->entityId}, attempting to associate participant '$orderParticipantId' with order primary participant '$primaryParticipantId'.");
    }

    // Delete any existing link for existingPrimaryParticipantId
    if ($existingPrimaryParticipantLinkId) {
      _eventbrite_civicrmapi('EventbriteLink', 'delete', array(
        'id' => $existingPrimaryParticipantLinkId,
      ), "Processing Order {$this->entityId}, attempting to delete PrimaryParticipant link for this order.");
    }
    // Create new link for PrimaryParticipantId
    _eventbrite_civicrmapi('EventbriteLink', 'create', array(
      'eb_entity_type' => 'Order',
      'civicrm_entity_type' => 'PrimaryParticipant',
      'eb_entity_id' => $this->entityId,
      'civicrm_entity_id' => $primaryParticipantId,
    ), "Processing Order {$this->entityId}, attempting to create a new PrimaryParticipant link for this order.");

    // Handle contributions, but only if event is configured as is_monetary.
    $event = _eventbrite_civicrmapi('Event', 'getSingle', array(
      'id' => $this->eventId,
    ), "Processing Order {$this->entityId}, attempting to get the CiviCRM Event for this order.");
    if (CRM_Utils_Array::value('is_monetary', $event)) {
      $financialTypeId = CRM_Utils_Array::value('financial_type_id', $event);

      $contactId = _eventbrite_civicrmapi('participant', 'getValue', array(
        'return' => 'contact_id',
        'id' => $primaryParticipantId,
      ), "Processing Order {$this->entityId}, attempting to get Contact ID for order primary participant '$primaryParticipantId'.");

      // Define contribution params based on Order.costs
      $contributionParams = array(
        'receive_date' => CRM_Utils_Date::processDate(CRM_Utils_Array::value('created', $this->order)),
        'total_amount' => $this->order['costs']['gross']['major_value'],
        'total_amount' => $this->order['costs']['gross']['major_value'],
        'fee_amount' => ($this->order['costs']['eventbrite_fee']['major_value'] + $this->order['costs']['payment_fee']['major_value']),
        'financial_type_id' => $financialTypeId,
        'contact_id' => $contactId,
      );

      // Determine which contribution is linked to this order, if any.
      $link = _eventbrite_civicrmapi('EventbriteLink', 'get', array(
        'eb_entity_type' => 'Order',
        'civicrm_entity_type' => 'Contribution',
        'eb_entity_id' => $this->entityId,
        'sequential' => 1,
      ), "Processing Order {$this->entityId}, attempting to get linked contribution for this order, if any.");
      $linkId = CRM_Utils_Array::value('id', $link);
      if ($linkId) {
        $contributionParams['id'] = CRM_Utils_Array::value('civicrm_entity_id', $link['values'][0]);
        _eventbrite_civicrmapi('EventbriteLink', 'delete', array(
          'id' => $linkId,
        ), "Processing Order {$this->entityId}, attempting to delete existing Contribution/Order link.");
      }
      $orderStatus = CRM_Utils_Array::value('status', $this->order);
      if (in_array($orderStatus, array('refunded', 'cancelled', 'deleted'))) {
        // If order status is 'deleted' or 'cancelled'/'refunded', contribution status = cancelled.
        $contributionParams['contribution_status_id'] = 'Cancelled';
      }

      // Use contribution.create api to update/create contribution with Order cost data.
      $contribution = _eventbrite_civicrmapi('contribution', 'create', $contributionParams, "Processing Order {$this->entityId}, attempting to create/update contribution record.");

      // Create new link between Order and ContributionId.
      _eventbrite_civicrmapi('EventbriteLink', 'create', array(
        'civicrm_entity_type' => 'Contribution',
        'civicrm_entity_id' => $contribution['id'],
        'eb_entity_type' => 'Order',
        'eb_entity_id' => $this->entityId,
      ), "Processing Order {$this->entityId}, attempting to create new Order/Contribution link.");
    }

  }

}
