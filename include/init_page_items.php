<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

/**
 * Remove form $page['items'] picture that musn't be displayed
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
SELECT DISTINCT(cat.id) AS id
  FROM '.CATEGORIES_TABLE.' AS cat
    INNER JOIN '.IMAGE_CATEGORY_TABLE.' AS img
    ON img.category_id = cat.id
  WHERE img.smart = "true"
;';
    $smart_albums = array_from_query($query, 'id');
      
    if (count($smart_albums) > 0 and !is_admin())
    {
      // add SmartAlbums to forbidden categories
      $user['forbidden_categories_old'] = $user['forbidden_categories'];
      $user['forbidden_categories'] = explode(',', $user['forbidden_categories']);
      $user['forbidden_categories'] = array_unique(array_merge($user['forbidden_categories'], $smart_albums));
      $user['forbidden_categories'] = implode(',', $user['forbidden_categories']);
    
      if ( isset($page['category']) )
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
        $subcat_ids = array_from_query($query, 'id');
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

      $page['items_wo_sa'] = array_from_query($query, 'image_id');
      $page['items'] = array_intersect($page['items'], $page['items_wo_sa']);
      
      // restore forbidden categories
      $user['forbidden_categories'] = $user['forbidden_categories_old'];
    }
  }
}

?>