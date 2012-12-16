<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

include_once(PHPWG_PLUGINS_PATH . 'SmartAlbums/include/install.inc.php');

function plugin_install() 
{
  smart_albums_install();
  define('smart_albums_installed', true);
}

function plugin_activate()
{ 
  if (!defined('smart_albums_installed'))
  {
    smart_albums_install();
  }
}

function plugin_uninstall() 
{
  global $prefixeTable;
  
  pwg_query('DROP TABLE `' . $prefixeTable . 'category_filters`;');
  pwg_query('ALTER TABLE `' . IMAGE_CATEGORY_TABLE . '` DROP `smart`;');
  pwg_query('ALTER TABLE `' . CATEGORIES_TABLE . '` DROP `smart_update`;');
  pwg_query('DELETE FROM `' . CONFIG_TABLE . '` WHERE param = \'SmartAlbums\' LIMIT 1;');
}

?>