# GoToWebinar Integration for CiviCRM Events #

### Overview ###

CiviCRM Events can be integrated with GoToWebinar

### Installation ###

* Install the extension in CiviCRM. More details [here](https://docs.civicrm.org/sysadmin/en/latest/customize/extensions/#installing-a-new-extension) about installing extensions in CiviCRM.
* Configure GoToWebinar details in **Events >> GoToWebinar Settings** (civicrm/gotowebinar/settings?reset=1)
![Screenshot of integration options](images/webinar-setting-page.jpg)

* After a successful authentication, a list of upcoming webinars will be fetched and displayed on the screen. (Note: First name, last name & email are the only fields that get pushed to the webinar for now. Webinars with additional mandatory fields will not get participants added from CiviCRM)

![Screenshot of integration options](images/setting-page-after-auth.jpg)

### Usage ###

* Setup CiviCRM Event with a Webinar Key
![Screenshot of integration options](images/manage-event.jpg)

* When participants register for that civiCRM Event, those participants are automatically created for GoToWebinar Event

### Changelog ###

#### Ver 3.0.0 ####
* Migrated to a new Webinar API which uses OAuthv2 standard
* Auto refresh to obtain new access token and refresh token

#### Ver 2.0.0 ####
* Migrated to new Webinar API.
* Re-Autheticate when the accesstoken become invalid/expired
* Display warning, if a webinar has additonal mandatory fields
* Display failure messages on thank you page, if push pariticipant to Webinar failed

### Support ###

support@vedaconsulting.co.uk


