<?php
/**
* ready for sky 
* @package project
* @author Wizard <cheloverts@gmail.com>
* @version 0.1 (wizard, 17:07:42 [Jul 29, 2019])
*/
//
//
class r4s extends module {
/**
* r4s
*
* Module class constructor
*
* @access private
*/
function __construct() {
  $this->name="r4s";
  $this->title="ready for sky";
  $this->module_category="<#LANG_SECTION_DEVICES#>";
  $this->checkInstalled();
}
/**
* saveParams
*
* Saving module parameters
*
* @access public
*/
function saveParams($data=1) {
 $p=array();
 if (IsSet($this->id)) {
  $p["id"]=$this->id;
 }
 if (IsSet($this->view_mode)) {
  $p["view_mode"]=$this->view_mode;
 }
 if (IsSet($this->edit_mode)) {
  $p["edit_mode"]=$this->edit_mode;
 }
 if (IsSet($this->data_source)) {
  $p["data_source"]=$this->data_source;
 }
 if (IsSet($this->tab)) {
  $p["tab"]=$this->tab;
 }
 return parent::saveParams($p);
}
/**
* getParams
*
* Getting module parameters from query string
*
* @access public
*/
function getParams() {
  global $id;
  global $mode;
  global $view_mode;
  global $edit_mode;
  global $data_source;
  global $tab;
  if (isset($id)) {
   $this->id=$id;
  }
  if (isset($mode)) {
   $this->mode=$mode;
  }
  if (isset($view_mode)) {
   $this->view_mode=$view_mode;
  }
  if (isset($edit_mode)) {
   $this->edit_mode=$edit_mode;
  }
  if (isset($data_source)) {
   $this->data_source=$data_source;
  }
  if (isset($tab)) {
   $this->tab=$tab;
  }
}
/**
* Run
*
* Description
*
* @access public
*/
function run() {
 global $session;
  $out=array();
  if ($this->action=='admin') {
   $this->admin($out);
  } else {
   $this->usual($out);
  }
  if (IsSet($this->owner->action)) {
   $out['PARENT_ACTION']=$this->owner->action;
  }
  if (IsSet($this->owner->name)) {
   $out['PARENT_NAME']=$this->owner->name;
  }
  $out['VIEW_MODE']=$this->view_mode;
  $out['EDIT_MODE']=$this->edit_mode;
  $out['MODE']=$this->mode;
  $out['ACTION']=$this->action;
  $out['DATA_SOURCE']=$this->data_source;
  $out['TAB']=$this->tab;
  $this->data=$out;
  $p=new parser(DIR_TEMPLATES.$this->name."/".$this->name.".html", $this->data, $this);
  $this->result=$p->result;
}
/**
* BackEnd
*
* Module backend
*
* @access public
*/
function admin(&$out) {
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='r4s_devices' || $this->data_source=='') {
  if ($this->view_mode=='' || $this->view_mode=='search_r4s_devices') {
   $this->search_r4s_devices($out);
  }
  if ($this->view_mode=='edit_r4s_devices') {
   $this->edit_r4s_devices($out, $this->id);
  }
  if ($this->view_mode=='delete_r4s_devices') {
   $this->delete_r4s_devices($this->id);
   $this->redirect("?data_source=r4s_devices");
  }
 }
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='r4s_values') {
  if ($this->view_mode=='' || $this->view_mode=='search_r4s_values') {
   $this->search_r4s_values($out);
  }
  if ($this->view_mode=='edit_r4s_values') {
   $this->edit_r4s_values($out, $this->id);
  }
 }
}
/**
* FrontEnd
*
* Module frontend
*
* @access public
*/
function usual(&$out) {
 $this->admin($out);
}
/**
* r4s_devices search
*
* @access public
*/
 function search_r4s_devices(&$out) {
  require(DIR_MODULES.$this->name.'/r4s_devices_search.inc.php');
 }
