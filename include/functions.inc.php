<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

/*
 * Associates images to the category according to the filters
 * @param int category_id
 * @return array
 */
function smart_make_associations($cat_id)
{
  pwg_query("DELETE FROM ".IMAGE_CATEGORY_TABLE." WHERE category_id = ".$cat_id." AND smart = true;");
  
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
    set_random_representant(array($cat_id));
  }
  
  return $images;
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
  if ($filters == null)
  {
    $filters = pwg_query("SELECT * FROM ".CATEGORY_FILTERS_TABLE." WHERE category_id = ".$cat_id." ORDER BY type ASC, cond ASC;");
    if (!pwg_db_num_rows($filters)) return array();
    
    while ($filter = pwg_db_fetch_assoc($filters))
    {
      $temp[] = array(
        'type' => $filter['type'],
        'cond' => $filter['cond'],
        'value' => $filter['value'],
      );
    }
     
    $filters = $temp;
  }
    
  /* build constrains */
  ## generate 'join', 'where' arrays and 'limit' string to create the SQL query
  ## inspired by PicsEngine by Michael Villar
  $i_tags = 1;
  foreach ($filters as $filter)
  {
    // tags
    if ($filter['type'] == 'tags')
    {
        if($filter['cond'] == "all")
        {
          $tags_arr = explode(',', $filter['value']);
          
          foreach($tags_arr as $value)
          {
            $join[] = "".IMAGE_TAG_TABLE." AS it_$i_tags ON i.id = it_$i_tags.image_id";
            $where[] = "it_$i_tags.tag_id = ".$value."";
            $i_tags++;
          }
        }
        else if ($filter['cond'] == 'one') 
        {
          $join[] = "".IMAGE_TAG_TABLE." AS it_$i_tags ON i.id = it_$i_tags.image_id";
          $where[] = "it_$i_tags.tag_id IN (".$filter['value'].")";
          $i_tags++;
        }
        else if ($filter['cond'] == 'none') 
        {
          $sub_query = "SELECT it_$i_tags.image_id
            FROM ".IMAGE_TAG_TABLE." AS it_$i_tags
            WHERE it_$i_tags.image_id = i.id
            AND it_$i_tags.tag_id IN (".$filter['value'].")
            GROUP BY it_$i_tags.image_id";
          $where[] = "NOT EXISTS (".$sub_query.")";
          $i_tags++;
        }
        else if ($filter['cond'] == 'only') 
        {
          $sub_query = "SELECT it_$i_tags.image_id
            FROM ".IMAGE_TAG_TABLE." AS it_$i_tags
            WHERE it_$i_tags.image_id = i.id
            AND it_$i_tags.tag_id NOT IN (".$filter['value'].")
            GROUP BY it_$i_tags.image_id";
          $where[] = "NOT EXISTS (".$sub_query.")";
        
          $i_tags++;
          $tags_arr = explode(',', $filter['value']);
          
          foreach($tags_arr as $value)
          {
            $join[] = "".IMAGE_TAG_TABLE." AS it_$i_tags ON i.id = it_$i_tags.image_id";
            $where[] = "it_$i_tags.tag_id = ".$value."";
            $i_tags++;
          }
        }        
    }
    // date
    else if ($filter['type'] == 'date')
    {
      if ($filter['cond'] == 'the')         $where[] = "date_available BETWEEN '".$filter['value']." 00:00:00' AND '".$filter['value']." 23:59:59'";
      else if ($filter['cond'] == 'before') $where[] = "date_available < '".$filter['value']." 00:00:00'";
      else if ($filter['cond'] == 'after')  $where[] = "date_available > '".$filter['value']." 23:59:59'";
    }
    // limit
    else if ($filter['type'] == 'limit')
    {
      $limit = "0, ".$filter['value'];
    }
  }
  
  /* bluid query */
  $MainQuery = "SELECT i.id 
    FROM (
      SELECT i.id
      FROM ".IMAGES_TABLE." AS i"."\n";
      
      if (isset($join))
      {
        foreach ($join as $query)
        {
          $MainQuery .= "LEFT JOIN ".$query."\n";
        }
      }
      if (isset($where))
      {
        $MainQuery .= "WHERE"."\n";
        $i = 0;
        foreach ($where as $query)
        {
          if ($i != 0) $MainQuery .= "AND ";
          $MainQuery .= $query."\n";
          $i++;
        }
      }
  
      $MainQuery .= "GROUP BY i.id
      ".$conf['order_by']."
      ".(isset($limit) ? "LIMIT ".$limit : null)."
    ) AS i
  ";
  
  return array_from_query($MainQuery, 'id');
}


/**
 * Check if the filter is proper
 *
 * @param array filter
 * @return array or false
 */
function smart_check_filter($filter)
{
  global $limit_is_set, $page;
  $error = false;
  
  # tags
  if ($filter['type'] == 'tags')
  {
    if ($filter['value'] == null) // tags fields musn't be null
    {
      $error = true;
      array_push($page['errors'], l10n('No tag selected'));
    }
    else
    {
      $filter['value'] = implode(',', get_fckb_tag_ids($filter['value']));
    }
  }
  # date
  else if ($filter['type'] == 'date')
  {
    if (!preg_match('#([0-9]{4})-([0-9]{2})-([0-9]{2})#', $filter['value'])) // dates must be proper
    {
      $error = true;
      array_push($page['errors'], l10n('Date string is malformed'));
    }
  }
  # limit
  else if ($filter['type'] == 'limit')
  {
    if (!preg_match('#([0-9]{1,})#', $filter['value'])) // limit must be an integer
    {
      $error = true;
      array_push($page['errors'], l10n('Limit must be an integer'));
    }
    else if ($limit_is_set == true) // only one limit is allowed, first is saved
    {
      $error = true;
      array_push($page['errors'], l10n('You can\'t use more than one limit'));
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
 *
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
?>