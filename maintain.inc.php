<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

function plugin_install() 
{
	global $prefixeTable;

  /* create table to store filters */
	pwg_query(
'CREATE TABLE IF NOT EXISTS `' . $prefixeTable . 'category_filters` (
  `category_id` smallint(5) unsigned NOT NULL,
  `type` varchar(16) NOT NULL,
  `cond` varchar(16) NULL,
  `value` text
) ENGINE=MyISAM DEFAULT CHARSET=utf8
;');
  
  /* add a collumn to image_category_table */
  pwg_query('ALTER TABLE `' . IMAGE_CATEGORY_TABLE . '` ADD `smart` ENUM(\'true\', \'false\') NOT NULL DEFAULT \'false\';');
      
  /* config parameter */
  pwg_query('
INSERT INTO `' . CONFIG_TABLE . '`
  VALUES (
    \'SmartAlbums\', 
    \''.serialize(array(
        'update_on_upload' => false,
        'show_list_messages' => true,
        )
      ).'\',
    \'Configuration for SmartAlbums plugin\'
  )
;');

}

function plugin_activate()
{ 
  global $conf;
  
  if (!isset($conf['SmartAlbums']))
  {
    pwg_query('
INSERT INTO `' . CONFIG_TABLE . '`
  VALUES (
    \'SmartAlbums\', 
    \''.serialize(array(
        'update_on_upload' => false,
        'show_list_messages' => true,
        )
      ).'\',
    \'Configuration for SmartAlbums plugin\'
  )
;');
  }
}

function plugin_uninstall() 
{
	global $prefixeTable;
  
  pwg_query('DROP TABLE `' . $prefixeTable . 'category_filters`;');
  pwg_query('ALTER TABLE `' . IMAGE_CATEGORY_TABLE . '` DROP `smart`;');
  pwg_query('DELETE FROM `' . CONFIG_TABLE . '` WHERE param = \'SmartAlbums\';');
}
?>