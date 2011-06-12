<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');
/**
 * Add the SmartAlbums configuration tool to virtual cats' configuration page
 */
 
function smart_cat_modify()
{
  global $template, $page;
  include_once(SMART_PATH.'include/functions.inc.php');
  
  $cat_id = $_GET['cat_id'];
  list($cat_dir) = pwg_db_fetch_row(pwg_query('SELECT dir FROM '.CATEGORIES_TABLE.' WHERE id = '.$cat_id.';'));
  
  // category must be virtual
  if ($cat_dir != NULL)
  {
    return;
  }
  
  /* SAVE FILTERS */
  if (isset($_POST['submitFilters']))
  {
    // test if it was a Smart Album
    $result = pwg_query('SELECT DISTINCT category_id FROM '.CATEGORY_FILTERS_TABLE.' WHERE category_id = '.$cat_id.';');
    $was_smart = pwg_db_num_rows($result);
    
    /* this album is no longer a SmartAlbum */
    if ($was_smart AND !isset($_POST['is_smart']))
    {
      pwg_query('DELETE FROM '.IMAGE_CATEGORY_TABLE.' WHERE category_id = '.$cat_id.' AND smart = true;');
      pwg_query('DELETE FROM '.CATEGORY_FILTERS_TABLE.' WHERE category_id = '.$cat_id.';');
      set_random_representant(array($cat_id));
    }
    /* no filter selected */
    else if (isset($_POST['is_smart']) AND !isset($_POST['filters']))
    {
      array_push($page['errors'], l10n('No filter selected'));
    }
    /* everything is fine */
    else if (isset($_POST['is_smart']) AND count($_POST['filters']) > 0)
    {
      pwg_query('DELETE FROM '.CATEGORY_FILTERS_TABLE.' WHERE category_id = '.$cat_id.';');
      
      $limit_is_set = false;
      foreach ($_POST['filters'] as $filter)
      {
        if (($filter = smart_check_filter($filter)) != false)
        {
          $query = '
INSERT INTO '.CATEGORY_FILTERS_TABLE.' 
  VALUES(
    '.$cat_id.', 
    "'.$filter['type'].'", 
    "'.$filter['cond'].'", 
    "'.$filter['value'].'"
  )
;';
        pwg_query($query);
        }
      }
      
      $associated_images = smart_make_associations($cat_id);
      invalidate_user_cache(true);
      $template->assign('IMAGE_COUNT', l10n_dec('%d photo', '%d photos', count($associated_images)));
    }
  }
      
  /* select options, for html_options */
  $template->assign(
    'options', 
    array(
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
      'limit' => array('limit' => 'limit'), // second filter not used
      )
    );
  
  /* get filters for this album */
  $filters = pwg_query('SELECT * FROM '.CATEGORY_FILTERS_TABLE.' WHERE category_id = '.$cat_id.' ORDER BY type ASC, cond ASC;');
  while ($filter = pwg_db_fetch_assoc($filters))
  {
    // get tags name and id
    if ($filter['type'] == 'tags')
    {
      $query = '
SELECT
    id AS tag_id,
    name AS tag_name
  FROM '.TAGS_TABLE.'
  WHERE id IN('.$filter['value'].')
';
      $filter['value'] = get_taglist($query); 
    }
    
    $template->append('filters', array(
      'TYPE' => $filter['type'],
      'COND' => $filter['cond'],
      'VALUE' => $filter['value'],
    ));
  }
  
  /* all tags */
  $query = '
SELECT
    id AS tag_id,
    name AS tag_name
  FROM '.TAGS_TABLE.'
;';
  $tags = get_taglist($query);
  
  /* get image number */
  if ($template->get_template_vars('IMAGE_COUNT') == null)
  {
    list($image_num) = pwg_db_fetch_row(pwg_query('SELECT count(*) FROM '.IMAGE_CATEGORY_TABLE.' WHERE category_id = '.$cat_id.' AND smart = true;'));
    $template->assign('IMAGE_COUNT', l10n_dec('%d photo', '%d photos', $image_num));
  }
  
  $template->assign(array(
    'SMART_PATH' => SMART_PATH,
    'COUNT_SCRIPT_URL' => SMART_PATH.'include/count_images.php',
    'tags' => $tags,
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