<?php
defined('SMART_PATH') or die('Hacking attempt!');

if (isset($_POST['submit']))
{
  if ($_POST['update_timeout'] == 0 or !preg_match('#^[0-9.]+$#', $_POST['update_timeout']))
  {
    $page['errors'][] = l10n('Invalid number of days');
    $_POST['update_timeout'] = $conf['SmartAlbums']['update_timeout'];
  }

  $conf['SmartAlbums'] = array(
    'show_list_messages' => $conf['SmartAlbums']['show_list_messages'],
    'last_update' =>        $conf['SmartAlbums']['last_update'],
    'update_on_upload' =>   isset($_POST['update_on_upload']),
    'update_on_date' =>     isset($_POST['update_on_date']),
    'update_timeout' =>     $_POST['update_timeout'],
    'smart_is_forbidden' => isset($_POST['smart_is_forbidden']),
    );

  conf_update_param('SmartAlbums', $conf['SmartAlbums']);
  $page['infos'][] = l10n('Information data registered in database');
}

$template->assign($conf['SmartAlbums']);

$template->set_filename('SmartAlbums_content', realpath(SMART_PATH . 'admin/template/config.tpl'));
