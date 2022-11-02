# CiviCRM: EventBrite

## Background

We've created this integration for CiviCRM user organizations who require an
automated process that will synchronize data from EventBrite account into CiviCRM,
with an aim to facilitate comprehensive reporting on contacts in CiviCRM while
leveraging certain desirable event registration features of EventBrite.

## Outcomes

When the CiviCRM extension is properly installed and configured, it will
automatically interface with EventBrite to pull data from relevant EventBrite
entities (such as attendees and orders) into relevant CiviCRM entities (such as
contacts, participants, and contributions).

This includes the following types of data:

* Participant first and last name, email address, billing address, phone
* Date of registration
* Participant role / ticket type
* Optional paid items
* Paid amounts, noting any EventBrite fees
* Custom data, such as "Twitter handle", etc.

## Technical implementation

### Extension configuration settings

The extension provides settings forms to configure the following options:

* **EventBrite API key** (for authentication).
* **Events:** Optionally identify a CiviCRM _Event_ for each EventBrite _Event_ in
  your account; only data from events so configured will be synchronized.
* **Participant Roles:** For any configured event, identify the appropriate
  Participant Role for each EventBrite Ticket Type.
* **Price Set Fields:** For any configured event, identify the appropriate Price Set
  fields and options to record for any selected EventBrite Additional Items.
