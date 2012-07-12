<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

global $prefixeTable;
define('smart_table', $prefixeTable . 'category_filters');

define('smart_default_config', serialize(array(
    'update_on_upload' => false,
    'show_list_messages' => true,
    )));

function plugin_install() 
{
  /* create table to store filters */
	pwg_query(
'CREATE TABLE IF NOT EXISTS `' . smart_table . '` (
  `category_id` smallint(5) unsigned NOT NULL,
  `type` varchar(16) NOT NULL,
  `cond` varchar(16) NULL,
  `value` text
) ENGINE=MyISAM DEFAULT CHARSET=utf8
;');
  
  /* add a collumn to image_category_table */
  pwg_query('ALTER TABLE `' . IMAGE_CATEGORY_TABLE . '` ADD `smart` ENUM(\'true\', \'false\') NOT NULL DEFAULT \'false\';');
      
  /* config parameter */
  conf_update_param('SmartAlbums', smart_default_config);
}

function plugin_activate()
{ 
  global $conf;
  
  if (!isset($conf['SmartAlbums']))
  {
    conf_update_param('SmartAlbums', smart_default_config);
  }
  
  /* some filters renamed in 1.2 */
  $name_changes = array(
    'the' => 'the_post',
    'before' => 'before_post',
    'after' => 'after_post',
    'the_crea' => 'the_taken',
    'before_crea' => 'before_taken',
    'after_crea' => 'after_taken',
    );
  foreach ($name_changes as $old => $new)
  {
    pwg_query('UPDATE ' . smart_table . ' SET cond = "'.$new.'" WHERE cond = "'.$old.'";');
  }
}

function plugin_uninstall() 
{  
  pwg_query('DROP TABLE `' . smart_table . '`;');
  pwg_query('ALTER TABLE `' . IMAGE_CATEGORY_TABLE . '` DROP `smart`;');
  pwg_query('DELETE FROM `' . CONFIG_TABLE . '` WHERE param = \'SmartAlbums\' LIMIT 1;');
}

?>