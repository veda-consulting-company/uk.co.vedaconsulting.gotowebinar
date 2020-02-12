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
    $this->addElement('text', 'client_secret', ts('Client Secret'), array(
      'size' => 48,
    ));

    $status = CRM_Event_PseudoConstant::participantStatus(NULL, NULL, 'label');
    foreach ($status as $id => $Name) {
      $this->addElement('checkbox', "participant_status_id[$id]", NULL, $Name);
    }

    $accessToken = CRM_Core_BAO_Setting::getItem(self::WEBINAR_SETTING_GROUP,
      'access_token');
    $organizerKey = CRM_Core_BAO_Setting::getItem(self::WEBINAR_SETTING_GROUP,
      'organizer_key');

    //DM: changed according to the new api amendments
    $validToken = FALSE;
    if($accessToken && $organizerKey) {
      $validToken = TRUE;
      $upcomingWebinars = CRM_Gotowebinar_Form_Setting::findUpcomingWebinars();
      if ( isset($upcomingWebinars['int_err_code']) && ($upcomingWebinars['int_err_code'] == 'InvalidToken') ) {
        $validToken = CRM_Gotowebinar_Form_Setting::refreshAccessToken();
        if($validToken){
          $upcomingWebinars = CRM_Gotowebinar_Form_Setting::findUpcomingWebinars();
        }
        else{
          $this->assign('error', $upcomingWebinars);
          // display the error if there are errors in obtaining the access token
          CRM_Core_Session::setStatus(ts('Tokens stored are not valid/expired. Refresh the tokens using your login details'), ts('Invalid/Expired Token'), 'error');
        }
      }
    }

    // GK 12102017 - Check each webinar's fields and display warning, if any of the webinars required additonal required fields
    if($validToken){
      foreach ($upcomingWebinars as $key => $webinar) {
        $registrationFields = CRM_Gotowebinar_Form_Setting::getRegistrationFields($webinar['webinarKey']);
        if( isset($registrationFields['int_err_code']) && ($registrationFields['int_err_code'] == 'InvalidToken') ) {
          //Refreshing the access token
          $validToken = CRM_Gotowebinar_Form_Setting::refreshAccessToken();
          if($validToken){
            $registrationFields = CRM_Gotowebinar_Form_Setting::getRegistrationFields($webinar['webinarKey']);
          }
        }

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

  /**
   * DM: Function to refresh and to obtain new access token
   *
   * @return validToken
   */
  static function refreshAccessToken(){
    // FIX ME : currently not refreshing tokens automatically - if the the above response returns 'InvalidToken' error, setting validToken flag as FALSE and displaying authentication fields again.
    $validToken = FALSE;
    $refreshToken = CRM_Core_BAO_Setting::getItem(self::WEBINAR_SETTING_GROUP,
      'refresh_token');
    //If refresh token is not available then return NULL
    if(!$refreshToken){
      return NULL;
    }
    //Setting up the curl fields
    //Encoding the api key and client secret along with the ':' symbol into the base64 format
    $string = WEBINAR_KEY.":".CLIENT_SECRET;
    $Base64EncodedCredentials = base64_encode($string);
    //Header fields are set
    $headers = array();
    $headers[] = "Authorization: Basic ".$Base64EncodedCredentials;
    $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    $url = WEBINAR_API_URL."/oauth/v2/token";
    $postFields = "grant_type=refresh_token&refresh_token=".$refreshToken;

    $response = CRM_Gotowebinar_Form_Setting::apiCall($url, $headers, $postFields);
    $clientInfo = json_decode($response, TRUE);
    $validToken = CRM_Gotowebinar_Form_Setting::storeAccessToken($clientInfo);
    return $validToken;
  }

  /**
   * DM: Function to store the new access_token, organizer_key and refresh_token
   *
   * @return TRUE(updated) / FALSE(not updated)
   */
  static function storeAccessToken($clientInfo){
    //Update the values iff all the keys exist in the array
    if(array_key_exists('access_token',$clientInfo) && array_key_exists('organizer_key',$clientInfo) && array_key_exists('refresh_token',$clientInfo)){
      CRM_Core_BAO_Setting::setItem($clientInfo['access_token'],
        self::WEBINAR_SETTING_GROUP,
        'access_token'
      );
      CRM_Core_BAO_Setting::setItem($clientInfo['organizer_key'],
          self::WEBINAR_SETTING_GROUP,
          'organizer_key'
      );
      CRM_Core_BAO_Setting::setItem($clientInfo['refresh_token'],
          self::WEBINAR_SETTING_GROUP,
          'refresh_token'
      );
      return TRUE;
    }
    else{
      return FALSE;
    }
  }

  public function setDefaultValues() {
    $defaults = $details = array();
    $status  = CRM_Core_BAO_Setting::getItem(self::WEBINAR_SETTING_GROUP, 'participant_status');
    if(WEBINAR_KEY) {
      $defaults['api_key'] = WEBINAR_KEY;
    }
    if(CLIENT_SECRET) {
      $defaults['client_secret'] = CLIENT_SECRET;
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

    //DM: Added Consumer Key for authentication
    // Save the API Key & Consumer Key
    if (CRM_Utils_Array::value('api_key', $params) && CRM_Utils_Array::value('client_secret', $params)
      && CRM_Utils_Array::value('email_address', $params) && CRM_Utils_Array::value('password', $params)) {
      CRM_Core_BAO_Setting::setItem(array_keys($params['participant_status_id']),
        self::WEBINAR_SETTING_GROUP, 'participant_status'
      );

      //Getting the fields needed for curl execution
      $redirectUrl    = CRM_Utils_System::url('civicrm/gotowebinar/settings', 'reset=1',  TRUE, NULL, FALSE, TRUE);
      $url = WEBINAR_API_URL."/oauth/v2/token";
      $postFields = "username=".$params['email_address']."&password=".$params['password']."&grant_type=password";
      //Encoding the api key and client secret along with the ':' symbol into the base64 format
      $string = $params['api_key'].":".$params['client_secret'];
      $Base64EncodedCredentials = base64_encode($string);
      //Header fields are set
      $headers = array();
      $headers[] = "Authorization: Basic ".$Base64EncodedCredentials;
      $headers[] = 'Content-Type: application/x-www-form-urlencoded';
      $url = WEBINAR_API_URL."/oauth/v2/token";

      $response = CRM_Gotowebinar_Form_Setting::apiCall($url, $headers, $postFields);
      $clientInfo = json_decode($response, TRUE);
      //DM
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
        $validToken = CRM_Gotowebinar_Form_Setting::storeAccessToken($clientInfo);
        if($validToken){
          $session = CRM_Core_Session::singleton();
          $session->set("autherror", NULL);
          CRM_Utils_System::redirect($redirectUrl);
          $upcomingWebinars = CRM_Gotowebinar_Form_Setting::findUpcomingWebinars();
          if(isset($upcomingWebinars['int_err_code']) and $upcomingWebinars['int_err_code'] != '') {
            $this->assign('error', $upcomingWebinars);
          } else {
            $this->assign('responseKey', TRUE);
            $this->assign('upcomingWebinars', $upcomingWebinars);
          }
        }
      }
    }
  }

  /**
   * DM: Function to do all the api calls and return the response from the server
   *
   * @return response/result
   */
  static function apiCall(
    $url = NULL,
    $headers = NULL,
    $postFields = NULL
    ){
    if(!$url){
      return NULL;
    }
    set_time_limit(160);

    //curl initiation
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    if(!empty($postFields)){
      curl_setopt($curl, CURLOPT_POST, 1);
      curl_setopt($curl, CURLOPT_POSTFIELDS, $postFields);
    }
    if(!empty($headers)){
      curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    }

    //curl execution
    $result = curl_exec($curl);
    if (curl_errno($curl)) {
        echo 'Error:' . curl_error($curl);
    }
    curl_close($curl);
    return $result;
  }//DM

  // Function to get  the details of the upcoming webinars
  static function findUpcomingWebinars() {
    $accessToken = CRM_Core_BAO_Setting::getItem(self::WEBINAR_SETTING_GROUP,
      'access_token');
    $organizerKey = CRM_Core_BAO_Setting::getItem(self::WEBINAR_SETTING_GROUP,
    'organizer_key');
    //Setting up the curl fields
    $url = WEBINAR_API_URL."/G2W/rest/organizers/".$organizerKey."/upcomingWebinars";

    $headers[] = "Authorization: OAuth oauth_token=".$accessToken;
    $headers[] = "Content-type:application/json";

    $response = CRM_Gotowebinar_Form_Setting::apiCall($url, $headers, NULL);
    $webinarDetails = json_decode(preg_replace('/("\w+"):(-?\d+(.\d+)?)/', '\1:"\2"', $response), true);

    return  $webinarDetails;
  }

  // Function to get registration fields of a webinar
  static function getRegistrationFields($webinarKey) {
    $response = array();
    if (!$webinarKey) {
      return $response;
    }

    $accessToken = CRM_Core_BAO_Setting::getItem(self::WEBINAR_SETTING_GROUP,
      'access_token');
    $organizerKey = CRM_Core_BAO_Setting::getItem(self::WEBINAR_SETTING_GROUP,
    'organizer_key');
    //Setting up the curl fields
    $url = WEBINAR_API_URL."/G2W/rest/organizers/".$organizerKey."/webinars/".$webinarKey."/registrants/fields";
    $headers[] = "Authorization: OAuth oauth_token=".$accessToken;
    $headers[] = "Content-type:application/json";
    $response = CRM_Gotowebinar_Form_Setting::apiCall($url, $headers, NULL);
    $registrationFields = json_decode(preg_replace('/("\w+"):(-?\d+(.\d+)?)/', '\1:"\2"', $response), true);

    return  $registrationFields;
  }
}
