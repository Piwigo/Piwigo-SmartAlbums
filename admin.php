<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

global $conf, $template;
load_language('plugin.lang', SMART_PATH);
if (!is_array($conf['SmartAlbums'])) $conf['SmartAlbums'] = unserialize($conf['SmartAlbums']);


// Enregistrement de la configuration
if (isset($_POST['submit']))
{
	$conf['SmartAlbums'] = array(
    'update_on_upload' => $_POST['update_on_upload'], 
    );    
	
  conf_update_param('SmartAlbums', serialize($conf['SmartAlbums']));
	array_push($page['infos'], l10n('Information data registered in database'));
}

$template->assign(array(
  'SMART_PATH' => SMART_PATH,
	'update_on_upload' => $conf['SmartAlbums']['update_on_upload'],
));
	
$template->set_filename('SmartAlbums_conf', dirname(__FILE__).'/template/admin.tpl');
$template->assign_var_from_handle('ADMIN_CONTENT', 'SmartAlbums_conf');

?>