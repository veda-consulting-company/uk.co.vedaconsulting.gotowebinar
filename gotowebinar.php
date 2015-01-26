<?php

require_once 'gotowebinar.civix.php';
define('WEBINAR_KEY', 'gL39JZHP8Uz1yMu3ii8vDAoKu8CDehZM');

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function gotowebinar_civicrm_config(&$config) {
  _gotowebinar_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function gotowebinar_civicrm_xmlMenu(&$files) {
  _gotowebinar_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function gotowebinar_civicrm_install() {
  
  #create custom group from xml file
  $extensionDir       = dirname( __FILE__ ) . DIRECTORY_SEPARATOR;
  $customDataXMLFile  = $extensionDir  . 'auto_install.xml';
  require_once 'CRM/Utils/Migrate/Import.php';
  $import = new CRM_Utils_Migrate_Import( );
  $import->run( $customDataXMLFile );
  return _gotowebinar_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function gotowebinar_civicrm_uninstall() {
  return _gotowebinar_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function gotowebinar_civicrm_enable() {
  return _gotowebinar_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function gotowebinar_civicrm_disable() {
  return _gotowebinar_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function gotowebinar_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _gotowebinar_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function gotowebinar_civicrm_managed(&$entities) {
  return _gotowebinar_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function gotowebinar_civicrm_caseTypes(&$caseTypes) {
  _gotowebinar_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function gotowebinar_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _gotowebinar_civix_civicrm_alterSettingsFolders($metaDataFolders);
}


function gotowebinar_civicrm_navigationMenu(&$params){
  $parentId             = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Navigation', 'Mailings', 'id', 'name');
  $maxId                = max(array_keys($params));
  $gotoWebinarMaxId     = $maxId+1;

  $params[$parentId]['child'][$gotoWebinarMaxId] = array(
        'attributes' => array(
          'label'     => ts('GoToWebinar Settings'),
          'name'      => 'Gotowebinar_Settings',
          'url'       => 'civicrm/gotowebinar/settings?reset=1',
          'active'    => 1,
          'parentID'  => $parentId,
          'operator'  => NULL,
          'navID'     => $gotoWebinarMaxId,
          'permission'=> 'administer CiviCRM',
        ),
  );
}

function gotowebinar_civicrm_postProcess($class, &$form) {
  
  if (!in_array($class, array(
    'CRM_Event_Form_Registration_Confirm',
  ))) {
    return;
  }
  
  $custom_group_name = 'Webinar_Event';
  $customGroupParams = array(
      'version'     => 3,
      'sequential'  => 1,
      'name'        => $custom_group_name,
  );
  $custom_group_ret = civicrm_api('CustomGroup', 'GET', $customGroupParams);
  if ($custom_group_ret['is_error'] || $custom_group_ret['count'] == 0) {
      throw new CRM_Finance_BAO_Import_ValidateException(
              "Can't find custom group for Financial_Import_Reference",
              $excCode,
              $value);
  }
  $customGroupID = $custom_group_ret['id'];
  $customGroupTableName = $custom_group_ret['values'][0]['table_name'];

  // Now try and find a record with the reference passed
  $customGroupParams = array(
      'version' => 3,
      'sequential' => 1,
      'custom_group_id' => $customGroupID,
  );
  $custom_field_ret = civicrm_api ('CustomField','GET',$customGroupParams);
  foreach($custom_field_ret['values'] as $k => $field){
    $field_attributes[$field['name']] = $field;
  }
   
  $webinarColumn  = $field_attributes['Webinar_id']['column_name'];
  $eventID        = $form->getVar('_eventId');
  $query          = "SELECT $webinarColumn AS webinar_id FROM $customGroupTableName WHERE entity_id = {$eventID}";
  $webinar_key    = CRM_Core_DAO::singleValueQuery($query);
  
  if(!empty($webinar_key)) {
    $accessToken = CRM_Core_BAO_Setting::getItem(CRM_Gotowebinar_Form_Setting::MC_SETTING_GROUP,
        'access_token', NULL, FALSE
      );
    $organizerKey = CRM_Core_BAO_Setting::getItem(CRM_Gotowebinar_Form_Setting::MC_SETTING_GROUP,
        'organizer_key', NULL, FALSE
      );
    $url = "https://api.citrixonline.com/G2W/rest/organizers/".$organizerKey."/webinars/".$webinar_key."/registrants";
    $options = array(
                    CURLOPT_RETURNTRANSFER => TRUE, // return web page
                    CURLOPT_SSL_VERIFYHOST => FALSE,
                    CURLOPT_SSL_VERIFYPEER => FALSE,
                    CURLOPT_HTTPHEADER     => array("Authorization: OAuth oauth_token=".$accessToken, "Content-type:application/json"),
                    CURLOPT_HEADER         => FALSE,
                  );
    $session = curl_init( $url );
    curl_setopt_array( $session, $options );
  
    $pids = $form->getVar('_participantIDS');
    $pidString = implode(',', $pids);
    
    $ParticipantQuery = "
      SELECT cc.`first_name`, cc.`last_name`, ce.email FROM `civicrm_contact` cc
      INNER JOIN civicrm_email ce ON (ce.contact_id = cc.id AND ce.is_primary = 1)
      INNER JOIN civicrm_participant cp ON cp.contact_id = cc.id
      WHERE cp.id IN ($pidString)";
      
      $fields = array();
      $fieldsDao = CRM_Core_DAO::executeQuery($ParticipantQuery);
      while($fieldsDao->fetch()) {
        $fields = array(
          'firstName' => $fieldsDao->first_name,
          'lastName'  => $fieldsDao->last_name,
          'email'     => $fieldsDao->email,
        );
        
        curl_setopt ($session, CURLOPT_POSTFIELDS, json_encode($fields));
        $output = curl_exec($session);
        $header = curl_getinfo( $session );
        $response = json_decode($output, TRUE);
      }
  }
}

function gotowebinar_civicrm_buildForm($formName, &$form) {
  if ($formName == 'CRM_Event_Form_ManageEvent_EventInfo' AND ($form->getAction() == CRM_Core_Action::ADD OR $form->getAction() == CRM_Core_Action::UPDATE)) {
       
     $accessToken = CRM_Core_BAO_Setting::getItem(CRM_Gotowebinar_Form_Setting::MC_SETTING_GROUP,
      'access_token', NULL, FALSE
    );
      $organizerKey = CRM_Core_BAO_Setting::getItem(CRM_Gotowebinar_Form_Setting::MC_SETTING_GROUP,
      'organizer_key', NULL, FALSE
    );
      
    if($accessToken && $organizerKey) {
      $upcomingWebinars = CRM_Gotowebinar_Form_Setting::findUpcomingWebinars();
      $form->assign('upcomingWebinars', $upcomingWebinars );
    }
  }
}