/**
* r4s_devices edit/add
*
* @access public
*/
 function edit_r4s_devices(&$out, $id) {
  require(DIR_MODULES.$this->name.'/r4s_devices_edit.inc.php');
 }
/**
* r4s_devices delete record
*
* @access public
*/
 function delete_r4s_devices($id) {
  $rec=SQLSelectOne("SELECT * FROM r4s_devices WHERE ID='$id'");
  // some action for related tables
  SQLExec("DELETE FROM r4s_devices WHERE ID='".$rec['ID']."'");
 }
/**
* r4s_values search
*
* @access public
*/
 function search_r4s_values(&$out) {
  require(DIR_MODULES.$this->name.'/r4s_values_search.inc.php');
 }
/**
* r4s_values edit/add
*
* @access public
*/
 function edit_r4s_values(&$out, $id) {
  require(DIR_MODULES.$this->name.'/r4s_values_edit.inc.php');
 }
 function propertySetHandle($object, $property, $value) {
   $table='r4s_values';
   $properties=SQLSelect("SELECT * FROM $table WHERE LINKED_OBJECT LIKE '".DBSafe($object)."' AND LINKED_PROPERTY LIKE '".DBSafe($property)."'");
   $total=count($properties);
   if ($total) {
    for($i=0;$i<$total;$i++) {
        $item = $properties[$i];
        #say("$property $value");
        if ( $item['TITLE'] == 'status' ) { # обработка изменения статуса чайника
            if ( $value > 0 ) {
                $this->turnOn($item);
            } else {
                $this->turnOff($item);
            }
        }
     //to-do
    }
   }
 }

 function turnOn($item){
    return $this->run_kettel_command($item['DEVICE_ID'], 'ON');
 }

 function turnOff($item){
    return $this->run_kettel_command($item['DEVICE_ID'], 'OFF');
 }

 function getStatus($item){
    return $this->run_kettel_command($item['DEVICE_ID'], 'GET_MODE');
 }

 function processSubscription($event, $details='') {
  if ($event=='SAY') {
   $level=$details['level'];
   $message=$details['message'];
   //...
  }
 }
 function processCycle() {
  $res=SQLSelect("SELECT * FROM r4s_devices");
  if ($res[0]['ID']) {
    $total=count($res);
    for($i=0;$i<$total;$i++) {
        $device = $res[$i];
        $this->setDeviceInfo($device);
    }
  }
 }

 function setDeviceInfo($device){
    $mac = $this->get_device_property($device['ID'], 'mac');
    $key = $this->get_device_property($device['ID'], 'key');
    $json = $this->run_kettel_command($device['ID'], 'GET_MODE');
    $this->set_device_property($device['ID'], 'message', json_encode($json));
    if ( isset($json['error'] )) {
        debmes($json['error'].": $mac", 'r4s');
        debmes("Невозможно обновить статус устройства: $mac", 'r4s');
        echo "Невозможно обновить статус устройства: $mac"."\n";
    } else {
        $status = $json['result']['status'];
        if ( $status == '00' ) {
            $status = 0;
        } else if ( $status == '02' ) {
            $status = 1;
        }
        $temp   = $json['result']['temp'];
        $mode   = $json['result']['mode'];
        if ( $mode == '00' ) {
            $mode_en = 'boiling'; # кипячение
            $mode_name = 'кипячение';
        } else if ( $mode == '01' ) {
            $mode_en = 'heat'; # подогрев
            $mode_name = 'подогрев';
        } else if ( $mode == '03' ) {
            $mode_en = 'night_light'; # режим подсветки
            $mode_name = 'подсветка';
        } else {
            $mode_en = 'unknown'; # режим подсветки
            $mode_name = 'неизвестный';
        }
        $this->setUpdateDevice($device, 'status', $status);
        $this->setUpdateDevice($device, 'current_temperature', $temp);
        $this->setUpdateDevice($device, 'mode', $mode);
        $this->setUpdateDevice($device, 'mode_en', $mode_en);
        $this->setUpdateDevice($device, 'mode_name', $mode_name);
    }
 }

 function setUpdateDevice($device, $property, $value) {
        $values=SQLSelectOne("SELECT * FROM r4s_values WHERE DEVICE_ID='".$device['ID']."' and TITLE='$property'");
        $linked_object = $values['LINKED_OBJECT'];
        if ( isset($linked_object) ) {
            sg("$linked_object.$property", $value);
        }
        echo "обновляется $property устройства: $mac значение $value"."\n";
        $this->set_device_property($device['ID'], $property, $value);
        debmes("обновляется $property устройства: $mac", 'r4s');
 }

 function get_device_property($id, $property){
    $values=SQLSelectOne("SELECT * FROM r4s_values WHERE DEVICE_ID='$id' and TITLE='$property'");
    $result = $values['VALUE'];
    return $result;
 }

 function set_device_property($id, $property, $value){
    $values=SQLSelectOne("SELECT * FROM r4s_values WHERE DEVICE_ID='$id' and TITLE='$property'");
    if ( isset($values) && isset($values['ID']) ) {
        $values['VALUE'] = "$value";
        SQLUpdate('r4s_values', $values);
        echo "update property $property => $value \n";
    } else {
        $values = array(
            'TITLE' => $property,
            'DEVICE_ID' => $id,
            'VALUE' => "$value",
        );
        echo "insert property $property => $value\n";
        SQLInsert('r4s_values', $values);
    }
    return $values;
 }

 function run_kettel_command($id, $command){
    $mac = $this->get_device_property($id, 'mac');
    $key = $this->get_device_property($id, 'key');
    $result = exec("python ".DIR_MODULES."/r4s/r4s.py --mac='$mac' --command '$command' --key='$key'");
    $json = json_decode($result, true);
    return $json;
 }
