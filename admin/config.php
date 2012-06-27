<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

// Enregistrement de la configuration
if (isset($_POST['submit']))
{
  $conf['SmartAlbums']['update_on_upload'] = isset($_POST['update_on_upload']);
  
  conf_update_param('SmartAlbums', serialize($conf['SmartAlbums']));
  array_push($page['infos'], l10n('Information data registered in database'));
}

$template->assign(array(
  'update_on_upload' => $conf['SmartAlbums']['update_on_upload'],
));

$template->set_filename('SmartAlbums_content', dirname(__FILE__).'/template/config.tpl');

?>