* **Custom fields for _Contact_ or _Participant_:** For any configured _Event_,
  identify the appropriate CiviCRM entity (_Contact_ or _Participant_) and field in
  which to record answers to any configured EventBrite Questions (e.g., such as
  "Twitter handle", or others added with EventBrite's "Add Another Question" button).

### Required EventBrite setup

The organization's EventBrite account needs to be configured in certain ways to
support proper data synchronization:

* For any configured Event, under Manage > Order Options > Order Form > Attendee
  Information:
  * The setting "Collection Type" must be set to "Each Attendee".
  * The checkboxes under "Collect information by ticket type" must be checked for each
    Ticket Type.
* Under Account Settings > App Management, an API key must be created.


### Major technical components

* EventBrite API authentication:
  * Authentication is configured by generating an EventBrite API key in EventBrite,
    and then entering that API key in the configuration settings for this extension in
    CiviCRM.
* Extension will listen for EventBrite API updates via webhooks:
  * Once Authentication has been established, the extension will configure webhooks
    (for supported events, pointing to the webhook listener) in EventBrite, using the
    EventBrite API. No manual configuration of webhooks is required.
  * When alerted by a webhook event, the extension will:
    * Poll the EventBrite API for the latest details on the relevant entity.
    * Update CiviCRM data using the CiviCRM API.
* Logging:
  * The extension will maintain its own logs for certain items:
    * Any received webhook notification will be logged, including the full contents of
      the message and the timestamp.
    * Any errors from the EventBrite or CiviCRM APIs will be logged, with a code
      execution backtrace, full API request, full API response, and timestamp.


### Major data entities

CiviCRM and EventBrite use different terminology -- and slightly different data
structures -- for the relevant data entities in this synchronization. These entities
can be summarized as follows:


* Contact:
  * A _Contact_ is a person who is capable of attending an event.
  * System terminology:
    * CiviCRM: _Contact_
      * In this synchronization, the _Contact_ is always of type "Individual".
    * EventBrite: _User_ / _Username_
      * EventBrite requires each user acquiring tickets to have a unique email address,
        which serves as the Username.
* Event: 
  * An _Event_ is a gathering at a specific time and place (which may be a physical
    location or online portal), which _Contacts_ may attend.
  * System terminology:
    * CiviCRM: _Event_
    * EventBrite: _Event_
  * Project scope: Events are not to be synced between systems. Staff will manually
    create each event, with all its configurations, in both systems.
  * Supported EventBrite Webhook Events:
    * (none)
* Participant:
  * A _Participant_ is a person who has indicated that they will attend an event. 
  * System terminology:
    * CiviCRM: _Participant_
    * EventBrite: _Attendee (equivalent to one ticket in an Order)_
  * Project scope: EventBrite _Attendees_ will be synced to CiviCRM
    _Participants_.
  * Supported EventBrite Webhook Events:
    * Attendee.Updated: Triggered by any change to the _Attendee_ (creation, deletion,
      alteration, etc.)
* Order:
  * An _Order_ is a single action that creates one or more _Participants_ for an
    _Event_ and includes any payment related to that action.
  * An order may be refunded in full or in part; partial refunds may either leave
    _Attendee_ status unchanged or cancel it.
  * System terminology:
    * CiviCRM: _Contribution, Participant Payment_
    * EventBrite: _Order_
  * Project scope: 
    * EventBrite Orders will be synced to CiviCRM _Contributions _and_ Participant
      Payments_. Only one _Contribution _and one_ Participant Payment_ are created per
      _Order_.
  * Supported EventBrite Webhook Events:
    * Order.updated: Triggered by any change to the _Order_ (creation, deletion,
      alteration, etc.)
      * The amount for the corresponding _Contribution_ and _Participant Payment_ are
        updated to match the paid amount on the order. E.g., after a refund, the
        _Contribution_ amount is synced to reflect the post-refund _Order_
        total.
      * _Attendee_ statuses are synced to corresponding _Participant_ statuses. E.g.,
        after a ticket is canceled, the _Participant_ status is changed to "Canceled".


### Supported data points

Initial development will support syncing the following data fields from EventBrite
to CiviCRM. It's notable that EventBrite includes many more standard fields, which
could be added in the future with minimal development effort.

|Description|EventBrite API entity.field|… synced to CiviCRM API entity.field|
| --- | --- | --- |
|First name|Attendee.first_name|Contact.first_name |
|Last name|Attendee.last_name|Contact.last_name |
|Email|Attendee.email|Contact.email|
|Billing address|Attendee.profile.bill|Address.*|
|Work phone|Attendee.profile.work_phone|Contact.phone.[location_type=work]|
|Date of Registration|Attendee.created|Participant.participant_register_date|
|Fee level(s)|Attendee.ticket_class_id|Participant.role_id, as configured|
|Payment amount: gross|Order.costs.gross.major_value|Contribution.total_amount|
|Payment amount: fees|Order.costs.eventbrite_fee.major_value + Order.costs.payment_fee.major_value|Contribution.fee_amount|
|Payment date|Order.created|Contribution.receive_date|
|Custom data|Attendee.answers.*|Contact.[custom_field] or Participant.[custom_field], as configured.|


### Sync mechanisms and policies

* **Durable linking:** In several instances, the extension will store a long-term
link between a CiviCRM entity and an EventBrite entity.
  * This will most likely be done in a custom database table managed by the extension
    (the alternative is to use custom fields, which at present seems to be more
    cumbersome to develop and to add little to the user experience.)
  * By "durable", we mean not that it's immutable, but that it's written to disk and
    will be preserved until specifically altered or removed; there are cases in which
    the extension will intentionally remove this link (e.g., when an _Attendee_ ID is
    determined to represent a different _Contact_ than it did previously, as in "Linking
    _Attendees_ to _Participants_", below).
* **Linking _Attendees_ to _Participants_:** _Attendees_ are linked to _Contacts_
  and to _Participants_ through the configured CiviCRM "unattended" dedupe rule.
  * With any _Attendee.updated_ webhook:
      * The latest _Attendee_ data is matched to existing _Contacts_ by way of the
        Contact.duplicatecheck API; 
        * If the matched _Contacts_ include the _Participant_ linked to the _Attendee_ ID,
          that _Contact_ is updated with the latest _Attendee_ data.  
        * Otherwise:
          * The existing _Contact_ is retained.
          * The existing _Contact_ is unlinked from the _Attendee_ ID.
          * The existing _Participant_ record is changed:
            * marked with a participant status of "Removed in EventBrite" (which has 
              a class of "Negative");
            * Unlinked from the _Attendee_ ID.
          * A new _Contact_ is created, and linked to the _Attendee_ ID.
          * A new _Participant_ is created for this new _Contact_ with a status corresponding
            to the current _Attendee_ status, and linked to the _Attendee_ ID.
* _Orders_: An _Order_ is recorded in CiviCRM as follows:
  * Every _Order_ contains at least one _Attendee_, which we'll call the "primary
    _Attendee_". If the _Order_ represents multiple _Attendees_, we'll call these
    additional _Attendees_ "secondary _Attendees_".
  * Each _Attendee_ is recorded as a _Participant_, and the _Participant_ is durably
    linked to the _Attendee_ ID.
  * The _Participant_ representing the primary _Attendee_ in the _Order_ is also
    recorded as the primary _Participant_ record for any _Participant_ records
    representing the secondary Attendees; this is the equivalent data structure as is
    natively used for recording multiple-participant event registrations in CiviCRM
    (i.e., as recorded in the SQL column civicrm_participant.registered_by_id).
  * Each _Participant_ is durably linked to the _Order._
  * A _Contribution_ is created, durably linked to the _Order_; this _Contribution_ is
    recorded as a _Participant Payment_ for the primary _Participant_.
* **Proposed (not-yet functional) workaround for EventBrite API bug in Additional
Items:** If the organization's EventBrite _Event_ is configured to allow purchase of
"Additional Items" (e.g., "Guest Pass", or "Event preconference"), we expect that
can only be stored in CiviCRM as Line Items (i.e., Price Set fields). However, the
EventBrite support team has confirmed that there's a bug in their API at present,
which prevents retrieving this data via the API. This extension could provide a
workaround for syncing Additional Items, which would require periodic manual
intervention by organization staff:
  * Periodic manual intervention by organization staff, at any interval, e.g.
    weekly:
    * Log into EventBrite and export the "Merchandise Summary" report to a CSV file.
    * Upload this CSV file to CiviCRM, for processing and import by this extension.
  * The extension would provide an extension to CiviCRM's API which will process data
    in CSV files of this format, associating any purchased Additional Items with the
    appropriate _Participants_.
  * Also note this limitation with regard to Additional Items ownership: EventBrite
    associates these Additional Items with the _Order_ entity, not with any particular
    _Attendee_; therefore, on multi-ticket _Orders_, we would have no way to associate
    them with a particular CiviCRM _Participant_ other than "the first _Attendee_ in the
    _Order_". For example, if an _Order_ includes five tickets and three additional
    "Guest Pass" items, we can only record in CiviCRM that the primary _Participant_
    purchased three "Guest Pass" items; we would have no way of knowing in CiviCRM which
    of the five participants should have those preconference items.


## Caveats

* **Staff awareness of double booking behaviors:** EventBrite allows any _User_ to
  acquire multiple tickets to a single _Event_. On the other hand, CiviCRM generally
  works to prevent this kind of "double booking" -- in most cases. That is, it
  prevents this at the user-interface level; for example, when using the "Add Event
  Registration" button on John Doe's "Events" tab, staff users will receive an error
  message if they try to create a second event registration for "John Doe" at "Event
  X".  However, CiviCRM does not enforce this restriction for _Participant_ records
  created through the API. Since this extension will be managing _Participant_ records
  through the API, it will not face this limitation. This difference may cause some
  confusion to staff who try to mimic this behavior in the user interface.