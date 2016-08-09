<?php
/**
* ESP8266 (Wifi-IoT) 
* @package project
* @author Wizard <sergejey@gmail.com>
* @copyright http://majordomo.smartliving.ru/ (c)
* @version 0.1 (wizard, 14:06:33 [Jun 20, 2016])
*/
//
//
class esp8266_wifiot extends module {
/**
* esp8266_wifiot
*
* Module class constructor
*
* @access private
*/
function esp8266_wifiot() {
  $this->name="esp8266_wifiot";
  $this->title="ESP8266 (Wifi-IoT)";
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
function saveParams($data=0) {
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
 if ($this->data_source=='espdevices' || $this->data_source=='') {
  if ($this->view_mode=='' || $this->view_mode=='search_espdevices') {
   $this->search_espdevices($out);
  }
  if ($this->view_mode=='edit_espdevices') {
   $this->edit_espdevices($out, $this->id);
  }
  if ($this->view_mode=='delete_espdevices') {
   $this->delete_espdevices($this->id);
   $this->redirect("?data_source=espdevices");
  }
 }
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='espdevices_data') {
  if ($this->view_mode=='' || $this->view_mode=='search_espdevices_data') {
   $this->search_espdevices_data($out);
  }
  if ($this->view_mode=='edit_espdevices_data') {
   $this->edit_espdevices_data($out, $this->id);
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
 //processing request
 //print_r($_GET);
 $idesp=$_GET['idesp'];
 $mac=$_GET['mac'];
 $ip=$_GET['wanip'];

 if (!$idesp) {
  return 0;
 }

 $device=SQLSelectOne("SELECT * FROM espdevices WHERE IDESP LIKE '".DBSafe($idesp)."'");
 if (!$device['ID']) {
  $device['IDESP']=$idesp;
  $device['TITLE']=$device['IDESP'];
  $device['ID']=SQLInsert('espdevices', $device);
 }
 $device['IP']=$ip;
 $device['MAC']=$mac;
 $device['UPDATED']=date('Y-m-d H:i:s');
 SQLUpdate('espdevices', $device);


 foreach($_GET as $k=>$v) {

  if ($k=='script' || $k=='idesp' || $v=='') {
   continue;
  }
  $prop=SQLSelectOne("SELECT * FROM espdevices_data WHERE TITLE LIKE '".DBSafe($k)."' AND DEVICE_ID='".$device['ID']."'");
  //$old_value=$prop['VALUE'];
  if ($v!=$prop['VALUE']) {
   $prop['UPDATED']=date('Y-m-d H:i:s');
  }
  $prop['VALUE']=$v;
  if (!$prop['ID']) {
   $prop['TITLE']=$k;
   $prop['DEVICE_ID']=$device['ID'];
   SQLInsert('espdevices_data', $prop);
  } else {
   SQLUpdate('espdevices_data', $prop);
  }

  if ($prop['LINKED_OBJECT'] && $prop['LINKED_PROPERTY']) {
   setGlobal($prop['LINKED_OBJECT'].'.'.$prop['LINKED_PROPERTY'], $prop['VALUE'], array($this->name=>'0'));
  }
  if ($prop['LINKED_OBJECT'] && $prop['LINKED_METHOD']) {
   $params=array();
   $params['TITLE']=$prop['TITLE'];
   $params['VALUE']=$prop['VALUE'];
   $params['value']=$prop['VALUE'];
   callMethod($prop['LINKED_OBJECT'].'.'.$prop['LINKED_METHOD'], $params);
  }
 }


}
/**
* espdevices search
*
* @access public
*/
 function search_espdevices(&$out) {
  require(DIR_MODULES.$this->name.'/espdevices_search.inc.php');
 }
/**
* espdevices edit/add
*
* @access public
*/
 function edit_espdevices(&$out, $id) {
  require(DIR_MODULES.$this->name.'/espdevices_edit.inc.php');
 }
/**
* espdevices delete record
*
* @access public
*/
 function delete_espdevices($id) {
  $rec=SQLSelectOne("SELECT * FROM espdevices WHERE ID='$id'");
  // some action for related tables
  SQLExec("DELETE FROM espdevices WHERE ID='".$rec['ID']."'");
 }
/**
* espdevices_data search
*
* @access public
*/
 function search_espdevices_data(&$out) {
  require(DIR_MODULES.$this->name.'/espdevices_data_search.inc.php');
 }
/**
* espdevices_data edit/add
*
* @access public
*/
 function edit_espdevices_data(&$out, $id) {
  require(DIR_MODULES.$this->name.'/espdevices_data_edit.inc.php');
 }
 function propertySetHandle($object, $property, $value) {
   $table='espdevices_data';
   $properties=SQLSelect("SELECT ID FROM $table WHERE LINKED_OBJECT LIKE '".DBSafe($object)."' AND LINKED_PROPERTY LIKE '".DBSafe($property)."'");
   $total=count($properties);
   if ($total) {
    for($i=0;$i<$total;$i++) {
     //to-do
    }
   }
 }
/**
* Install
*
* Module installation routine
*
* @access private
*/
 function install($data='') {
  $match_pattern='esp8266_wifiot';
  $code="// esp8266_wifiot begin";
  $code.="\n".'include_once(DIR_MODULES."esp8266_wifiot/esp8266_wifiot.class.php");';
  $code.="\n".'$wifiot=new esp8266_wifiot();';
  $code.="\n".'$wifiot->usual($out);';
  $code.="\n// esp8266_wifiot end\n";
  $script=SQLSelectOne("SELECT * FROM scripts WHERE TITLE LIKE 'espdata'");
  if (!$script['ID']) {
   $script['TITLE']='espdata';
   $script['DESCRIPTION']='ESP data processing';
   $script['CODE']=$code;
   SQLInsert('scripts', $script);
  } elseif (!preg_match('/'.$match_pattern.'/is', $script['CODE'])) {
   $script['CODE']=$code."\n".$script['CODE'];
   SQLUpdate('scripts', $script);
  }
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
  SQLExec('DROP TABLE IF EXISTS espdevices');
  SQLExec('DROP TABLE IF EXISTS espdevices_data');
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
espdevices - 
espdevices_data - 
*/
  $data = <<<EOD
 espdevices: ID int(10) unsigned NOT NULL auto_increment
 espdevices: TITLE varchar(100) NOT NULL DEFAULT ''
 espdevices: IDESP varchar(100) NOT NULL DEFAULT ''
 espdevices: IP varchar(255) NOT NULL DEFAULT ''
 espdevices: MAC varchar(255) NOT NULL DEFAULT ''
 espdevices: UPDATED datetime
 espdevices_data: ID int(10) unsigned NOT NULL auto_increment
 espdevices_data: TITLE varchar(100) NOT NULL DEFAULT ''
 espdevices_data: VALUE varchar(255) NOT NULL DEFAULT ''
 espdevices_data: DEVICE_ID int(10) NOT NULL DEFAULT '0'
 espdevices_data: LINKED_OBJECT varchar(100) NOT NULL DEFAULT ''
 espdevices_data: LINKED_PROPERTY varchar(100) NOT NULL DEFAULT ''
 espdevices_data: LINKED_METHOD varchar(100) NOT NULL DEFAULT ''
 espdevices_data: UPDATED datetime
EOD;
  parent::dbInstall($data);
 }
// --------------------------------------------------------------------
}
/*
*
* TW9kdWxlIGNyZWF0ZWQgSnVuIDIwLCAyMDE2IHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
