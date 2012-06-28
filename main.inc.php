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

include_once(SMART_PATH.'include/functions.inc.php');

if (script_basename() == 'index')
{
  add_event_handler('loc_end_section_init', 'smart_init_page_items');
  include_once(SMART_PATH.'include/page_items.php');
}
else if (script_basename() == 'admin')
{
  add_event_handler('init', 'smart_init');
}

function smart_init()
{
  global $conf;
  
  load_language('plugin.lang', SMART_PATH);
  $conf['SmartAlbums'] = unserialize($conf['SmartAlbums']);
    
  include_once(SMART_PATH.'include/cat_list.php');
  
  add_event_handler('loc_begin_cat_list', 'smart_cat_list');
  add_event_handler('loc_begin_admin_page', 'smart_add_admin_album_tab');
  add_event_handler('get_admin_plugin_menu_links', 'smart_admin_menu');
}

function smart_admin_menu($menu) 
{
  array_push($menu, array(
      'NAME' => 'SmartAlbums',
      'URL' => SMART_ADMIN,
    ));
  return $menu;
}

function smart_add_admin_album_tab()
{
  global $page, $template;
  if ($page['page'] != 'album') return;
  
  $template->assign('SMART_CAT_ID', $_GET['cat_id']);
  $template->set_prefilter('tabsheet', 'smart_add_admin_album_tab_prefilter');
}
function smart_add_admin_album_tab_prefilter($content)
{
  $search = '{/foreach}';
  $add = '
<li class="{if false}selected_tab{else}normal_tab{/if}">
  <a href="'.SMART_ADMIN.'-album&amp;cat_id={$SMART_CAT_ID}"><span>SmartAlbum</span></a>
</li>';
  return str_replace($search, $search.$add, $content);
}
?>