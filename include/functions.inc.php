<?php
defined('SMART_PATH') or die('Hacking attempt!');

/*
 * Associates images to the category according to the filters
 * @param int category_id
 * @return array
 */
function smart_make_associations($cat_id)
{
  global $logger;

  $logger->debug(__FUNCTION__);
  // is the current album thumbnail associated to the album? If not, then we won't
  // refresh it after associations reset. It would mean the album thumbnail is
  // already in another album.
  $album_thumbnail_was_in_album = false;

  $query = '
SELECT representative_picture_id
  FROM '.CATEGORIES_TABLE.'
  WHERE id = '.$cat_id.'
;';
  list($rep_id) = pwg_db_fetch_row(pwg_query($query));

  if (!empty($rep_id))
  {
    $query = '
SELECT
    COUNT(*)
  FROM '.IMAGE_CATEGORY_TABLE.'
  WHERE category_id = '.$cat_id.'
    AND image_id = '.$rep_id.'
;';
    list($count) = pwg_db_fetch_row(pwg_query($query));

    if ($count > 0)
    {
      $album_thumbnail_was_in_album = true;
    }
  }

  $query = '
DELETE FROM '.IMAGE_CATEGORY_TABLE.'
  WHERE
    category_id = '.$cat_id.'
    AND smart = true
;';
  pwg_query($query);

  $images = smart_get_pictures($cat_id);

  if (count($images) != 0)
  {
    foreach ($images as $img)
    {
      $datas[] = array(
        'image_id' => $img,
        'category_id' => $cat_id,
        'smart' => true,
        );
    }
    mass_inserts(
      IMAGE_CATEGORY_TABLE,
      array_keys($datas[0]),
      $datas,
      array('ignore'=>true)
      );
  }

  if ($album_thumbnail_was_in_album)
  {
    $logger->debug(__FUNCTION__.' album thumbnail was in album');
    // if the album thumbnail was already in the album and is still in the album, then do nothing here
    if (!in_array($rep_id, $images))
    {
      $logger->debug(__FUNCTION__.' and is no longer in album, so we reset the album thumbnail');
      include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');
      set_random_representant(array($cat_id));
    }
  }

  $query = '
UPDATE '.CATEGORY_FILTERS_TABLE.'
  SET updated = NOW()
  WHERE category_id = '.$cat_id.'
;';
  pwg_query($query);

  return $images;
}


/*
 * Make associations for all SmartAlbums
 * Called with invalidate_user_cache
 */
function smart_make_all_associations()
{
  global $conf;

  if (defined('SMART_NOT_UPDATE'))
  {
    return;
  }

  // get categories with smart filters
  $query = '
SELECT DISTINCT category_id
  FROM '.CATEGORY_FILTERS_TABLE.'
;';

  // regenerate photo list
  $smart_cats = query2array($query, null, 'category_id');
  array_map('smart_make_associations', $smart_cats);
}


/*
 * Generates the list of images, according to the filters of the category
 * @param int category_id
 * @param array filters, if null => catch from db
 * @return array
 */
