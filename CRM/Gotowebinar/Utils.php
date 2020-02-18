<?php

class CRM_Gotowebinar_Utils {

  /**
   * DM: Function to refresh and to obtain new access token
   *
   * @return validToken
   */
  public function refreshAccessToken(){
    // FIX ME : currently not refreshing tokens automatically - if the the above response returns 'InvalidToken' error, setting validToken flag as FALSE and displaying authentication fields again.
    $validToken = FALSE;
    $refreshToken = CRM_Core_BAO_Setting::getItem(CRM_Gotowebinar_Form_Setting::WEBINAR_SETTING_GROUP,
      'refresh_token');
    //If refresh token is not available then return NULL
    if(!$refreshToken){
      return NULL;
    }
    //Setting up the curl fields
    //Retrieving the api_key and client_secret
    $apiKey  = CRM_Core_BAO_Setting::getItem(CRM_Gotowebinar_Form_Setting::WEBINAR_SETTING_GROUP, 'api_key');
    $clientSecret  = CRM_Core_BAO_Setting::getItem(CRM_Gotowebinar_Form_Setting::WEBINAR_SETTING_GROUP, 'client_secret');
    //Encoding the api key and client secret along with the ':' symbol into the base64 format
    $string = $apiKey.":".$clientSecret;
    $Base64EncodedCredentials = base64_encode($string);
    //Header fields are set
    $headers = array();
    $headers[] = "Authorization: Basic ".$Base64EncodedCredentials;
    $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    $url = WEBINAR_API_URL."/oauth/v2/token";
    $postFields = "grant_type=refresh_token&refresh_token=".$refreshToken;

    $response = CRM_Gotowebinar_Utils::apiCall($url, $headers, $postFields);
    $clientInfo = json_decode($response, TRUE);
    $validToken = CRM_Gotowebinar_Utils::storeAccessToken($clientInfo);
    return $validToken;
  }

  /**
   * DM: Function to store the new access_token, organizer_key and refresh_token
   *
   * @return TRUE(updated) / FALSE(not updated)
   */
  public function storeAccessToken($clientInfo){
    //Update the values iff all the keys exist in the array
    if(array_key_exists('access_token',$clientInfo) && array_key_exists('organizer_key',$clientInfo) && array_key_exists('refresh_token',$clientInfo)){
      CRM_Core_BAO_Setting::setItem($clientInfo['access_token'],
        CRM_Gotowebinar_Form_Setting::WEBINAR_SETTING_GROUP,
        'access_token'
      );
      CRM_Core_BAO_Setting::setItem($clientInfo['organizer_key'],
          CRM_Gotowebinar_Form_Setting::WEBINAR_SETTING_GROUP,
          'organizer_key'
      );
      CRM_Core_BAO_Setting::setItem($clientInfo['refresh_token'],
          CRM_Gotowebinar_Form_Setting::WEBINAR_SETTING_GROUP,
          'refresh_token'
      );
      return TRUE;
    }
    else{
      return FALSE;
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
    $apiResponse = curl_exec($curl);
    if (curl_errno($curl)) {
        echo 'Error:' . curl_error($curl);
    }
    curl_close($curl);
    return $apiResponse;
  }//DM

  /**
   *Function to register a participant for a webinar event
   */
  public function registerParticipant($webinar_key, $fields=NULL){
    $accessToken = CRM_Core_BAO_Setting::getItem(CRM_Gotowebinar_Form_Setting::WEBINAR_SETTING_GROUP,
        'access_token');
    $organizerKey = CRM_Core_BAO_Setting::getItem(CRM_Gotowebinar_Form_Setting::WEBINAR_SETTING_GROUP,
        'organizer_key');
    $url = WEBINAR_API_URL."/G2W/rest/organizers/".$organizerKey."/webinars/".$webinar_key."/registrants";
    $headers = array();
    $headers[] = "Authorization: OAuth oauth_token=".$accessToken;
    $headers[] = "Content-type:application/json";

    $result = CRM_Gotowebinar_Utils::apiCall($url, $headers, json_encode($fields));
    $response = json_decode($result, TRUE);
    return $response;
  }

}