<?php
defined('SMART_PATH') or die('Hacking attempt!');

global $conf, $template, $page;

$page['tab'] = (isset($_GET['tab'])) ? $_GET['tab'] : 'cat_list';

if ($page['tab'] == 'album')
{
  include(SMART_PATH . 'admin/album.php');
}
else
{
  include_once(PHPWG_ROOT_PATH.'admin/include/tabsheet.class.php');
  $tabsheet = new tabsheet();
  $tabsheet->add('cat_list', l10n('All SmartAlbums'), SMART_ADMIN.'-cat_list');
  $tabsheet->add('config', l10n('Configuration'), SMART_ADMIN.'-config');
  $tabsheet->select($page['tab']);
  $tabsheet->assign();

  include(SMART_PATH . 'admin/'.$page['tab'].'.php');
}

$template->assign(array(
  'SMART_PATH' => SMART_PATH,
  'SMART_ABS_PATH' => realpath(SMART_PATH) . '/',
  ));

$template->assign_var_from_handle('ADMIN_CONTENT', 'SmartAlbums_content');
