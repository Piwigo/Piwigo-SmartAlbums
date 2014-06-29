<?php
defined('SMART_PATH') or die('Hacking attempt!');

/**
 * clean table when categories are deleted
 */
function smart_delete_categories($ids)
{
  $query = '
DELETE FROM '.CATEGORY_FILTERS_TABLE.'
  WHERE category_id IN('.implode(',', $ids).')
;';
  pwg_query($query);
}

/**
 * update images list periodically
 */
function smart_periodic_update()
{
  global $conf;

  // we only search for old albums every hour, nevermind which user is connected
  if ($conf['SmartAlbums']['last_update'] > time() - 3600) return;

  $conf['SmartAlbums']['last_update'] = time();
  conf_update_param('SmartAlbums', $conf['SmartAlbums']);

  // get categories with smart filters
  $query = '
SELECT DISTINCT category_id
  FROM '.CATEGORY_FILTERS_TABLE.'
  WHERE updated < DATE_SUB(NOW(), INTERVAL '.$conf['SmartAlbums']['update_timeout'].' DAY)
;';

  // regenerate photo list
  $smart_cats = query2array($query, null, 'category_id');
  array_map('smart_make_associations', $smart_cats);
}

/**
 * Remove picture that must not be displayed from $page['items']
 *
 * here we get all pictures that current user could see
 * if SmartAlbums doesn't exist, and make intersect with pictures
 * actually displayed
 */
function smart_init_page_items()
{
  global $user, $page, $conf;

  if (
    ('categories' == $page['section']) and
    (!isset($page['chronology_field'])) and
    (
      (isset($page['category'])) or
      (isset($page['flat']))
    )
  ) {

    $query = '
SELECT DISTINCT category_id
  FROM '.CATEGORY_FILTERS_TABLE.'
;';
    $smart_albums = query2array($query, null, 'category_id');

    if (count($smart_albums) > 0 and !is_admin())
    {
      // add SmartAlbums to forbidden categories
      $user['forbidden_categories_old'] = $user['forbidden_categories'];
      $user['forbidden_categories'] = explode(',', $user['forbidden_categories']);
      $user['forbidden_categories'] = array_unique(array_merge($user['forbidden_categories'], $smart_albums));
      $user['forbidden_categories'] = implode(',', $user['forbidden_categories']);

      if (isset($page['category']))
      {
        $query = '
SELECT id
  FROM '.CATEGORIES_TABLE.'
  WHERE
    '.get_sql_condition_FandF(
      array(
        'forbidden_categories' => 'id',
        'visible_categories' => 'id',
        )
      );
        $subcat_ids = query2array($query, null, 'id');
        $subcat_ids[] = 0;
        $where_sql = 'category_id IN ('.implode(',',$subcat_ids).')';
        // remove categories from forbidden because just checked above
        $forbidden = get_sql_condition_FandF(
          array(
            'visible_images' => 'id'
            ),
          'AND'
          );
      }
      else
      {
        $where_sql = '1=1';
        $forbidden = get_sql_condition_FandF(
          array(
            'forbidden_categories' => 'category_id',
            'visible_categories' => 'category_id',
            'visible_images' => 'id'
            ),
          'AND'
          );
      }

      // Main query
      $query = '
SELECT DISTINCT(image_id)
  FROM '.IMAGE_CATEGORY_TABLE.'
    INNER JOIN '.IMAGES_TABLE.' ON id = image_id
  WHERE
    '.$where_sql.'
'.$forbidden.'
  '.$conf['order_by'].'
;';

      $page['items_wo_sa'] = query2array($query, null, 'image_id');
      $page['items'] = array_intersect($page['items'], $page['items_wo_sa']);

      // restore forbidden categories
      $user['forbidden_categories'] = $user['forbidden_categories_old'];
    }
  }
}
