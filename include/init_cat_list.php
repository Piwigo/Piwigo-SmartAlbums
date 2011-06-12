<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');
/**
 * Add a link into categories list to regenerate associations
 */
$smart_count = 0;

function smart_cat_list()
{
  global $template, $page, $smart_count;
  include_once(SMART_PATH.'include/functions.inc.php');
  $self_url = get_root_url().'admin.php?page=cat_list'.(isset($_GET['parent_id']) ? '&amp;parent_id='.$_GET['parent_id'] : null);
  
  /* get categories with smart filters */
  $query = '
SELECT DISTINCT id, name 
  FROM '.CATEGORIES_TABLE.' AS c
    INNER JOIN '.CATEGORY_FILTERS_TABLE.' AS cf
      ON c.id = cf.category_id';
  if (!isset($_GET['parent_id']))
  {
    // $query.= '
    // WHERE id_uppercat IS NULL';
  }
  else
  {
    $query .= '
  WHERE uppercats LIKE \'%'.$_GET['parent_id'].'%\'';
  }
  $query .= '
;';
  
  $result = pwg_query($query);
  $smart_cats = array();
  while ($cat = pwg_db_fetch_assoc($result))
  {
    $smart_cats[$cat['id']] = trigger_event('render_category_name', $cat['name']);
  }
  
  $smart_count = count($smart_cats);
  
  if (isset($_GET['smart_generate']))
  {
    /* regenerate photo list | all (sub) categories */
    if ($_GET['smart_generate'] == 'all')
    {
      foreach ($smart_cats as $cat => $name)
      {
        $associated_images = smart_make_associations($cat);
        array_push(
          $page['infos'], 
          l10n_args(get_l10n_args(
            '%d photos associated to album &laquo;%s&raquo;', 
            array(count($associated_images), $name)
            ))
          );
      }
    }
    /* regenerate photo list | one category */
    else
    {
      $associated_images = smart_make_associations($_GET['smart_generate']);    
      array_push(
        $page['infos'], 
        l10n_args(get_l10n_args(
          '%d photos associated to album &laquo;%s&raquo;', 
          array(count($associated_images), $smart_cats[$_GET['smart_generate']])
          ))
        );
    }
    
    invalidate_user_cache(true);
  }
  
  // create regenerate link
  $tpl_cat = array();
  foreach ($smart_cats as $cat => $name)
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
  global $smart_count;
  
  $search[0] = '{if isset($category.U_SYNC) }';
  $replacement[0] = '
{if isset($SMART_URL[$category.ID])}
        <li><a href="{$SMART_URL[$category.ID]}" title="{\'Regenerate photos list of this SmartAlbum\'|@translate}"><img src="{$ROOT_URL}{$themeconf.admin_icon_dir}/synchronize.png" class="button" alt="{\'regenerate photos list\'|@translate}"></a></li>
{/if}'
.$search[0];

  if ($smart_count > 0)
  {
    $search[1] = '</ul>
</form>
{/if}';
    $replacement[1] = $search[1].'
<form method="post" action="{$SMART_URL.all}">
  <input type="hidden" name="pwg_token" value="{$PWG_TOKEN}">
  <p><input class="submit" type="submit" value="{\'Regenerate photos list of all SmartAlbums\'|@translate}"></p>
</form>';
  }

  return str_replace($search, $replacement, $content);
}

?>