<?php
/**
 * Count images for AJAX info
 */

define('PHPWG_ROOT_PATH','./../../../');
define('IN_ADMIN', true);
include_once(PHPWG_ROOT_PATH.'include/common.inc.php');
include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');
include_once(SMART_PATH.'include/functions.inc.php');

$filters = array();
$limit_is_set = false;
foreach ($_POST['filters'] as $filter)
{
  if (($filter = smart_check_filter($filter)) != false)
  {
    array_push($filters, $filter);
  }
}

$associated_images = smart_get_pictures($_POST['cat_id'], $filters);
echo l10n_dec('%d photo', '%d photos', count($associated_images));

?>