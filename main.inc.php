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

if (script_basename() == 'index')
{
  add_event_handler('loc_end_section_init', 'smart_init_page_items');
  include_once(SMART_PATH.'include/init_page_items.php');
}
else if (script_basename() == 'admin')
{
  load_language('plugin.lang', SMART_PATH);
  
  add_event_handler('loc_begin_cat_modify', 'smart_cat_modify'); 
  include_once(SMART_PATH.'include/init_cat_modify.php');
  
  add_event_handler('loc_begin_cat_list', 'smart_cat_list');
  include_once(SMART_PATH.'include/init_cat_list.php');
}

?>