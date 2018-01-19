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

    $this->addElement('text', 'api_key', ts('API Key'), array(
      'size' => 48,
    ));

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
      if(isset($upcomingWebinars['int_err_code']) and $upcomingWebinars['int_err_code'] != '') {
        $this->assign('error', $upcomingWebinars);
        // FIX ME : currently not refreshing tokens automatically - if the the above response returns 'InvalidToken' error, setting validToken flag as FALSE and displaying authentication fields again.
        if ($upcomingWebinars['int_err_code'] == 'InvalidToken') {
          $validToken = FALSE;
          // display the error
          CRM_Core_Session::setStatus(ts('Tokens stored are not valid/expired. Refresh the tokens using your login details'), ts('Invalid/Expired Token'), 'error');
        }
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
      $this->add('text', "email_address", ts('Email Address'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_Email', 'email'), TRUE);
      $this->add('text', "password", ts('Password'), array('size' => 48,), TRUE);
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
    if(WEBINAR_KEY) {
      $defaults['api_key'] = WEBINAR_KEY;
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
    CRM_Core_BAO_Setting::setItem(array_keys($params['participant_status_id']),
        self::WEBINAR_SETTING_GROUP, 'participant_status'
      );

    // Save the API Key & Save the Security Key
    if (CRM_Utils_Array::value('api_key', $params) && CRM_Utils_Array::value('email_address', $params) && CRM_Utils_Array::value('password', $params)) {
      CRM_Core_BAO_Setting::setItem(array_keys($params['participant_status_id']),
        self::WEBINAR_SETTING_GROUP, 'participant_status'
      );
      $apiKey         = urlencode($params['api_key']);
      $username       = urlencode($params['email_address']);
      $password       = urlencode($params['password']);
      $redirectUrl    = CRM_Utils_System::url('civicrm/gotowebinar/settings', 'reset=1',  TRUE, NULL, FALSE, TRUE);

      $url = WEBINAR_API_URL."/oauth/access_token?grant_type=password&user_id=".$username."&password=".$password."&client_id=".$apiKey;
      $clientInfo   = $this->requestPost($url);
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
          CRM_Core_BAO_Setting::setItem($clientInfo['access_token'],
            self::WEBINAR_SETTING_GROUP,
            'access_token'
          );
          CRM_Core_BAO_Setting::setItem($clientInfo['organizer_key'],
              self::WEBINAR_SETTING_GROUP,
              'organizer_key'
          );
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

  function requestPost($url){
    set_time_limit(160);
    // Initialise output variable
    $output = array();
    $options = array(
                    CURLOPT_RETURNTRANSFER => true, // return web page
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_SSL_VERIFYPEER => false,
                  );
    $session = curl_init( $url );
    curl_setopt_array( $session, $options );

    $output = curl_exec($session);
    $header = curl_getinfo( $session );
    $response = json_decode($output, TRUE);
    CRM_Core_Error::debug_var('Connection response', $response);
    return $response;
  } // END function request

  static function findUpcomingWebinars() {

    $accessToken = CRM_Core_BAO_Setting::getItem(self::WEBINAR_SETTING_GROUP,
      'access_token', NULL, FALSE
    );
    $organizerKey = CRM_Core_BAO_Setting::getItem(self::WEBINAR_SETTING_GROUP,
    'organizer_key', NULL, FALSE
    );
    $url = WEBINAR_API_URL."/G2W/rest/organizers/".$organizerKey."/upcomingWebinars";

    $options = array(
                    CURLOPT_RETURNTRANSFER => TRUE, // return web page
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_HTTPHEADER => array("Authorization: OAuth oauth_token=".$accessToken, "Content-type:application/json"),
                    CURLOPT_HEADER  => FALSE,
                  );

    $session  = curl_init( $url );
    curl_setopt_array( $session, $options );
    $output   = curl_exec($session);
    $header   = curl_getinfo( $session );
    $response = json_decode(preg_replace('/("\w+"):(-?\d+(.\d+)?)/', '\1:"\2"', $output), true);
    return  $response;
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

    $options = array(
                    CURLOPT_RETURNTRANSFER => TRUE, // return web page
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_HTTPHEADER => array("Authorization: OAuth oauth_token=".$accessToken, "Content-type:application/json"),
                    CURLOPT_HEADER  => FALSE,
                  );

    $session  = curl_init( $url );
    curl_setopt_array( $session, $options );
    $output   = curl_exec($session);
    $header   = curl_getinfo( $session );
    $response = json_decode(preg_replace('/("\w+"):(-?\d+(.\d+)?)/', '\1:"\2"', $output), true);
    return  $response;
  }

}
