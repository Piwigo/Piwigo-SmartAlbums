<?php
// +-----------------------------------------------------------------------+
// | Piwigo - a PHP based photo gallery                                    |
// +-----------------------------------------------------------------------+
// | Copyright(C) 2008-2012 Piwigo Team                  http://piwigo.org |
// | Copyright(C) 2003-2008 PhpWebGallery Team    http://phpwebgallery.net |
// | Copyright(C) 2002-2003 Pierrick LE GALL   http://le-gall.net/pierrick |
// +-----------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify  |
// | it under the terms of the GNU General Public License as published by  |
// | the Free Software Foundation                                          |
// |                                                                       |
// | This program is distributed in the hope that it will be useful, but   |
// | WITHOUT ANY WARRANTY; without even the implied warranty of            |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU      |
// | General Public License for more details.                              |
// |                                                                       |
// | You should have received a copy of the GNU General Public License     |
// | along with this program; if not, write to the Free Software           |
// | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, |
// | USA.                                                                  |
// +-----------------------------------------------------------------------+

if(!defined("PHPWG_ROOT_PATH")) die ("Hacking attempt!");

// +-----------------------------------------------------------------------+
// | Basic checks                                                          |
// +-----------------------------------------------------------------------+

check_status(ACCESS_ADMINISTRATOR);

check_input_parameter('cat_id', $_GET, false, PATTERN_ID);

$admin_album_base_url = get_root_url().'admin.php?page=album-'.$_GET['cat_id'];
$self_url = SMART_ADMIN.'-album&amp;cat_id='.$_GET['cat_id'];

$query = '
SELECT *
  FROM '.CATEGORIES_TABLE.'
  WHERE id = '.$_GET['cat_id'].'
;';
$category = pwg_db_fetch_assoc(pwg_query($query));

if (!isset($category['id']))
{
  die("unknown album");
}

// +-----------------------------------------------------------------------+
// | Tabs                                                                  |
// +-----------------------------------------------------------------------+

include_once(PHPWG_ROOT_PATH.'admin/include/tabsheet.class.php');
$tabsheet = new tabsheet();
$tabsheet->add('properties', l10n('Properties'), $admin_album_base_url.'-properties');
$tabsheet->add('sort_order', l10n('Manage photo ranks'), $admin_album_base_url.'-sort_order');
$tabsheet->add('permissions', l10n('Permissions'), $admin_album_base_url.'-permissions');
$tabsheet->add('notification', l10n('Notification'), $admin_album_base_url.'-notification');
$tabsheet->add('smartalbum', 'SmartAlbum', $self_url);
$tabsheet->select('smartalbum');
$tabsheet->assign();


$cat_id = $_GET['cat_id'];

// category must be virtual
if ($category['dir'] != NULL)
{
  die("physical album");
}

// +-----------------------------------------------------------------------+
// | Save Filters                                                          |
// +-----------------------------------------------------------------------+

if (isset($_POST['submitFilters']))
{
  // test if it was a Smart Album
  $query = '
SELECT DISTINCT category_id 
  FROM '.CATEGORY_FILTERS_TABLE.' 
  WHERE category_id = '.$cat_id.'
;';
  $was_smart = pwg_db_num_rows(pwg_query($query));
  
  /* this album is no longer a SmartAlbum */
  if ( $was_smart AND !isset($_POST['is_smart']) )
  {
    pwg_query('DELETE FROM '.IMAGE_CATEGORY_TABLE.' WHERE category_id = '.$cat_id.' AND smart = true;');
    pwg_query('DELETE FROM '.CATEGORY_FILTERS_TABLE.' WHERE category_id = '.$cat_id.';');
    set_random_representant(array($cat_id));
    invalidate_user_cache();
  }
  /* no filter selected */
  else if ( isset($_POST['is_smart']) AND empty($_POST['filters']) )
  {
    array_push($page['errors'], l10n('No filter selected'));
  }
  /* everything is fine */
  else if ( isset($_POST['is_smart']) )
  {
    pwg_query('DELETE FROM '.CATEGORY_FILTERS_TABLE.' WHERE category_id = '.$cat_id.';');
    
    foreach ($_POST['filters'] as $filter)
    {
      if (($filter = smart_check_filter($filter)) != false)
      {
        $query = '
INSERT IGNORE INTO '.CATEGORY_FILTERS_TABLE.' 
  VALUES(
    '.$cat_id.', 
    "'.$filter['type'].'", 
    "'.$filter['cond'].'", 
    "'.$filter['value'].'"
  )
;';
      pwg_query($query);
      }
    }
    
    $associated_images = smart_make_associations($cat_id);
    $template->assign('IMAGE_COUNT', l10n_dec('%d photo', '%d photos', count($associated_images)));
    
    array_push($page['infos'], sprintf('%d photos associated to the album %s', count($associated_images), ''));
    
    define('SMART_NOT_UPDATE', 1);
    invalidate_user_cache();
  }
}

