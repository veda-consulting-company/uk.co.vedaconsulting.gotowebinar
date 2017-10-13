# GoToWebinar Integration for CiviCRM Events #

### Overview ###

CiviCRM Events can be integrated with GoToWebinar

### Installation ###

* Install the extension manually in CiviCRM. More details [here](http://wiki.civicrm.org/confluence/display/CRMDOC/Extensions#Extensions-Installinganewextension) about installing extensions in CiviCRM.
* Configure GoToWebinar details in Events >> GoToWebinar Settings(civicrm/gotowebinar/settings?reset=1)
![Screenshot of integration options](images/webinar-setting-page.jpg)

* After the successful authentication, list of upcoming webinars will be fetched and displayed on the screen. (Note : Firstname, lastname & email are the only fields that get pushed to webinar for now. Any webinars with additional mandatory fields will not be getting the participants added from CiviCRM)

![Screenshot of integration options](images/setting-page-after-auth.jpg)

### Usage ###

* Setup CiviCRM Event with Webinar Key
![Screenshot of integration options](images/manage-event.jpg)

* When participants register for that civiCRM Event, those participants are automatically created for GoToWebinar Event

### Changelog ###

#### Ver 2.0.0 ####
* Migrated to new Webinar API.
* Re-Autheticate when the accesstoken become invalid/expired
* Display warning, if a webinar has additonal mandatory fields
* Display failure messages on thank you page, if push pariticipant to Webinar failed

### Support ###

support (at) vedaconsulting.co.uk


