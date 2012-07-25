<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

/*
 * Associates images to the category according to the filters
 * @param int category_id
 * @return array
 */
function smart_make_associations($cat_id)
{
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
    mass_inserts_ignore(
      IMAGE_CATEGORY_TABLE, 
      array_keys($datas[0]), 
      $datas
      );
  }
  
  // representant, try to not overwrite if still in images list
  $query = '
SELECT representative_picture_id
  FROM '.CATEGORIES_TABLE.'
  WHERE id = '.$cat_id.'
;';
  list($rep_id) = pwg_db_fetch_row(pwg_query($query));
  
  if ( !in_array($rep_id, $images) )
  {
    include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');
    set_random_representant(array($cat_id));
  }
  
  return $images;
}


/*
 * Make associations for all SmartAlbums
 * Called with invalidate_user_cache
 */
function smart_make_all_associations()
{
  global $conf;
  
  if ( defined('SMART_NOT_UPDATE') OR !$conf['SmartAlbums']['update_on_upload'] ) return;
  
  /* get categories with smart filters */
  $query = '
SELECT DISTINCT id
  FROM '.CATEGORIES_TABLE.' AS c
    INNER JOIN '.CATEGORY_FILTERS_TABLE.' AS cf
    ON c.id = cf.category_id
;';
  
  /* regenerate photo list */
  $smart_cats = array_from_query($query, 'id');
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
  if ($filters === null)
  {
    $query = '
SELECT * 
  FROM '.CATEGORY_FILTERS_TABLE.' 
  WHERE category_id = '.$cat_id.' 
  ORDER BY type ASC, cond ASC
;';
    $result = pwg_query($query);
    
    if (!pwg_db_num_rows($result)) return array();
    
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
    
  /* build constrains */
  ## generate 'join', 'where' arrays and 'limit' string to create the SQL query
  ## inspired by PicsEngine 3 by Michael Villar
  $i_tags = 1;
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
            foreach($tags_arr as $value)
            {
              $join[] = IMAGE_TAG_TABLE.' AS it'.$i_tags.' ON i.id = it'.$i_tags.'.image_id';
              $where[] = 'it'.$i_tags.'.tag_id = '.$value;
              $i_tags++;
            }
            
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
        }
        
        break;
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
        break;
      }
    }
  }
  
  /* bluid query */
  $MainQuery = '
SELECT i.id
  FROM '.IMAGES_TABLE.' AS i';
    
    if (isset($join))
    {
      $MainQuery.= '
    LEFT JOIN '.implode("\n    LEFT JOIN ", $join);
    }
    if (isset($where))
    {
      $MainQuery.= '
  WHERE
    '.implode("\n    AND ", $where);
    }

  $MainQuery.= '
  GROUP BY i.id
  '.$conf['order_by'].'
  '.(isset($limit) ? "LIMIT ".$limit : null).'
;';

  // file_put_contents(SMART_PATH.'query.sql', $MainQuery);
  return array_from_query($MainQuery, 'id');
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
  
  # tags
  if ($filter['type'] == 'tags')
  {
    if ($filter['value'] == null)
    {
      $error = true;
      array_push($page['errors'], l10n('No tag selected'));
    }
    else
    {
      $filter['value'] = implode(',', get_tag_ids($filter['value']));
    }
  }
  # date
  else if ($filter['type'] == 'date')
  {
    if (!preg_match('#([0-9]{4})-([0-9]{2})-([0-9]{2})#', $filter['value']))
    {
      $error = true;
      array_push($page['errors'], l10n('Date string is malformed'));
    }
  }
  # name
  else if ($filter['type'] == 'name')
  {
    if (empty($filter['value']))
    {
      $error = true;
      array_push($page['errors'], l10n('Name is empty'));
    }
  }
  # author
  else if ($filter['type'] == 'author')
  {
    if (empty($filter['value']))
    {
      $error = true;
      array_push($page['errors'], l10n('Author is empty'));
    }
    else
    {
      $filter['value'] = preg_replace('#([ ]?),([ ]?)#', ',', $filter['value']);
    }
  }
  # hit
  else if ($filter['type'] == 'hit')
  {
    if (!preg_match('#([0-9]{1,})#', $filter['value']))
    {
      $error = true;
      array_push($page['errors'], l10n('Hits must be an integer'));
    }
  }
  # rating_score
  else if ($filter['type'] == 'rating_score')
  {
    if (!preg_match('#([0-9]{1,})#', $filter['value']))
    {
      $error = true;
      array_push($page['errors'], l10n('Rating score must be an integer'));
    }
  }
  # level
  else if ($filter['type'] == 'level')
  {
    if ($level_is_set == true) // only one level is allowed, first is saved
    {
      $error = true;
      array_push($page['errors'], l10n('You can\'t use more than one level filter'));
    }
    else
    {
      $level_is_set = true;
    }
  }
  # limit
  else if ($filter['type'] == 'limit')
  {
    if (!preg_match('#([0-9]{1,})#', $filter['value']))
    {
      $error = true;
      array_push($page['errors'], l10n('Limit must be an integer'));
    }
    else if ($limit_is_set == true) // only one limit is allowed, first is saved
    {
      $error = true;
      array_push($page['errors'], l10n('You can\'t use more than one limit filter'));
    }
    else
    {
      $limit_is_set = true;
    }
  }
  
  # out
  if ($error == false)
  {
    return $filter;
  }
  else
  {
    return false;
  }
}


/**
 * inserts multiple lines in a table, ignore duplicate entries
 * @param string table_name
 * @param array dbfields
 * @param array inserts
 * @return void
 */
function mass_inserts_ignore($table_name, $dbfields, $datas)
{
  if (count($datas) != 0)
  {
    $first = true;

    $query = 'SHOW VARIABLES LIKE \'max_allowed_packet\'';
    list(, $packet_size) = pwg_db_fetch_row(pwg_query($query));
    $packet_size = $packet_size - 2000; // The last list of values MUST not exceed 2000 character*/
    $query = '';

    foreach ($datas as $insert)
    {
      if (strlen($query) >= $packet_size)
      {
        pwg_query($query);
        $first = true;
      }

      if ($first)
      {
        $query = '
INSERT IGNORE INTO '.$table_name.'
  ('.implode(',', $dbfields).')
  VALUES';
        $first = false;
      }
      else
      {
        $query .= '
  , ';
      }

      $query .= '(';
      foreach ($dbfields as $field_id => $dbfield)
      {
        if ($field_id > 0)
        {
          $query .= ',';
        }

        if (!isset($insert[$dbfield]) or $insert[$dbfield] === '')
        {
          $query .= 'NULL';
        }
        else
        {
          $query .= "'".$insert[$dbfield]."'";
        }
      }
      $query .= ')';
    }
    pwg_query($query);
  }
}

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

?>