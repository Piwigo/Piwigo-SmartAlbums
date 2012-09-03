<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

function smart_albums_install() 
{
  global $conf, $prefixeTable;
  
  // configuration
  if (!isset($conf['SmartAlbums']))
  {
    $smart_default_config = serialize(array(
      'update_on_upload' => false,
      'show_list_messages' => true,
      'smart_is_forbidden' => true,
      ));
    
    conf_update_param('SmartAlbums', $smart_default_config);
    $conf['SmartAlbums'] = $smart_default_config;
  }
  else
  {
    $new_conf = unserialize($conf['SmartAlbums']);
    // new param in 2.0.2
    if (!isset($new_conf['smart_is_forbidden']))
    {
      $new_conf['smart_is_forbidden'] = true;
      conf_update_param('SmartAlbums', serialize($new_conf));
      $conf['SmartAlbums'] = serialize($new_conf);
    }
  }
  
  // new table
	pwg_query(
'CREATE TABLE IF NOT EXISTS `' . $prefixeTable . 'category_filters` (
  `category_id` smallint(5) unsigned NOT NULL,
  `type` varchar(16) NOT NULL,
  `cond` varchar(16) NULL,
  `value` text
) ENGINE=MyISAM DEFAULT CHARSET=utf8
;');
  
  // new column on image category table
  $result = pwg_query('SHOW COLUMNS FROM `' . IMAGE_CATEGORY_TABLE . '` LIKE "smart";');
  if (!pwg_db_num_rows($result))
  {      
    pwg_query('ALTER TABLE `' . IMAGE_CATEGORY_TABLE . '` ADD `smart` ENUM(\'true\', \'false\') NOT NULL DEFAULT \'false\';');
  }
  
  // date filters renamed in 2.0
  $query = '
SELECT category_id
  FROM `' . $prefixeTable . 'category_filters`
  WHERE 
    type = "date" AND
    cond IN ("the","before","after","the_crea","before_crea","after_crea")
;';

  if (pwg_db_num_rows(pwg_query($query)))
  {
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
      pwg_query('UPDATE `' . $prefixeTable . 'category_filters` SET cond = "'.$new.'" WHERE cond = "'.$old.'";');
    }
  }
}

?>