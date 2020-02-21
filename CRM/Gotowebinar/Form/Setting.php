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

    $accessToken = CRM_Core_BAO_Setting::getItem(self::WEBINAR_SETTING_GROUP,
      'access_token', NULL, FALSE
    );
    $organizerKey = CRM_Core_BAO_Setting::getItem(self::WEBINAR_SETTING_GROUP,
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

          if (!empty($registrationFields && isset($registrationFields['fields']))) {
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
      $this->add('password', 'api_key', ts('API Key'), array(
        'size' => 48,
      ), TRUE);
      $this->add('password', 'client_secret', ts('Client Secret'), array(
        'size' => 48,
      ), TRUE);
      $this->add('text', "email_address", ts('Email Address'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_Email', 'email'), TRUE);
      $this->add('password', 'password', ts('Password'), ['autocomplete' => 'off'], TRUE);
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
    $status  = CRM_Core_BAO_Setting::getItem(self::WEBINAR_SETTING_GROUP, 'participant_status');
    $apiKey  = CRM_Core_BAO_Setting::getItem(self::WEBINAR_SETTING_GROUP, 'api_key');
    $clientSecret  = CRM_Core_BAO_Setting::getItem(self::WEBINAR_SETTING_GROUP, 'client_secret');

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
    // If gotowebinar was already connected, we introduced button called 'save status'
    if(isset($params['participant_status_id'])){
      CRM_Core_BAO_Setting::setItem(array_keys($params['participant_status_id']),
          self::WEBINAR_SETTING_GROUP, 'participant_status'
        );
    }

    // Save the API Key & Save the Security Key
    if (CRM_Utils_Array::value('api_key', $params) && CRM_Utils_Array::value('client_secret', $params)
      && CRM_Utils_Array::value('email_address', $params) && CRM_Utils_Array::value('password', $params)) {
      //Storing the api_key and client_secret obtained from the form
      CRM_Core_BAO_Setting::setItem($params['api_key'],
        self::WEBINAR_SETTING_GROUP,
        'api_key'
      );
      CRM_Core_BAO_Setting::setItem($params['client_secret'],
        self::WEBINAR_SETTING_GROUP,
        'client_secret'
      );

      $redirectUrl    = CRM_Utils_System::url('civicrm/gotowebinar/settings', 'reset=1',  TRUE, NULL, FALSE, TRUE);
      $url = WEBINAR_API_URL."/oauth/v2/token";
      //Setting up the curl fields
      $postFields = "username=".$params['email_address']."&password=".$params['password']."&grant_type=password";
      //Encoding the api key and client secret along with the ':' symbol into the base64 format
      $string = $params['api_key'].":".$params['client_secret'];
      $Base64EncodedCredentials = base64_encode($string);
      //Header fields are set
      $headers = array();
      $headers[] = "Authorization: Basic ".$Base64EncodedCredentials;
      $headers[] = 'Content-Type: application/x-www-form-urlencoded';
      $url = WEBINAR_API_URL."/oauth/v2/token";

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

  static function findUpcomingWebinars() {

    $accessToken = CRM_Core_BAO_Setting::getItem(self::WEBINAR_SETTING_GROUP,
      'access_token', NULL, FALSE
    );
    $organizerKey = CRM_Core_BAO_Setting::getItem(self::WEBINAR_SETTING_GROUP,
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
    $accessToken = CRM_Core_BAO_Setting::getItem(self::WEBINAR_SETTING_GROUP,
      'access_token', NULL, FALSE
    );
    $organizerKey = CRM_Core_BAO_Setting::getItem(self::WEBINAR_SETTING_GROUP,
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
