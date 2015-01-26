<?php

class CRM_Gotowebinar_Form_Setting extends CRM_Core_Form {

  const 
    MC_SETTING_GROUP = 'Webinar Preferences';
  
   /**
   * Function to pre processing
   *
   * @return None
   * @access public
   */
  function preProcess() { 
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
     $accessToken = CRM_Core_BAO_Setting::getItem(self::MC_SETTING_GROUP,
      'access_token', NULL, FALSE
    );
      $organizerKey = CRM_Core_BAO_Setting::getItem(self::MC_SETTING_GROUP,
      'organizer_key', NULL, FALSE
    );
      
    if($accessToken && $organizerKey) {
      $upcomingWebinars = CRM_Gotowebinar_Form_Setting::findUpcomingWebinars();
      if(isset($upcomingWebinars['int_err_code']) and $upcomingWebinars['int_err_code'] != '') {
        $this->assign('error', $upcomingWebinars['int_err_code']);
      } else {
        $this->assign('responseKey', TRUE);
        $this->assign('upcomingWebinars', $upcomingWebinars );
      }
      
    }elseif(CRM_Utils_Request::retrieve('code','String' , $this) ) {
      
      $apiKey       = WEBINAR_KEY;
      $responseKey  = CRM_Utils_Array::value('code', $_GET, '');
      $url          = "https://api.citrixonline.com/oauth/access_token?grant_type=authorization_code&code=".$responseKey."&client_id=".$apiKey;
      $output       = array();
      $clientInfo   = $this->requestPost($url);
      if($clientInfo['access_token'] && $clientInfo['organizer_key']) {
        CRM_Core_BAO_Setting::setItem($clientInfo['access_token'],
          self::MC_SETTING_GROUP,
          'access_token'
        );
        CRM_Core_BAO_Setting::setItem($clientInfo['organizer_key'],
            self::MC_SETTING_GROUP,
            'organizer_key'
        );
        
        $upcomingWebinars = CRM_Gotowebinar_Form_Setting::findUpcomingWebinars();
        if(isset($upcomingWebinars['int_err_code']) and $upcomingWebinars['int_err_code'] != '') {
          $this->assign('error', $upcomingWebinars['int_err_code']);
        } else {
          $this->assign('responseKey', TRUE);
          $this->assign('upcomingWebinars', $upcomingWebinars );
        }
       
      }
    }else {
  
      $buttons = array(
        array(
          'type' => 'submit',
          'name' => ts('Connect To My GoToWebinar'),
        ),
      );

      // Add the Buttons.
      $this->addButtons($buttons);
    }
  }

  public function setDefaultValues() {
    $defaults = $details = array();

    if(WEBINAR_KEY) {
      $defaults['api_key'] = WEBINAR_KEY;
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
    if (CRM_Utils_Array::value('api_key', $params)) {
      
    $apiKey       = urlencode($params['api_key']);
    $redirectUrl  = CRM_Utils_System::url('civicrm/gotowebinar/settings', 'reset=1',  TRUE, NULL, FALSE, TRUE);
    
    // Redirect to Webinar 
    $url = "https://api.citrixonline.com/oauth/authorize?client_id=".$apiKey."&redirect_uri=".$redirectUrl;
    CRM_Utils_System::redirect($url);
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
        
    return json_decode($output, TRUE);
  } // END function request
  
  static function findUpcomingWebinars() {
    
    $accessToken = CRM_Core_BAO_Setting::getItem(self::MC_SETTING_GROUP,
      'access_token', NULL, FALSE
    );
    $organizerKey = CRM_Core_BAO_Setting::getItem(self::MC_SETTING_GROUP,
    'organizer_key', NULL, FALSE
    );
    $url = "https://api.citrixonline.com/G2W/rest/organizers/".$organizerKey."/upcomingWebinars";
    
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
    $response = json_decode($output, TRUE, 1024, JSON_BIGINT_AS_STRING);
    return  $response;
  }
}
