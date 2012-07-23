<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

// Enregistrement de la configuration
if (isset($_POST['submit']))
{
  $conf['SmartAlbums']['update_on_upload'] = isset($_POST['update_on_upload']);
  $conf['SmartAlbums']['smart_is_forbidden'] = isset($_POST['smart_is_forbidden']);
  
  conf_update_param('SmartAlbums', serialize($conf['SmartAlbums']));
  array_push($page['infos'], l10n('Information data registered in database'));
}

$template->assign($conf['SmartAlbums']);

$template->set_filename('SmartAlbums_content', dirname(__FILE__).'/template/config.tpl');

?>