// +-----------------------------------------------------------------------+
// | Display page                                                          |
// +-----------------------------------------------------------------------+

/* select options, for html_options */
$options = array(
  'tags' => array(
    'name' => l10n('Tags'),
    'options' => array(
      'all'   => l10n('All these tags'),
      'one'   => l10n('One of these tags'),
      'none'  => l10n('None of these tags'),
      'only'  => l10n('Only these tags'),
      ),
    ),
  'date' => array(
    'name' => l10n('Date'),
    'options' => array(
      'the_post'      => l10n('Added on'),
      'before_post'   => l10n('Added before'),
      'after_post'    => l10n('Added after'),
      'the_taken'     => l10n('Created on'),
      'before_taken'  => l10n('Created before'),
      'after_taken'   => l10n('Created after'),
      ),
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
      ),
    ),
  'author' => array(
    'name' => l10n('Author'),
    'options' => array(
      'is'      => l10n('Is'),
      'in'      => l10n('Is in'),
      'not_is'  => l10n('Is not'),
      'not_in'  => l10n('Is not in'),
      ),
    ),
  'hit' => array(
    'name' => l10n('Hits'),
    'options' => array(
      'less' => l10n('Bellow'),
      'more' => l10n('Above'),
      ),
    ),
  'rating_score' => array(
    'name' => l10n('Rating score'),
    'options' => array(
      'less' => l10n('Bellow'),
      'more' => l10n('Above'),
      ),
    ),
  'level' => array(
    'name' => l10n('Privacy level'),
    'options' => array(),
    ),
  'limit' => array(
    'name' => l10n('Max. number of photos'),
    'options' => array(),
    ),
  );
$template->assign('options', $options);

/* get filters for this album */
$query = '
SELECT * 
  FROM '.CATEGORY_FILTERS_TABLE.' 
  WHERE category_id = '.$cat_id.' 
  ORDER BY 
    type ASC, 
    cond ASC
;';
$result = pwg_query($query);

while ($filter = pwg_db_fetch_assoc($result))
{
  // get tags name and id
  if ($filter['type'] == 'tags')
  {
    $query = '
SELECT
    id,
    name
  FROM '.TAGS_TABLE.'
  WHERE id IN('.$filter['value'].')
;';
    $filter['value'] = get_taglist($query); 
  }
  
  $template->append('filters', array(
    'TYPE' => $filter['type'],
    'COND' => $filter['cond'],
    'VALUE' => $filter['value'],
    'CAPTION' => $options[ $filter['type'] ]['name'],
  ));
}

/* all tags */
$query = '
SELECT
    id,
    name
  FROM '.TAGS_TABLE.'
;';
$all_tags = get_taglist($query);

/* get image number */
if ($template->get_template_vars('IMAGE_COUNT') == null)
{
  $query = '
SELECT count(1) 
  FROM '.IMAGE_CATEGORY_TABLE.' 
  WHERE 
    category_id = '.$cat_id.' 
    AND smart = true
';

  list($image_num) = pwg_db_fetch_row(pwg_query($query));
  $template->assign('IMAGE_COUNT', l10n_dec('%d photo', '%d photos', $image_num));
}

if (isset($_GET['new_smart']))
{
  $template->assign('new_smart', true);
}

$template->assign(array(
  'COUNT_SCRIPT_URL' => SMART_PATH.'include/count_images.php',
  'all_tags' => $all_tags,
  'level_options' => get_privacy_level_options(),
  'F_ACTION' => $self_url,
  'CATEGORIES_NAV' => get_cat_display_name_cache(
    $category['uppercats'],
    SMART_ADMIN.'-album&amp;cat_id='
    ),
));

$template->set_filename('SmartAlbums_content', dirname(__FILE__).'/template/album.tpl');

?>