function smart_get_pictures($cat_id, $filters = null)
{
  global $conf;

  /* get filters */
  if (!isset($filters))
  {
    $query = '
SELECT *
  FROM '.CATEGORY_FILTERS_TABLE.'
  WHERE category_id = '.$cat_id.'
  ORDER BY type ASC, cond ASC
;';
    $result = pwg_query($query);

    if (!pwg_db_num_rows($result))
    {
      return array();
    }

    $filters = array();
    while ($row = pwg_db_fetch_assoc($result))
    {
      $filters[] = array(
        'type' => $row['type'],
        'cond' => $row['cond'],
        'value' => $row['value'],
        );
    }
  }
  else if (!count($filters))
  {
    return array();
  }

  $mode = 'and';

  /* build constrains */
  ## generate 'join', 'where' arrays and 'limit' string to create the SQL query
  ## inspired by PicsEngine 3 by Michael Villar
  $i_tags = 1;
  $join = array();
  $where = array();

  foreach ($filters as $filter)
  {
    switch ($filter['type'])
    {
      // tags
      case 'tags':
      {
        switch ($filter['cond'])
        {
          // search images which have all tags
          case 'all':
          {
            $tags_arr = explode(',', $filter['value']);
            $tags_where = array() ;
            foreach ($tags_arr as $value)
            {
              $join[] = IMAGE_TAG_TABLE.' AS it'.$i_tags.' ON i.id = it'.$i_tags.'.image_id';
              $tags_where[] = 'it'.$i_tags.'.tag_id = '.$value;
              $i_tags++;
            }
            $where[] = '('.implode(' and ', $tags_where).')' ;

            break;
          }
          // search images which tags are in the list
          case 'one':
          {
            $join[] = IMAGE_TAG_TABLE.' AS it'.$i_tags.' ON i.id = it'.$i_tags.'.image_id';
            $where[] = 'it'.$i_tags.'.tag_id IN ('.$filter['value'].')';
            $i_tags++;

            break;
          }
          // exclude images which tags are in the list
          case 'none':
          {
            $sub_query = '
      SELECT it'.$i_tags.'.image_id
        FROM '.IMAGE_TAG_TABLE.' AS it'.$i_tags.'
        WHERE
          it'.$i_tags.'.image_id = i.id AND
          it'.$i_tags.'.tag_id IN ('.$filter['value'].')
        GROUP BY it'.$i_tags.'.image_id
      ';
            $join[] = IMAGE_TAG_TABLE.' AS it'.$i_tags.' ON i.id = it'.$i_tags.'.image_id';
            $where[] = 'NOT EXISTS ('.$sub_query.')';
            $i_tags++;

            break;
          }
          // exclude images which tags are not in the list and search images which have all tags
          case 'only':
          {
            $sub_query = '
      SELECT it'.$i_tags.'.image_id
        FROM '.IMAGE_TAG_TABLE.' AS it'.$i_tags.'
        WHERE
          it'.$i_tags.'.image_id = i.id AND
          it'.$i_tags.'.tag_id NOT IN ('.$filter['value'].')
        GROUP BY it'.$i_tags.'.image_id
      ';
            $join[] = IMAGE_TAG_TABLE.' AS it'.$i_tags.' ON i.id = it'.$i_tags.'.image_id';
            $where[] = 'NOT EXISTS ('.$sub_query.')';
            $i_tags++;

            $tags_arr = explode(',', $filter['value']);
            foreach($tags_arr as $value)
            {
              $join[] = IMAGE_TAG_TABLE.' AS it'.$i_tags.' ON i.id = it'.$i_tags.'.image_id';
              $where[] = 'it'.$i_tags.'.tag_id = '.$value;
              $i_tags++;
            }

            break;
          }
        }

        break;
      }

      // date
      case 'date':
      {
        switch ($filter['cond'])
        {
          case 'the_post':
            $where[] = 'date_available BETWEEN "'.$filter['value'].' 00:00:00" AND "'.$filter['value'].' 23:59:59"';
            break;
          case 'before_post':
            $where[] = 'date_available < "'.$filter['value'].' 00:00:00"';
            break;
          case 'after_post':
            $where[] = 'date_available > "'.$filter['value'].' 23:59:59"';
            break;
          case 'the_taken':
            $where[] = 'date_creation BETWEEN "'.$filter['value'].' 00:00:00" AND "'.$filter['value'].' 23:59:59"';
            break;
          case 'before_taken':
            $where[] = 'date_creation < "'.$filter['value'].' 00:00:00"';
            break;
          case 'after_taken':
            $where[] = 'date_creation > "'.$filter['value'].' 23:59:59"';
            break;
        }

        break;
      }

      // name
      case 'name':
      {
        switch ($filter['cond'])
        {
          case 'contain':
            $where[] = 'name LIKE "%'.$filter['value'].'%"';
            break;
          case 'begin':
            $where[] = 'name LIKE "'.$filter['value'].'%"';
            break;
          case 'end':
            $where[] = 'name LIKE "%'.$filter['value'].'"';
            break;
          case 'not_contain':
            $where[] = 'name NOT LIKE "%'.$filter['value'].'%"';
            break;
          case 'not_begin':
            $where[] = 'name NOT LIKE "'.$filter['value'].'%"';
            break;
          case 'not_end':
            $where[] = 'name NOT LIKE "%'.$filter['value'].'"';
            break;
          case 'regex':
            $where[] = 'name REGEXP "'.$filter['value'].'"';
            break;
        }

        break;
      }

      // album
      case 'album':
      {
        $filter['values'] = explode(',', $filter['value']);
        $filter['recursive'] = array_shift($filter['values']) == "true";
        $filter['value'] = implode(',', $filter['values']);

        switch ($filter['cond'])
        {
          // search images existing in all albums
          case 'all':
          {
            foreach ($filter['values'] as $value)
            {
              if ($filter['recursive'])
              {
                $value = get_subcat_ids_query(array($value));
              }
              $sub_query = '
      SELECT image_id
        FROM '.IMAGE_CATEGORY_TABLE.'
        WHERE category_id IN('.$value.')
      ';
              $where[] = 'i.id IN ('.$sub_query.')';
            }

            break;
          }
          // search images existing in one of these albums
          case 'one':
          {
            if ($filter['recursive'])
            {
              $value = get_subcat_ids_query($filter['values']);
            }
            else
            {
              $value = $filter['value'];
            }
            $sub_query = '
      SELECT image_id
        FROM '.IMAGE_CATEGORY_TABLE.'
        WHERE category_id IN('.$value.')
      ';
            $where[] = 'i.id IN ('.$sub_query.')';

            break;
          }
          // exclude images existing in one of these albums
          case 'none':
          {
            if ($filter['recursive'])
            {
              $value = get_subcat_ids_query($filter['values']);
            }
            else
            {
              $value = $filter['value'];
            }
            $sub_query = '
      SELECT image_id
        FROM '.IMAGE_CATEGORY_TABLE.'
        WHERE category_id IN('.$value.')
      ';
            $where[] = 'i.id NOT IN ('.$sub_query.')';

            break;
          }
          // exclude images existing on other albums, and search images existing in all albums
          case 'only':
          {
            if ($filter['recursive'])
            {
              $value = get_subcat_ids_query($filter['values']);
            }
            else
            {
              $value = $filter['value'];
            }
            $sub_query = '
      SELECT image_id
        FROM '.IMAGE_CATEGORY_TABLE.'
        WHERE category_id NOT IN('.$value.')
      ';
            $where[] = 'i.id NOT IN ('.$sub_query.')';

            foreach ($filter['values'] as $value)
            {
              if ($filter['recursive'])
              {
                $value = get_subcat_ids_query(array($value));
              }
              $sub_query = '
      SELECT image_id
        FROM '.IMAGE_CATEGORY_TABLE.'
        WHERE category_id = '.$value.'
      ';
              $where[] = 'i.id IN ('.$sub_query.')';
            }

            break;
          }
        }

        break;
      }

      // dimensions
      case 'dimensions':
      {
        $filter['value'] = explode(',', $filter['value']);

        switch ($filter['cond'])
        {
          case 'width':
            $where[] = 'width >= '.$filter['value'][0].' AND width <= '.$filter['value'][1];
            break;
          case 'height':
            $where[] = 'height >= '.$filter['value'][0].' AND height <= '.$filter['value'][1];
            break;
          case 'ratio':
            $where[] = 'width/height >= '.$filter['value'][0].' AND width/height < '.($filter['value'][1]+0.01);
            break;
        }
      }

      // author
      case 'author':
      {
        switch ($filter['cond'])
        {
          case 'is':
            if ($filter['value'] != 'NULL') $filter['value'] = '"'.$filter['value'].'"';
            $where[] = 'author = '.$filter['value'].'';
            break;
          case 'not_is':
            if ($filter['value'] != 'NULL') $filter['value'] = '"'.$filter['value'].'"';
            $where[] = 'author != '.$filter['value'].'';
            break;
          case 'in':
            $filter['value'] = '"'.str_replace(',', '","', $filter['value']).'"';
            $where[] = 'author IN('.$filter['value'].')';
            break;
          case 'not_in':
            $filter['value'] = '"'.str_replace(',', '","', $filter['value']).'"';
            $where[] = 'author NOT IN('.$filter['value'].')';
            break;
          case 'regex':
            $where[] = 'author REGEXP "'.$filter['value'].'"';
            break;
        }

        break;
      }

      // hit
      case 'hit':
      {
        switch ($filter['cond'])
        {
          case 'less':
            $where[] = 'hit < '.$filter['value'].'';
            break;
          case 'more':
            $where[] = 'hit >= '.$filter['value'].'';
            break;
        }

        break;
      }

      // rating_score
      case 'rating_score':
      {
        switch ($filter['cond'])
        {
          case 'less':
            $where[] = 'rating_score < '.$filter['value'].'';
            break;
          case 'more':
            $where[] = 'rating_score >= '.$filter['value'].'';
            break;
        }

        break;
      }

      // level
      case 'level':
      {
        $where[] = 'level = '.$filter['value'].'';
        break;
      }

      // limit
      case 'limit':
      {
        $limit = '0, '.$filter['value'];
        if (!empty($filter['cond'])) $order_by = $filter['cond'];
        break;
      }

      // mode
      case 'mode':
      {
        $mode = $filter['value'];
        break;
      }
    }
  }

  /* bluid query */
  $MainQuery = '
SELECT i.id
  FROM '.IMAGES_TABLE.' AS i';

    if (count($join))
    {
      $MainQuery.= '
    LEFT JOIN '.implode("\n    LEFT JOIN ", $join);
    }
    if (count($where))
    {
      $MainQuery.= '
  WHERE
    '.implode("\n    ".$mode." ", $where);
    }

  $MainQuery.= '
  GROUP BY i.id
  '.(isset($order_by) ? "ORDER BY ".$order_by : $conf['order_by']).'
  '.(isset($limit) ? "LIMIT ".$limit : null).'
;';

  if (defined('SMART_DEBUG'))
  {
    file_put_contents(SMART_PATH.'dump_filters.txt', print_r($filters, true));
    file_put_contents(SMART_PATH.'dump_query.sql', $MainQuery);
  }

  return query2array($MainQuery, null, 'id');
}


