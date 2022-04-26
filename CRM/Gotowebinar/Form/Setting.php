<?php

class CRM_Gotowebinar_Form_Setting extends CRM_Core_Form {

  const
    WEBINAR_SETTING_GROUP = 'Webinar Preferences';

   /**
   * Function to pre processing
   *
   * @return None
   * @access public
   */
  function preProcess() {
    $session      = CRM_Core_Session::singleton();
    $clientError  = $session->get("autherror");
    $this->assign('clienterror', $clientError);

    if (isset($_GET['state']) && $_GET['state'] == 'civicrmauthorize' && isset($_GET['code'])) {
      $redirectUrl = CRM_Utils_System::url('civicrm/gotowebinar/settings', NULL,  TRUE, NULL, FALSE, TRUE);
      // We have the authorization code so get the access token
      $authorizationCode = $_GET['code'];
      $apiKey = CRM_Gotowebinar_Utils::getItem(self::WEBINAR_SETTING_GROUP, 'api_key');
      $clientSecret = CRM_Gotowebinar_Utils::getItem(self::WEBINAR_SETTING_GROUP, 'client_secret');

      $url = WEBINAR_API_URL."/oauth/v2/token";
      //Setting up the curl fields
      $postFields = "grant_type=authorization_code&code={$authorizationCode}&redirect_uri=" . $redirectUrl;
      //Encoding the api key and client secret along with the ':' symbol into the base64 format
      $string = $apiKey.":".$clientSecret;
      $Base64EncodedCredentials = base64_encode($string);

      //Header fields are set
      $headers = array();
      $headers[] = "Authorization: Basic ".$Base64EncodedCredentials;
      $headers[] = 'Content-Type: application/x-www-form-urlencoded';

      $response = CRM_Gotowebinar_Utils::apiCall($url, $headers, $postFields);
      $clientInfo = json_decode($response, TRUE);

      if(isset($clientInfo['int_err_code']) && $clientInfo['int_err_code'] != '') {
          $session = CRM_Core_Session::singleton();
          $session->set("autherror", $clientInfo);
          CRM_Utils_System::redirect($redirectUrl);
      }
      elseif (isset($clientInfo['error'])) {
        CRM_Core_Error::statusBounce(
          ts($clientInfo['error']),
          $redirectUrl
        );
      }
      else {
        if($clientInfo['access_token'] && $clientInfo['organizer_key']) {
          CRM_Gotowebinar_Utils::storeAccessToken($clientInfo);
          $session = CRM_Core_Session::singleton();
          $session->set("autherror", NULL);
          CRM_Utils_System::redirect($redirectUrl);
          $upcomingWebinars = CRM_Gotowebinar_Form_Setting::findUpcomingWebinars();
          if(isset($upcomingWebinars['int_err_code']) and $upcomingWebinars['int_err_code'] != '') {
            $this->assign('error', $upcomingWebinars);
          } else {
            $this->assign('responseKey', TRUE);
            $this->assign('upcomingWebinars', $upcomingWebinars );
          }
        }
      }
    }

  }