/**
* Install
*
* Module installation routine
*
* @access private
*/
 function install($data='') {
  subscribeToEvent($this->name, 'SAY');
  parent::install();
 }
/**
* Uninstall
*
* Module uninstall routine
*
* @access public
*/
 function uninstall() {
  unsubscribeFromEvent('SAY');
  SQLExec('DROP TABLE IF EXISTS r4s_devices');
  SQLExec('DROP TABLE IF EXISTS r4s_values');
  parent::uninstall();
 }
/**
* dbInstall
*
* Database installation routine
*
* @access private
*/
 function dbInstall($data) {
/*
r4s_devices - 
r4s_values - 
*/
  $data = <<<EOD
 r4s_devices: ID int(10) unsigned NOT NULL auto_increment
 r4s_devices: TITLE varchar(100) NOT NULL DEFAULT ''
 r4s_devices: DEVICE_NAME varchar(255) NOT NULL DEFAULT ''
 r4s_devices: DEVICE_STATUS varchar(255) NOT NULL DEFAULT ''
 r4s_devices: LINKED_OBJECT varchar(100) NOT NULL DEFAULT ''
 r4s_devices: LINKED_PROPERTY varchar(100) NOT NULL DEFAULT ''
 r4s_devices: LINKED_METHOD varchar(100) NOT NULL DEFAULT ''
 r4s_devices: UPDATED datetime
 r4s_values: ID int(10) unsigned NOT NULL auto_increment
 r4s_values: TITLE varchar(100) NOT NULL DEFAULT ''
 r4s_values: VALUE varchar(255) NOT NULL DEFAULT ''
 r4s_values: DEVICE_ID int(10) NOT NULL DEFAULT '0'
 r4s_values: LINKED_OBJECT varchar(100) NOT NULL DEFAULT ''
 r4s_values: LINKED_PROPERTY varchar(100) NOT NULL DEFAULT ''
 r4s_values: LINKED_METHOD varchar(100) NOT NULL DEFAULT ''
 r4s_values: UPDATED datetime
EOD;
  parent::dbInstall($data);
 }
// --------------------------------------------------------------------
}
/*
*
* TW9kdWxlIGNyZWF0ZWQgSnVsIDI5LCAyMDE5IHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
