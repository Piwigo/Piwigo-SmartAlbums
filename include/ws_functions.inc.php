<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');

/**
 * `SmartAlbums` : add new pwg method
 */
function smart_add_methods($arr)
{
  $service = &$arr[0];

  $service->addMethod(
    'smart_album.create',
    'smart_create',
    array(
      'category_id' => array(
        'type' => WS_TYPE_ID
      ),
      'filter_type' => array(
        'info' => 'Only two choices : <br /> and: Photos must match all filters <br /> or: Photos must match at least one filter'
      ),
      'filters' => array(
        'info' => 'See description',
      ),

    ),
    'Create a SmartAlbums <br />
    Filters parameter must be a JSON array of objects with fields: <br />
    <code>{"type": "filter_name", "cond": "condition", "value": "..."}</code><br />
    Example: <br />
    <code>[{"type":"hit","cond":"less","value":100},{"type":"date","cond":"before_post","value":"2024-12-11"}]</code><br /><br />
    For detailed documentation, see the <a href="https://github.com/Piwigo/Piwigo-SmartAlbums/wiki/smart_album.create" target="_blank">SmartAlbums API wiki</a>.',
    null,
    array(
      'hidden' => false,
      'post_only' => true,
      'admin_only' => true,
    )
  );

}

/**
 * `SmartAlbums` : add new pwg method change config
 */
function smart_create($params)
{
  global $page;

  $cat_id = pwg_db_real_escape_string($params['category_id']);

  $query = '
SELECT id
  FROM '.CATEGORIES_TABLE.'
  WHERE id = '.$cat_id.'
;';

  $category = pwg_db_fetch_assoc(pwg_query($query));

  if (!isset($category['id']))
  {
    return new PwgError(404, 'Category not found');
  }

  pwg_query('DELETE FROM '.CATEGORY_FILTERS_TABLE.' WHERE category_id = '.$cat_id.';');

  if (!preg_match('/^(and)$|^(or)$/', $params['filter_type']))
  {
    return new PwgError(WS_ERR_INVALID_PARAM, 'Invalid filter_Type');
  }

  $inserts = array();

  $params['filters'] = stripslashes($params['filters']);
  $filters = json_decode($params['filters'], true);

  // if the return of json_decode($params['filters'], true)
  if (is_null($filters))
  {
    return new PwgError(WS_ERR_INVALID_PARAM, 'Invalid JSON filters : ' . json_last_error_msg());
  }

  $smart_options = smart_get_options();
  
  foreach ($filters as $filter)
  {
    if (!isset($filter['value']) or !isset($filter['cond']) or !isset($filter['type']))
    {
      return new PwgError(WS_ERR_INVALID_PARAM, 'Invalid filters formats. Accepted formats: value, cond, type');
    }

    $filter['value'] = pwg_db_real_escape_string($filter['value']);
    $filter['cond'] = pwg_db_real_escape_string($filter['cond']);
    $filter['type'] = pwg_db_real_escape_string($filter['type']);

    $valid_cond = isset($smart_options[ $filter['type'] ][ 'options' ][ $filter['cond'] ]);

    if (!$valid_cond)
    {
      return new PwgError(WS_ERR_INVALID_PARAM, 'Invalid cond for '.$filter['type']);
    }

    if (($filter = smart_check_filter($filter)) != false)
    {
      $filter['category_id'] = $category['id'];
      $inserts[] = $filter;
    }
    else
    {
      return new PwgError(WS_ERR_INVALID_PARAM, 'Invalid filters : ' . implode($page['errors']));
    }
  }

  $inserts['cond'] = array(
    'type' => 'mode',
    'cond' => 'mode',
    'value' => $params['filter_type']
  );

  mass_inserts(
    CATEGORY_FILTERS_TABLE,
    array('category_id', 'type', 'cond', 'value'),
    $inserts,
    array('ignore'=>true)
  );

  smart_make_associations($category['id']);

  invalidate_user_cache();
  return array(
    'id' => $params['category_id'],
    'message' => 'Smart album created and images associated!'
  );
}