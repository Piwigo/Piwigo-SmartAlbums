<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');
/**
 * Add a link into categories list to regenerate associations
 */
 
function smart_cat_list()
{
  global $template, $page;
  include_once(SMART_PATH.'include/functions.inc.php');
  $self_url = get_root_url().'admin.php?page=cat_list'.(isset($_GET['parent_id']) ? '&amp;parent_id='.$_GET['parent_id'] : null);
  
  /* get categories with smart filters */
  $query = "SELECT DISTINCT id, name 
    FROM ".CATEGORIES_TABLE." AS c
    INNER JOIN ".CATEGORY_FILTERS_TABLE." AS cf
    ON c.id = cf.category_id";
  if (!isset($_GET['parent_id']))
  {
    // $query.= '
    // WHERE id_uppercat IS NULL';
  }
  else
  {
    $query.= '
    WHERE uppercats LIKE \'%'.$_GET['parent_id'].'%\'';
  }
  
  $result = pwg_query($query);
  $categories = array();
  while ($cat = pwg_db_fetch_assoc($result))
  {
    $categories[$cat['id']] = trigger_event('render_category_name', $cat['name']);
  }
  
  if (isset($_GET['smart_generate']))
  {
    /* regenerate photo list | all (sub) categories */
    if ($_GET['smart_generate'] == 'all')
    {
      foreach ($categories as $cat => $name)
      {
        $associated_images = smart_make_associations($cat);
        array_push($page['infos'], l10n_args(get_l10n_args(
          '%d photos associated to album &laquo;%s&raquo;', 
          array(count($associated_images), $name)
        )));
      }
    }
    /* regenerate photo list | one category */
    else
    {
      $associated_images = smart_make_associations($_GET['smart_generate']);    
      array_push($page['infos'], l10n_args(get_l10n_args(
        '%d photos associated to album &laquo;%s&raquo;', 
        array(count($associated_images), $categories[$_GET['smart_generate']])
      )));
    }
    
    invalidate_user_cache(true);
  }
  
  // create regenerate link
  $tpl_cat = array();
  foreach ($categories as $cat => $name)
  {
    $tpl_cat[$cat] = $self_url.'&amp;smart_generate='.$cat;
  }
  $tpl_cat['all'] = $self_url.'&amp;smart_generate=all';
  
  $template->assign(array(
    'SMART_URL' => $tpl_cat,
    'SMART_PATH' => SMART_PATH,
  ));
  
  $template->set_prefilter('categories', 'smart_cat_list_prefilter');
}


function smart_cat_list_prefilter($content, &$smarty)
{
$search[0] = '<ul class="categoryActions">';
$replacement[0] = $search[0].'
{if isset($SMART_URL[$category.ID])}
        <li><a href="{$SMART_URL[$category.ID]}" title="{\'regenerate photos list\'|@translate}"><img src="{$SMART_PATH}template/refresh.png" class="button" alt="{\'regenerate photos list\'|@translate}"></a></li>
{/if}';

$search[1] = '</ul>
</form>
{/if}';
$replacement[1] = $search[1].'
<form method="post" action="{$SMART_URL.all}">
  <input type="hidden" name="pwg_token" value="{$PWG_TOKEN}">
  <p><input class="submit" type="submit" value="{\'regenerate photos list of all SmartAlbums\'|@translate}"></p>
</form>';

  return str_replace($search, $replacement, $content);
}

?>