/**
 * Check if the filter is proper
 * @param array filter
 * @return array or false
 */
function smart_check_filter($filter)
{
  global $page, $limit_is_set, $level_is_set;

  $error = false;
  if (!isset($limit_is_set)) $limit_is_set = false;
  if (!isset($level_is_set)) $level_is_set = false;

  switch ($filter['type'])
  {
    # tags
    case 'tags':
    {
      if ($filter['value'] == null)
      {
        $page['errors'][] = l10n('No tag selected');
      }
      else
      {
        include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');
        $filter['value'] = implode(',', get_tag_ids($filter['value']));
      }
      break;
    }
    # date
    case 'date':
    {
      if (!preg_match('#([0-9]{4})-([0-9]{2})-([0-9]{2})#', $filter['value']))
      {
        $page['errors'][] = l10n('Date string is malformed');
      }
      break;
    }
    # name
    case 'name':
    {
      if (empty($filter['value']))
      {
        $page['errors'][] = l10n('Name is empty');
      }
      else if ($filter['cond']=='regex' and @preg_match('/'.$filter['value'].'/', null)===false)
      {
        $page['errors'][] = l10n('Regex is malformed');
      }
      break;
    }
    # album
    case 'album':
    {
      if (@$filter['value'] == null)
      {
        $page['errors'][] = l10n('No album selected');
      }
      else
      {
        array_unshift($filter['value'], boolean_to_string(isset($filter['recursive'])));
        $filter['value'] = implode(',', $filter['value']);
      }
      break;
    }
    # dimensions
    case 'dimensions':
    {
      if (empty($filter['value']['min']) or empty($filter['value']['max']))
      {
        $error = true;
      }
      else
      {
        $filter['value'] = $filter['value']['min'].','.$filter['value']['max'];
      }
      break;
    }
    # author
    case 'author':
    {
      if (empty($filter['value']))
      {
        $page['errors'][] = l10n('Author is empty');
      }
      else if ($filter['cond']=='regex' and @preg_match('/'.$filter['value'].'/', null)===false)
      {
        $page['errors'][] = l10n('Regex is malformed');
      }
      else
      {
        $filter['value'] = preg_replace('#([ ]?),([ ]?)#', ',', $filter['value']);
      }
      break;
    }
    # hit
    case 'hit':
    {
      if (!preg_match('#([0-9]+)#', $filter['value']))
      {
        $page['errors'][] = l10n('Hits must be an integer');
      }
      break;
    }
    # rating_score
    case 'rating_score':
    {
      if (!preg_match('#([0-9]+)#', $filter['value']))
      {
        $page['errors'][] = l10n('Rating score must be an integer');
      }
      break;
    }
    # level
    case 'level':
    {
      if ($level_is_set == true) // only one level is allowed, first is saved
      {
        $page['errors'][] = l10n('You can\'t use more than one level filter');
      }
      else
      {
        $filter['cond'] = 'level';
        $level_is_set = true;
      }
      break;
    }
    # limit
    case 'limit':
    {
      if ($limit_is_set == true) // only one limit is allowed, first is saved
      {
        $page['errors'][] = l10n('You can\'t use more than one limit filter');
      }
      else if (!preg_match('#([0-9]+)#', $filter['value']))
      {
        $page['errors'][] = l10n('Limit must be an integer');
      }
      else
      {
        $limit_is_set = true;
      }
      break;
    }
    # mode
    case 'mode':
    {
      $filter['cond'] = 'mode';
      break;
    }

    default:
    {
      $error = true;
      break;
    }
  }


  if (!$error && empty($page['errors']))
  {
    return $filter;
  }
  else
  {
    return false;
  }
}

