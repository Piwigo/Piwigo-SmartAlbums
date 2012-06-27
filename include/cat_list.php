<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');
/**
 * Add a link into categories list to regenerate associations
 */
$smart_count = 0;

function smart_cat_list()
{
  global $template, $page, $smart_count;

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
  
  $smart_cats = hash_from_query($query, 'id'); 
  $smart_count = count($smart_cats);
  
  if (isset($_GET['smart_generate']))
  {
    /* regenerate photo list | all (sub) categories */
    if ($_GET['smart_generate'] == 'all')
    {
      foreach ($smart_cats as $category)
      {
        $associated_images = smart_make_associations($category['id']);
        array_push($page['infos'], 
          sprintf(l10n('%d photos associated to album %s'), 
            count($associated_images), 
            '&laquo;'.trigger_event('render_category_name', $category['name'], 'admin_cat_list').'&raquo;'
            )
          );
      }
    }
    /* regenerate photo list | one category */
    else
    {
      $associated_images = smart_make_associations($_GET['smart_generate']);    
      array_push($page['infos'], 
        sprintf(l10n('%d photos associated to album %s'), 
          count($associated_images), 
          '&laquo;'.trigger_event('render_category_name', $smart_cats[ $_GET['smart_generate'] ]['name'], 'admin_cat_list').'&raquo;'
          )
        );
    }
    
    define('SMART_NOT_UPDATE', 1);
    invalidate_user_cache();
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
  
  $search[0] = '{if isset($category.U_MANAGE_ELEMENTS) }';
  $replacement[0] = $search[0].'
{if isset($SMART_URL[$category.ID])}
| <a href="{$SMART_URL[$category.ID]}">{\'Regenerate photos list of this SmartAlbum\'|@translate}</a>
{/if}';

  if ($smart_count > 0)
  {
    $search[1] = '<a href="#" id="autoOrderOpen">{\'apply automatic sort order\'|@translate}</a>';
    $replacement[1] = $search[1].'
| <a href="{$SMART_URL.all}">{\'Regenerate photos list of all SmartAlbums\'|@translate}</a>';
  }
  
  $search[2] = '{$category.NAME}</a></strong>';
  $replacement[2] = $search[2].'
{if isset($SMART_URL[$category.ID])}
<img src="'.SMART_PATH.'admin/template/lightning.png">
{/if}';

  return str_replace($search, $replacement, $content);
}

?>