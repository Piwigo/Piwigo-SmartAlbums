<?php
defined('SMART_PATH') or die('Hacking attempt!');

if (isset($_GET['hide_messages']))
{
  $conf['SmartAlbums']['show_list_messages'] = false;
  conf_update_param('SmartAlbums', $conf['SmartAlbums']);
}

// +-----------------------------------------------------------------------+
// |                            initialization                             |
// +-----------------------------------------------------------------------+
$base_url = get_root_url() . 'admin.php?page=';
$self_url = SMART_ADMIN . '-cat_list';

$categories = array();
$query = '
SELECT
    id,
    name,
    permalink,
    dir,
    cf.updated
  FROM '.CATEGORIES_TABLE.' AS cat
  INNER JOIN '.CATEGORY_FILTERS_TABLE.' AS cf
    ON cf.category_id = cat.id
  GROUP BY id
  ORDER BY global_rank ASC
;';
$categories = hash_from_query($query, 'id');

// +-----------------------------------------------------------------------+
// |                    virtual categories management                      |
// +-----------------------------------------------------------------------+
// request to delete a album
if (isset($_GET['delete']) and is_numeric($_GET['delete']))
{
  delete_categories(array($_GET['delete']));
  $_SESSION['page_infos'] = array(l10n('SmartAlbum deleted'));
  update_global_rank();
  redirect($self_url);
}
// request to add a album
else if (isset($_POST['submitAdd']))
{
  $output_create = create_virtual_category(
    $_POST['virtual_name'],
    @$_POST['parent_id']
    );

  if (isset($output_create['error']))
  {
    $page['errors'][] = $output_create['error'];
  }
  else
  {
    $_SESSION['page_infos'] = array(l10n('SmartAlbum added'));
    $redirect_url = SMART_ADMIN . '-album&amp;cat_id='.$output_create['id'].'&amp;new_smart';
    redirect($redirect_url);
  }
}
// request to regeneration
else if (isset($_GET['smart_generate']))
{
  /* regenerate photo list | all categories */
  if ($_GET['smart_generate'] == 'all')
  {
    foreach ($categories as $category)
    {
      $associated_images = smart_make_associations($category['id']);

      $page['infos'][] = l10n(
        '%d photos associated to album %s',
        count($associated_images),
        '&laquo;'.trigger_change('render_category_name', $category['name'], 'admin_cat_list').'&raquo;'
        );
    }
  }
  /* regenerate photo list | one category */
  else
  {
    $associated_images = smart_make_associations($_GET['smart_generate']);

    $page['infos'][] = l10n(
      '%d photos associated to album %s',
      count($associated_images),
      '&laquo;'.trigger_change('render_category_name', $categories[ $_GET['smart_generate'] ]['name'], 'admin_cat_list').'&raquo;'
      );
  }

  define('SMART_NOT_UPDATE', 1);
  invalidate_user_cache();
}

// +-----------------------------------------------------------------------+
// |                       template initialization                         |
// +-----------------------------------------------------------------------+
$template->assign(array(
  'F_ACTION' => $self_url,
  'PWG_TOKEN' => get_pwg_token(),
 ));

// retrieve all existing categories for album creation
$query = '
SELECT id,name,uppercats,global_rank
  FROM '.CATEGORIES_TABLE.'
;';

display_select_cat_wrapper(
  $query, null,
  'category_options'
  );

if ($conf['SmartAlbums']['show_list_messages'])
{
  $page['warnings'][] = l10n('Only SmartAlbums are displayed on this page');
  $page['warnings'][] = sprintf(l10n('To order albums please go the main albums <a href="%s">management page</a>'), $base_url.'cat_list');
  $page['warnings'][] = '<a href="'.$self_url.'&hide_messages">['.l10n('Don\'t show this message again').']</a>';
}

// +-----------------------------------------------------------------------+
// |                          Categories display                           |
// +-----------------------------------------------------------------------+

$categories_count_images = array();
if (count($categories))
{
  $query = '
SELECT
    category_id,
    COUNT(image_id) AS total_images
  FROM '.IMAGE_CATEGORY_TABLE.'
  WHERE category_id IN ('.implode(',', array_keys($categories)).')
  GROUP BY category_id
;';
  $categories_count_images = simple_hash_from_query($query, 'category_id', 'total_images');
}

$template->assign('categories', array());

foreach ($categories as $category)
{
  $tpl_cat =
    array(
      'NAME'        => get_cat_display_name_from_id($category['id'], $base_url.'album-'),
      'ID'          => $category['id'],
      'IMG_COUNT'   => !empty($categories_count_images[ $category['id'] ]) ? $categories_count_images[ $category['id'] ] : 0,
      'LAST_UPDATE' => format_date($category['updated'], true),

      'U_JUMPTO'    => make_index_url(array('category' => $category)),
      'U_EDIT'      => SMART_ADMIN.'-album&amp;cat_id='.$category['id'],
      'U_DELETE'    => $self_url.'&amp;delete='.$category['id'].'&amp;pwg_token='.get_pwg_token(),
      'U_SMART'     => $self_url.'&amp;smart_generate='.$category['id'],
    );

  $template->append('categories', $tpl_cat);
}

$template->set_filename('SmartAlbums_content', realpath(SMART_PATH . 'admin/template/cat_list.tpl'));