  /**
   * Function to actually build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {

    $status = CRM_Event_PseudoConstant::participantStatus(NULL, NULL, 'label');
    foreach ($status as $id => $Name) {
      $this->addElement('checkbox', "participant_status_id[$id]", NULL, $Name);
    }

    $accessToken = CRM_Gotowebinar_Utils::getItem(self::WEBINAR_SETTING_GROUP,
      'access_token', NULL, FALSE
    );
    $organizerKey = CRM_Gotowebinar_Utils::getItem(self::WEBINAR_SETTING_GROUP,
      'organizer_key', NULL, FALSE
    );

    $validToken = FALSE;
    if($accessToken && $organizerKey) {
      $validToken = TRUE;
      $upcomingWebinars = CRM_Gotowebinar_Form_Setting::findUpcomingWebinars();
      //If Invalid token then refresh the accessToken and obtain the upcomingWebinars
      if(isset($upcomingWebinars['int_err_code']) && $upcomingWebinars['int_err_code'] == 'InvalidToken'){
        $validToken = CRM_Gotowebinar_Utils::refreshAccessToken();
        if($validToken){
          $upcomingWebinars = CRM_Gotowebinar_Form_Setting::findUpcomingWebinars();
        }
      }
      if(isset($upcomingWebinars['int_err_code']) and $upcomingWebinars['int_err_code'] != '') {
        $this->assign('error', $upcomingWebinars);
      } else {
        // GK 12102017 - Check each webinar's fields and display warning, if any of the webinars required additonal required fields
        foreach ($upcomingWebinars as $key => $webinar) {
          $registrationFields = CRM_Gotowebinar_Form_Setting::getRegistrationFields($webinar['webinarKey']);

          if (!empty($registrationFields) && isset($registrationFields['fields'])) {
            $numberOfFields = count($registrationFields['fields']);
            //firstName, lastName, email are mandatory fields in Webinar. If number of fields exceeds 3, display warning to the users
            $upcomingWebinars[$key]['warning'] = '';
            if ($numberOfFields > 3){
              $upcomingWebinars[$key]['warning'] = 'This Webinar has more mandatory fields. Please note that participants will not be updated from CiviCRM for this webinar, unless the required fields are removed from this webinar!';
            }
          }
        }

        $this->assign('responseKey', TRUE);
        $this->assign('upcomingWebinars', $upcomingWebinars );
        $buttons = array(
        array(
          'type' => 'submit',
          'name' => ts('Save Status'),
          ),
        );
        $this->addButtons($buttons);
      }

    }

    // If valid token not found, displaying the authentication fields
    if (!$validToken) {
      $this->add('password', 'api_key', ts('Consumer Key'), array(
        'size' => 48,
      ), TRUE);
      $this->add('password', 'client_secret', ts('Consumer Secret'), array(
        'size' => 48,
      ), TRUE);

      $buttons = array(
        array(
          'type' => 'submit',
          'name' => ts('Connect To My GoToWebinar'),
        ),
      );

      // Add the Buttons.
      $this->addButtons($buttons);
      $this->assign('initial', TRUE);
    }
  }

  public function setDefaultValues() {
    $defaults = $details = array();
    $status  = CRM_Gotowebinar_Utils::getItem(self::WEBINAR_SETTING_GROUP, 'participant_status');
    $apiKey  = CRM_Gotowebinar_Utils::getItem(self::WEBINAR_SETTING_GROUP, 'api_key');
    $clientSecret  = CRM_Gotowebinar_Utils::getItem(self::WEBINAR_SETTING_GROUP, 'client_secret');

    if($apiKey) {
      $defaults['api_key'] = $apiKey;
    }
    if($clientSecret) {
      $defaults['client_secret'] = $clientSecret;
    }
    if ($status) {
      foreach ($status as $key => $id) {
        $defaults['participant_status_id['.$id.']'] = 1;
      }
    }
    return $defaults;
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    // Store the submitted values in an array.
    $params = $this->controller->exportValues($this->_name);

    // Save the API Key & Save the Security Key
    if (CRM_Utils_Array::value('api_key', $params) && CRM_Utils_Array::value('client_secret', $params)) {

      $redirectUrl = CRM_Utils_System::url('civicrm/gotowebinar/settings', NULL,  TRUE, NULL, FALSE, TRUE);

      // Perform an authorization request, if the auth code is not set
      if (!isset($_GET['code'])) {

        //Storing the api_key and client_secret obtained from the form
        CRM_Gotowebinar_Utils::setItem($params['api_key'], self::WEBINAR_SETTING_GROUP, 'api_key');
        CRM_Gotowebinar_Utils::setItem($params['client_secret'], self::WEBINAR_SETTING_GROUP, 'client_secret');

        $authUrl = WEBINAR_API_URL."/oauth/v2/authorize" . "?response_type=code&state=civicrmauthorize&client_id=" . $params['api_key'] . "&redirect_uri=" . $redirectUrl;
        $authDestination = urldecode($authUrl);
        CRM_Utils_System::redirect($authDestination);
      }

    }

    // If gotowebinar was already connected, we introduced button called 'save status'
    if(isset($params['participant_status_id'])){
      CRM_Gotowebinar_Utils::setItem(array_keys($params['participant_status_id']),
          self::WEBINAR_SETTING_GROUP, 'participant_status'
        );
    }

  }

  static function findUpcomingWebinars() {

    $accessToken = CRM_Gotowebinar_Utils::getItem(self::WEBINAR_SETTING_GROUP,
      'access_token', NULL, FALSE
    );
    $organizerKey = CRM_Gotowebinar_Utils::getItem(self::WEBINAR_SETTING_GROUP,
    'organizer_key', NULL, FALSE
    );
    $url = WEBINAR_API_URL."/G2W/rest/organizers/".$organizerKey."/upcomingWebinars";
    //Setting up the curl fields
    $headers[] = "Authorization: OAuth oauth_token=".$accessToken;
    $headers[] = "Content-type:application/json";
    $response = CRM_Gotowebinar_Utils::apiCall($url, $headers, NULL);
    $webinarDetails = json_decode(preg_replace('/("\w+"):(-?\d+(.\d+)?)/', '\1:"\2"', $response), true);
    return  $webinarDetails;
  }

  // Function to get registration fields of a webinar
  static function getRegistrationFields($webinarKey) {
    $response = array();
    if (!$webinarKey) {
      return $response;
    }

    // FIX ME :  These post request needs to be moved into function and called everywhere
    $accessToken = CRM_Gotowebinar_Utils::getItem(self::WEBINAR_SETTING_GROUP,
      'access_token', NULL, FALSE
    );
    $organizerKey = CRM_Gotowebinar_Utils::getItem(self::WEBINAR_SETTING_GROUP,
    'organizer_key', NULL, FALSE
    );

    $url = WEBINAR_API_URL."/G2W/rest/organizers/".$organizerKey."/webinars/".$webinarKey."/registrants/fields";
    //Setting up the curl fields
    $headers[] = "Authorization: OAuth oauth_token=".$accessToken;
    $headers[] = "Content-type:application/json";
    $response = CRM_Gotowebinar_Utils::apiCall($url, $headers, NULL);
    $registrationFields = json_decode(preg_replace('/("\w+"):(-?\d+(.\d+)?)/', '\1:"\2"', $response), true);
    return  $registrationFields;
  }

}
