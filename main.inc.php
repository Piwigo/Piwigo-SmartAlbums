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

define('SMART_PATH',    PHPWG_PLUGINS_PATH . 'SmartAlbums/');
define('CATEGORY_FILTERS_TABLE', $prefixeTable . 'category_filters');
define('SMART_ADMIN',   get_root_url() . 'admin.php?page=plugin-SmartAlbums');
define('SMART_VERSION', '2.0.3');


add_event_handler('invalidate_user_cache', 'smart_make_all_associations');
add_event_handler('init', 'smart_init');

if (defined('IN_ADMIN'))
{
  include_once(SMART_PATH.'include/cat_list.php');
  add_event_handler('loc_begin_cat_list', 'smart_cat_list');
  add_event_handler('tabsheet_before_select','smart_tab', EVENT_HANDLER_PRIORITY_NEUTRAL, 2); 
  add_event_handler('get_admin_plugin_menu_links', 'smart_admin_menu');
  add_event_handler('delete_categories', 'smart_delete_categories');
}

include_once(SMART_PATH.'include/functions.inc.php');


/**
 * update plugin & unserialize conf & load language
 */
function smart_init()
{
  global $conf, $pwg_loaded_plugins;
  
  if (
    $pwg_loaded_plugins['SmartAlbums']['version'] == 'auto' or
    version_compare($pwg_loaded_plugins['SmartAlbums']['version'], SMART_VERSION, '<')
  )
  {
    include_once(SMART_PATH . 'include/install.inc.php');
    smart_albums_install();
    
    if ($pwg_loaded_plugins['SmartAlbums']['version'] != 'auto')
    {
      $query = '
UPDATE '. PLUGINS_TABLE .'
SET version = "'. SMART_VERSION .'"
WHERE id = "SmartAlbums"';
      pwg_query($query);
      
      $pwg_loaded_plugins['SmartAlbums']['version'] = SMART_VERSION;
      
      if (defined('IN_ADMIN'))
      {
        $_SESSION['page_infos'][] = 'Smart Albums updated to version '. SMART_VERSION;
      }
    }
  }
  
  if (defined('IN_ADMIN'))
  {
    load_language('plugin.lang', SMART_PATH);
  }
  $conf['SmartAlbums'] = unserialize($conf['SmartAlbums']);
  
  if ( script_basename() == 'index' and $conf['SmartAlbums']['smart_is_forbidden'] )
  {
    add_event_handler('loc_end_section_init', 'smart_init_page_items');
    include_once(SMART_PATH.'include/page_items.php');
  }
}

/**
 * new tab on album properties page
 */
function smart_tab($sheets, $id)
{
  if ($id != 'album') return $sheets;
  
  global $category;
  
  if ($category['dir'] == null)
  {
    $sheets['smartalbum'] = array(
      'caption' => 'SmartAlbum',
      'url' => SMART_ADMIN.'-album&amp;cat_id='.$_GET['cat_id'],
      );
  }
  
  return $sheets;
}


/**
 * admin plugins menu link
 */
function smart_admin_menu($menu) 
{
  array_push($menu, array(
      'NAME' => 'SmartAlbums',
      'URL' => SMART_ADMIN,
    ));
  return $menu;
}

?>