/**
 * Returns SQL query returning all subcategory identifiers of given category ids
 *
 * @param int[] $ids
 * @return int[]
 */
function get_subcat_ids_query($ids)
{
  $query = '
SELECT DISTINCT(id)
  FROM '.CATEGORIES_TABLE.'
  WHERE ';
  foreach ($ids as $num => $category_id)
  {
    if ($num > 0)
    {
      $query.= '
    OR ';
    }
    $query.= 'uppercats '.DB_REGEX_OPERATOR.' \'(^|,)'.$category_id.'(,|$)\'';
  }
  return $query;
}

/**
 * Returns all filters options
 */
function smart_get_options()
{
  return array(
    'tags' => array(
      'name' => l10n('Tags'),
      'options' => array(
        'all'   => l10n('All these tags'),
        'one'   => l10n('One of these tags'),
        'none'  => l10n('None of these tags'),
        'only'  => l10n('Only these tags'),
      ),
      'example' => array('value' => array('tag1', 'tag2')),
    ),
    'date' => array(
      'name' => l10n('Date'),
      'options' => array(
        'the_post'     => l10n('Added on'),
        'before_post'  => l10n('Added before'),
        'after_post'   => l10n('Added after'),
        'the_taken'    => l10n('Created on'),
        'before_taken' => l10n('Created before'),
        'after_taken'  => l10n('Created after'),
      ),
      'example' => array('value' => '2024-12-11'),
    ),
    'name' => array(
      'name' => l10n('Photo name'),
      'options' => array(
        'contain'     => l10n('Contains'),
        'begin'       => l10n('Begins with'),
        'end'         => l10n('Ends with'),
        'not_contain' => l10n('Doesn\'t contain'),
        'not_begin'   => l10n('Doesn\'t begin with'),
        'not_end'     => l10n('Doesn\'t end with'),
        'regex'       => l10n('Regular expression'),
      ),
      'example' => array('value' => 'holiday'),
    ),
    'album' => array(
      'name' => l10n('Album'),
      'options' => array(
        'all'   => l10n('All these albums'),
        'one'   => l10n('One of these albums'),
        'none'  => l10n('None of these albums'),
        'only'  => l10n('Only these albums'),
      ),
      'example' => array('value' => array(42, 51), 'recursive' => true),
    ),
    'dimensions' => array(
      'name' => l10n('Dimensions'),
      'options' => array(
        'width'  => l10n('Width'),
        'height' => l10n('Height'),
        'ratio'  => l10n('Ratio') . ' (' . l10n('Width') . '/' . l10n('Height') . ')',
      ),
      'example' => array('value' => array('min' => 800, 'max' => 1600)),
    ),
    'author' => array(
      'name' => l10n('Author'),
      'options' => array(
        'is'      => l10n('Is'),
        'in'      => l10n('Is in'),
        'not_is'  => l10n('Is not'),
        'not_in'  => l10n('Is not in'),
        'regex'   => l10n('Regular expression'),
      ),
      'example' => array('value' => 'George'),
    ),
    'hit' => array(
      'name' => l10n('Hits'),
      'options' => array(
        'less' => l10n('Bellow'),
        'more' => l10n('Above'),
      ),
      'example' => array('value' => 100),
    ),
    'rating_score' => array(
      'name' => l10n('Rating score'),
      'options' => array(
        'less' => l10n('Bellow'),
        'more' => l10n('Above'),
      ),
      'example' => array('value' => 4),
    ),
    'level' => array(
      'name' => l10n('Privacy level'),
      'options' => array(),
      'example' => array('value' => 2),
    ),
    'limit' => array(
      'name' => l10n('Max. number of photos'),
      'options' => array(
        ''                    => '-- ' . l10n('Default') . ' --',
        'file ASC'            => l10n('File name, A &rarr; Z'),
        'file DESC'           => l10n('File name, Z &rarr; A'),
        'name ASC'            => l10n('Photo title, A &rarr; Z'),
        'name DESC'           => l10n('Photo title, Z &rarr; A'),
        'date_creation DESC'  => l10n('Date created, new &rarr; old'),
        'date_creation ASC'   => l10n('Date created, old &rarr; new'),
        'date_available DESC' => l10n('Date posted, new &rarr; old'),
        'date_available ASC'  => l10n('Date posted, old &rarr; new'),
        'rating_score DESC'   => l10n('Rating score, high &rarr; low'),
        'rating_score ASC'    => l10n('Rating score, low &rarr; high'),
        'hit DESC'            => l10n('Visits, high &rarr; low'),
        'hit ASC'             => l10n('Visits, low &rarr; high'),
        'id ASC'              => l10n('Numeric identifier, 1 &rarr; 9'),
        'id DESC'             => l10n('Numeric identifier, 9 &rarr; 1'),
      ),
      'example' => array('value' => 50, 'cond' => 'date_creation DESC'),
    ),
  );
}