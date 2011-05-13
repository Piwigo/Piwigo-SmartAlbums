<?php
define('PHPWG_ROOT_PATH','./../../../');
define('IN_ADMIN', true);
include_once(PHPWG_ROOT_PATH.'include/common.inc.php');
include_once(SMART_PATH.'include/functions.inc.php');

$error = false;
foreach ($_POST['filters'] as $filter)
{
  if ($filter['type'] == 'tags')
  {
    $filter['value'] = str_replace(' ', null, $filter['value']);
  }
  else if ($filter['type'] == 'date')
  {
    if (!preg_match('#([0-9]{4})-([0-9]{2})-([0-9]{2})#', $filter['value']))
    {
      $error = true;
    }
  }
  else if ($filter['type'] == 'limit')
  {
    if (!preg_match('#([0-9]{1,})#', $filter['value']))
    {
      $error = true;
    }
    else if (isset($limit_is_set))
    {
      $error = true;
    }
    else
    {
      $limit_is_set = true;
    }
  }
}

if ($error == false)
{
  $associated_images = SmartAlbums_get_pictures($_POST['cat_id'], $_POST['filters']);
  echo l10n_dec('%d photo', '%d photos', count($associated_images));
}
else
{
  echo 'error';
}
?>