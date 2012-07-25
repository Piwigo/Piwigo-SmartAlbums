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

define('SMART_PATH', PHPWG_PLUGINS_PATH . basename(dirname(__FILE__)) . '/');
define('CATEGORY_FILTERS_TABLE', $prefixeTable . 'category_filters');
define('SMART_ADMIN', get_root_url() . 'admin.php?page=plugin-' . basename(dirname(__FILE__)));

add_event_handler('invalidate_user_cache', 'smart_make_all_associations');
add_event_handler('init', 'smart_init');

include_once(SMART_PATH.'include/functions.inc.php');

function smart_init()
{
  global $conf;
  
  load_language('plugin.lang', SMART_PATH);
  $conf['SmartAlbums'] = unserialize($conf['SmartAlbums']);
  
  if ( script_basename() == 'index' and $conf['SmartAlbums']['smart_is_forbidden'] )
  {
    add_event_handler('loc_end_section_init', 'smart_init_page_items');
    include_once(SMART_PATH.'include/page_items.php');
  }
  else if (script_basename() == 'admin')
  {
    include_once(SMART_PATH.'include/cat_list.php');
    
    add_event_handler('loc_begin_cat_list', 'smart_cat_list');
    add_event_handler('tabsheet_before_select','smart_tab', EVENT_HANDLER_PRIORITY_NEUTRAL, 2); 
    add_event_handler('get_admin_plugin_menu_links', 'smart_admin_menu');
    add_event_handler('delete_categories', 'smart_delete_categories');
  }
}

function smart_tab($sheets, $id)
{
  if ($id == 'album')
  {
    $sheets['smartalbum'] = array(
      'caption' => 'SmartAlbum',
      'url' => SMART_ADMIN.'-album&amp;cat_id='.$_GET['cat_id'],
      );
  }
  
  return $sheets;
}

function smart_admin_menu($menu) 
{
  array_push($menu, array(
      'NAME' => 'SmartAlbums',
      'URL' => SMART_ADMIN,
    ));
  return $menu;
}

?>