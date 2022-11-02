# com.joineryhq.eventbrite

This extension is licensed under [GPL-3.0](LICENSE.txt).

## NOTICE of project status
### This project is known to have significant issues with the current EventBrite API.
Although it worked well when first released, EB has made changes to their API,
and this extension is very likely not to work completely, or perhaps in any usable way.

If you're a current EventBrite user who'd like to help sponsor the work needed get
this working again, please contact us directly via https://joineryhq.com to discuss
your needs.

## What it does
Provides synchronization to CiviCRM for participants and payments registered through
Eventbrite, updating your CiviCRM data only (i.e., data from Eventrite is updated
in CiviCRM, not the other way around).

## Usage

Configuration options are available at Administer > CiviEvent > Eventbrite Integration

* The extension asks for your "Personal OAuth Token"; this may also be called the
"Private API key" under the properties of the API Key in your [Evenbrite account settings](https://www.eventbrite.com/account-settings/apps).

To test, run the Scheduled Job called Call Eventbrite.Runqueue API manually.

## Support
![screenshot](/images/joinery-logo.png)

Joinery provides services for CiviCRM including custom extension development, training, data migrations, and more.

**This extension is known to have significant issues with the current EventBrite API.**

If you require urgent or highly customized improvements to this extension, we may
suggest conducting a fee-based project under our standard commercial terms.  In
any case, the place to start is the [github issue queue](https://github.com/twomice/com.joineryhq.eventbrite/issues) --
let us hear what you need and we'll be glad to help however we can.

And, if you need help with any other aspect of CiviCRM -- from hosting to custom
development to strategic consultation and more -- please contact us directly via
https://joineryhq.com
