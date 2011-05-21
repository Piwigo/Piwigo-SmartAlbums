<?php
/*
Plugin Name: SmartAlbums
Version: auto
Description: Easily create dynamic albums with tags, date and other criteria
Plugin URI: http://piwigo.org/ext/extension_view.php?eid=544
Author: Mistic
Author URI: http://www.strangeplanet.fr
*/

if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');
global $prefixeTable;

define('SMART_DIR', basename(dirname(__FILE__)));
define('SMART_PATH', PHPWG_PLUGINS_PATH.SMART_DIR.'/');
define('CATEGORY_FILTERS_TABLE', $prefixeTable.'category_filters');

if (script_basename() == 'admin')
{
  add_event_handler('loc_begin_cat_modify', 'smart_init_cat_modify'); 
  function smart_init_cat_modify()
  {
    include_once(SMART_PATH.'init_cat_modify.php');
    smart_cat_modify();
  }
  
  add_event_handler('loc_begin_cat_list', 'smart_init_cat_list');
  function smart_init_cat_list()
  {
    include_once(SMART_PATH.'init_cat_list.php');
    smart_cat_list();
  }
}


?>