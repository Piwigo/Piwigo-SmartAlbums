<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

global $conf, $template, $page;

load_language('plugin.lang', SMART_PATH);
if (!is_array($conf['SmartAlbums'])) $conf['SmartAlbums'] = unserialize($conf['SmartAlbums']);

include_once(PHPWG_ROOT_PATH.'admin/include/tabsheet.class.php');
$page['tab'] = (isset($_GET['tab'])) ? $_GET['tab'] : $page['tab'] = 'albums';
  
$tabsheet = new tabsheet();
$tabsheet->add('albums', l10n('All SmartAlbums'), SMART_ADMIN.'-albums');
$tabsheet->add('config', l10n('Configuration'),   SMART_ADMIN.'-config');
$tabsheet->select($page['tab']);
$tabsheet->assign();

$template->assign(array(
  'SMART_PATH' => SMART_PATH,
));

include(SMART_PATH.'admin/'.$page['tab'].'.inc.php');
$template->set_filename('SmartAlbums_content', dirname(__FILE__).'/admin/template/'.$page['tab'].'.tpl');
$template->assign_var_from_handle('ADMIN_CONTENT', 'SmartAlbums_content');

?>