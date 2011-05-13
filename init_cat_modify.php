<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

function smart_cat_modify()
{
  global $template;
  include_once(SMART_PATH.'include/functions.inc.php');
  $cat_id = $_GET['cat_id'];
  
  if (isset($_POST['submitFilters']))
  {
    // test if it was a Smart Album
    $result = pwg_query("SELECT DISTINCT category_id FROM ".CATEGORY_FILTERS_TABLE." WHERE category_id = ".$cat_id.";");
    $_was_smart = pwg_db_num_rows($result);
    
    /* this album is no longer a SmartAlbum */
    if ($_was_smart AND !isset($_POST['is_smart']))
    {
      pwg_query("DELETE FROM ".IMAGE_CATEGORY_TABLE." WHERE category_id = ".$cat_id." AND smart = true;");
      pwg_query("DELETE FROM ".CATEGORY_FILTERS_TABLE." WHERE category_id = ".$cat_id.";");
    }
    /* no filter selected */
    else if (isset($_POST['is_smart']) AND !isset($_POST['filters']))
    {
      array_push($page['errors'], l10n('No filter selected'));
    }
    /* everything is fine */
    else if (isset($_POST['is_smart']) AND count($_POST['filters']) > 0)
    {
      pwg_query("DELETE FROM ".CATEGORY_FILTERS_TABLE." WHERE category_id = ".$cat_id.";");

      foreach ($_POST['filters'] as $filter)
      {
        $error = false;
        if ($filter['type'] == 'tags')
        {
          $filter['value'] = str_replace(' ', null, $filter['value']);
        }
        else if ($filter['type'] == 'date')
        {
          if (!preg_match('#([0-9]{4})-([0-9]{2})-([0-9]{2})#', $filter['value']))
          {
            $error = true;
            array_push($page['errors'], l10n('Date string is malformed'));
          }
        }
        else if ($filter['type'] == 'limit')
        {
          if (!preg_match('#([0-9]{1,})#', $filter['value']))
          {
            $error = true;
            array_push($page['errors'], l10n('Limit must be an integer'));
          }
          else if (isset($limit_is_set))
          {
            $error = true;
            array_push($page['errors'], l10n('You can\'t use more than one limit'));
          }
          else
          {
            $limit_is_set = true;
          }
        }
        
        if ($error == false)
        {
          pwg_query("INSERT INTO ".CATEGORY_FILTERS_TABLE."
            VALUES(".$cat_id.", '".$filter['type']."', '".$filter['cond']."', '".$filter['value']."');");
        }
      }
      
      $associated_images = SmartAlbums_make_associations($cat_id);
      $template->assign('IMAGE_COUNT', l10n_dec('%d photo', '%d photos', count($associated_images)));
      
      set_random_representant(array($cat_id));
      invalidate_user_cache(true);
    }
  }
      
  /* select options */
  $template->assign('options', array(
    'tags' => array(
      'all' => l10n('All these tags'),
      'one' => l10n('One of these tags'),
      'none' => l10n('None of these tags'),
      'only' => l10n('Only these tags'),
      ),
    'date' => array(
      'the' => l10n('Added the'),
      'before' => l10n('Added before the'),
      'after' => l10n('Added after the'),
      ),
    'limit' => array('limit' => 'limit'),
  ));
  
  /* get filters for this album */
  $filters = pwg_query("SELECT * FROM ".CATEGORY_FILTERS_TABLE." WHERE category_id = ".$cat_id." ORDER BY type ASC, cond ASC;");
  while ($filter = pwg_db_fetch_assoc($filters))
  {
    $template->append('filters', array(
      'TYPE' => $filter['type'],
      'COND' => $filter['cond'],
      'VALUE' => $filter['value'],
    ));
  }
  
  /* get image number */
  if ($template->get_template_vars('IMAGE_COUNT') == null)
  {
    list($image_num) = pwg_db_fetch_row(pwg_query("SELECT count(*) FROM ".IMAGE_CATEGORY_TABLE." WHERE category_id = ".$cat_id." AND smart = true;"));
    $template->assign('IMAGE_COUNT', l10n_dec('%d photo', '%d photos', $image_num));
  }
  
  $template->assign(array(
    'SMART_PATH' => SMART_PATH,
    'COUNT_SCRIPT_URL' => SMART_PATH.'include/count_images.php',
  )); 
  $template->set_prefilter('categories', 'smart_cat_modify_prefilter');
}


function smart_cat_modify_prefilter($content, &$smarty)
{
  $search = '<form action="{$F_ACTION}" method="POST" id="links">';
  $replacement = file_get_contents(SMART_PATH.'template/cat_modify.tpl')."\n".$search;
  return str_replace($search, $replacement, $content);
}

?>