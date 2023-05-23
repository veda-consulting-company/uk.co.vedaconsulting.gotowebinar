<?php

require_once 'gotowebinar.civix.php';
define('WEBINAR_API_URL', 'https://api.getgo.com');
define('REGISTRANT_KEY','Registrant_Key');

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
  return;
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
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function gotowebinar_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
}

function gotowebinar_civicrm_navigationMenu(&$params){
  $parentId             = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Navigation', 'Events', 'id', 'name');
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

function gotowebinar_civicrm_post( $op, $objectName, $objectId, &$objectRef ) {
  if (($op == 'create' || $op == 'edit') && $objectName == 'Participant') {
    $eventID  = $objectRef->event_id;
    $pid      = $objectId;

    $participantQuery = "
      SELECT cc.`first_name`, cc.`last_name`, ce.email FROM `civicrm_contact` cc
      INNER JOIN civicrm_email ce ON (ce.contact_id = cc.id AND ce.is_primary = 1)
      INNER JOIN civicrm_participant cp ON cp.contact_id = cc.id
      LEFT JOIN civicrm_value_webinar_participant cw ON cp.id = cw.entity_id";

    $where  = " WHERE cp.id = %1 AND (cw.registrant_key is null OR cw.registrant_key='')";
    $status = CRM_Gotowebinar_Utils::getItem(CRM_Gotowebinar_Form_Setting::WEBINAR_SETTING_GROUP, 'participant_status');
    if (!empty($status)) {
      $statusValues = array_values($status);
      $where .= " AND cp.status_id IN ( ".implode(' , ', $statusValues)." )";
    }
    $participantQueryParams = array(1 => array($pid, 'Int'));
    $sql = $participantQuery.$where;
    $fields = array();
    $fieldsDao = CRM_Core_DAO::executeQuery($sql, $participantQueryParams);

    while($fieldsDao->fetch()) {
      $fields = array(
      'firstName' => $fieldsDao->first_name,
      'lastName'  => $fieldsDao->last_name,
      'email'     => $fieldsDao->email,
      );
    }

    //48 exit if no matching record (e.g. unsynched status)
    if (empty($fields)) {
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
        throw new CRM_Core_Exception("Can't find custom group for Webinar_Event");
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
    $query          = "SELECT $webinarColumn AS webinar_id FROM $customGroupTableName WHERE entity_id = {$eventID}";
    $webinar_key    = CRM_Core_DAO::singleValueQuery($query);

    if(!empty($webinar_key)) {
      $response = CRM_Gotowebinar_Utils::registerParticipant($webinar_key, $fields);

      if ((isset($response['int_err_code'])) && ($response['int_err_code'] == 'InvalidToken')) {
        $validToken = CRM_Gotowebinar_Utils::refreshAccessToken();
        if($validToken){
          $response = CRM_Gotowebinar_Utils::registerParticipant($webinar_key, $fields);
        }
      }

      // display if any errors and return
      if ( isset($response['errorCode']) && !empty($response['errorCode']) ) {
        $errorCode = 'Webinar Error : '.$response['errorCode'];
        CRM_Core_Session::setStatus(ts($response['description']), ts($errorCode), 'error');
        return;
      }
      $customGroupDetails = civicrm_api3('CustomGroup', 'get', [
        'sequential' => 1,
        'name' => "Webinar_Participant",
        ]);
      $custom_group_id = $customGroupDetails['id'];
      $custom_group_table_name = $customGroupDetails['values'][0]['table_name'];
      $customFieldDetails = civicrm_api3('CustomField', 'get', [
        'sequential' => 1,
        'name' => REGISTRANT_KEY,
        'custom_group_id' => $custom_group_id,
        ]);
      $regColName = $customFieldDetails['values'][0]['column_name'];

      $customTableQuery = "INSERT INTO {$custom_group_table_name} ($regColName,entity_id) VALUES(%1,%2)";
      $QueryParams = array(
        '1' => array($response['registrantKey'], 'String'),
        '2' => array($pid, 'Integer')
        );
      CRM_Core_DAO::executeQuery($customTableQuery, $QueryParams);
      $webinar_participant_details = array(
        'participant_id' => $pid,
        'registrantKey' => $response['registrantKey'],
      );

      $session = CRM_Core_Session::singleton();
      $session->set('webinar_participant_details',$webinar_participant_details);
    }
  }
}

//DM: Fixing the registrantKey updation into the custom table for offline registrations
function gotowebinar_civicrm_custom( $op, $groupID, $entityID, &$params ){

  $customGroupDetails = civicrm_api3('CustomGroup', 'get', [
    'sequential' => 1,
    'name' => "Webinar_Participant",
  ]);
  $session = CRM_Core_Session::singleton();
  $webinarDetails = $session->get('webinar_participant_details');

  if (($op == 'create' || $op == 'edit')
    && $groupID == $customGroupDetails['id']
    && !empty($webinarDetails)
    && $webinarDetails['participant_id'] == $entityID
  ) {
    $tableName = $customGroupDetails['values'][0]['table_name'];

    $customFieldDetails = civicrm_api3('CustomField', 'get', [
      'sequential' => 1,
      'name' => REGISTRANT_KEY,
      'custom_group_id' => $customGroupDetails['id'],
      ]);
    $regKeyField = $customFieldDetails['values'][0];
    $regColName  = $regKeyField['column_name'];

    foreach ($params as $key => $value) {
      if ($value['custom_field_id'] == $regKeyField['id'] && empty($value['value'])) {
        $sqlParams = array(1 =>  array($webinarDetails['registrantKey'], 'String'), 2 =>  array($entityID, 'Integer'));
        CRM_Core_DAO::executeQuery("UPDATE {$tableName} SET {$regColName} = %1 WHERE entity_id = %2", $sqlParams);
        $session->set('webinar_participant_details', NULL);
      }
    }
  }
}

function gotowebinar_civicrm_buildForm($formName, &$form) {
  if ($formName == 'CRM_Event_Form_ManageEvent_EventInfo' AND ($form->getAction() == CRM_Core_Action::ADD OR $form->getAction() == CRM_Core_Action::UPDATE)) {

     $accessToken = CRM_Gotowebinar_Utils::getItem(CRM_Gotowebinar_Form_Setting::WEBINAR_SETTING_GROUP,
      'access_token', NULL, FALSE
    );
      $organizerKey = CRM_Gotowebinar_Utils::getItem(CRM_Gotowebinar_Form_Setting::WEBINAR_SETTING_GROUP,
      'organizer_key', NULL, FALSE
    );

    if($accessToken && $organizerKey) {
      $upcomingWebinars = CRM_Gotowebinar_Form_Setting::findUpcomingWebinars();
      if(isset($upcomingWebinars['int_err_code']) && $upcomingWebinars['int_err_code'] == 'InvalidToken'){
        $validToken = CRM_Gotowebinar_Utils::refreshAccessToken();
        if($validToken){
          $upcomingWebinars = CRM_Gotowebinar_Form_Setting::findUpcomingWebinars();
        }
      }
      if(isset($upcomingWebinars['int_err_code']) && $upcomingWebinars['int_err_code'] != '') {
        $error['message'] = ts('Unable to fetch the Upcoming Webinars, please check the webinar settings page');
        $form->assign('error_message', $error);
      } else{
        $form->assign('upcomingWebinars', $upcomingWebinars );
      }
    }
  }
}

