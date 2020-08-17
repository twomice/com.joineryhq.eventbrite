# com.joineryhq.eventbrite

Provides synchronization to CiviCRM for participants and payments registered through Eventbrite.

The extension is licensed under [GPL-3.0](LICENSE.txt).

## Installation (Web UI)

This extension has not yet been published for installation via the web UI.

## Installation (CLI, Zip)

Sysadmins and developers may download the `.zip` file for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
cd <extension-dir>
cv dl com.joineryhq.eventbrite@https://github.com/twomice/com.joineryhq.eventbrite/archive/master.zip
```

## Installation (CLI, Git)

Sysadmins and developers may clone the [Git](https://en.wikipedia.org/wiki/Git) repo for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
git clone https://github.com/twomice/com.joineryhq.eventbrite.git
cv en eventbrite
```

## Usage

Configuration options are available at Administer > CiviEvent > Eventbrite Integration

* The extension asks for your "Personal OAuth Token"; this may also be called the "Private API key" under the properties of the API Key in your [Evenbrite account settings](https://www.eventbrite.com/account-settings/apps).

To test, run the Scheduled Job called Call Eventbrite.Runqueue API manually. 

## Support
![screenshot](/images/joinery-logo.png)

Joinery provides services for CiviCRM including custom extension development, training, data migrations, and more. We aim to keep this extension in good working order, and will do our best to respond appropriately to issues reported on its [github issue queue](https://github.com/twomice/com.joineryhq.eventbrite/issues). In addition, if you require urgent or highly customized improvements to this extension, we may suggest conducting a fee-based project under our standard commercial terms.  In any case, the place to start is the [github issue queue](https://github.com/twomice/com.joineryhq.eventbrite/issues) -- let us hear what you need and we'll be glad to help however we can.

And, if you need help with any other aspect of CiviCRM -- from hosting to custom development to strategic consultation and more -- please contact us directly via https://joineryhq.com