<?php
/**
 * Count images for AJAX info
 */

define('PHPWG_ROOT_PATH','./../../../');
define('IN_ADMIN', true);

include_once(PHPWG_ROOT_PATH.'include/common.inc.php');
include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');

is_admin() or die('Hacking attempt!');


if (isset($_POST['filters']))
{
  $filters = array();
  $page['errors'] = array();
  $limit_is_set = false;

  foreach ($_POST['filters'] as $filter)
  {
    if (($filter = smart_check_filter($filter)) != false)
    {
      $filters[] = $filter;
    }
    else
    {
      echo '<span class="filter_error">'. array_pop($page['errors']) .'</span>';
      exit;
    }
  }

  $associated_images = smart_get_pictures($_POST['cat_id'], $filters);
}
else
{
  $associated_images = array();
}

echo '<span class="count_image">'.l10n_dec('%d photo', '%d photos', count($associated_images)).'</span>';

?>