{combine_css path=$SMART_PATH|@cat:"admin/template/style.css"}
{include file='include/datepicker.inc.tpl'}
{combine_script id='jquery.tokeninput' load='async' require='jquery' path='themes/default/js/plugins/jquery.tokeninput.js'}

{footer_script require='jquery.tokeninput'}
var lang = new Array();
var options = new Array();

{foreach from=$options item=details key=mode}
  lang['{$mode}_filter'] = '{$mode|cat:'_filter'|@translate|escape:javascript}';
  {capture assign="option_content"}{html_options options=$details}{/capture}
  options['{$mode}'] = "{$option_content|escape:javascript}";
{/foreach}

lang['For "Is (not) in", separate each author by a comma'] = '{'For "Is (not) in", separate each author by a comma'|@translate|escape:javascript}';
lang['remove this filter'] = '{'remove this filter'|@translate|escape:javascript}';
{capture assign="option_content"}{html_options options=$level_options selected=0}{/capture}
options['level_options'] = "{$option_content|escape:javascript}";

{literal}
$('#addFilter').change(function() {
  add_filter($(this).attr('value'));
  $(this).attr('value', '-1');
});
  
$('#removeFilters').click(function() {
  $('#filterList li').each(function() {
    $(this).remove();
  });
  return false;
});

$('input[name="is_smart"]').change(function() {
  $('#SmartAlbum_options').toggle();
  $('input[name="countImages"]').toggle();
  $('.count_images_wrapper').toggle();
});

$('input[name="countImages"]').click(function() {
  countImages($("#smart"));
  return false;
});


function add_filter(type) {
  // add line
  $('<li class="filter_'+ type +'" id="filter_'+ i +'"></li>').appendTo('#filterList');
  
  //set content
  content = 
    ' <span class="filter-title">'+
      ' <a href="#" class="removeFilter" title="'+ lang['remove this filter'] +'"><span>[x]</span></a>'+
      ' <input type="hidden" name="filters['+ i +'][type]" value="'+ type +'"/>&nbsp;'+ lang[type +'_filter'] +
    '</span>'+
    ' <span class="filter-cond">'+
      ' <select name="filters['+ i +'][cond]">'+ options[type] +'</select>'+
    '</span>'+
    ' <span class="filter-value">';
  
  if (type == 'tags')
    content+= ' <select name="filters['+ i +'][value]" class="tagSelect"></select>';
  else if (type == 'level')
    content+= ' <select name="filters['+ i +'][value]">'+ options['level_options'] +'</select>';
  else
    content+= ' <input type="text" name="filters['+ i +'][value]" size="30"/>';
  
  if (type == 'author')
    content+= ' <i>'+ lang['For "Is (not) in", separate each author by a comma'] +'</i>';
    
  content+= '</span>';
  
  $('#filter_'+ i).html(content);
  
  // reinit handlers
  init_jquery_handlers();
  i++;
}

function init_jquery_handlers() {  
  $('.removeFilter').click(function() {
    $(this).parents('li').remove();
    return false;
  });

  $('.filter_date input[type="text"]').each(function() {
    $(this).datepicker({dateFormat:'yy-mm-dd', firstDay:1});
  });
  
  jQuery(".tagSelect").tokenInput(
  {/literal}
    [{foreach from=$all_tags item=tag name=tags}{ldelim}"name":"{$tag.name|@escape:'javascript'}","id":"{$tag.id}"{rdelim}{if !$smarty.foreach.tags.last},{/if}{/foreach}],
    {ldelim}
      hintText: '{'Type in a search term'|@translate}',
      noResultsText: '{'No results'|@translate}',
      searchingText: '{'Searching...'|@translate}',
      animateDropdown: false,
      preventDuplicates: true,
      allowCreation: false
  {literal}
    }
  );
}

function countImages(form) {
{/literal}
  jQuery.post("{$COUNT_SCRIPT_URL}", 'cat_id={$CAT_ID}&'+form.serialize(),
{literal}
    function success(data) {
      jQuery('.count_images_wrapper').html(data);
    }
  );
}

function doBlink(obj,start,finish) { 
  jQuery(obj).fadeOut(400).fadeIn(400); 
  if(start!=finish) { 
    doBlink(obj,start+1,finish);
  } else {
    jQuery(obj).fadeOut(400);
  }
}
  
init_jquery_handlers();
{/literal}
{if isset($new_smart)}doBlink('.new_smart', 0, 3);{/if}
{/footer_script}

<div class="titrePage">
  <h2><span style="letter-spacing:0">{$CATEGORIES_NAV}</span> &#8250; {'Edit album'|@translate} [SmartAlbum]</h2>
</div>

<div id="batchManagerGlobal">
<form action="{$F_ACTION}" method="POST" id="smart">
  <p style="text-align:left;"><label><input type="checkbox" name="is_smart" {if isset($filters) OR isset($new_smart)}checked="checked"{/if}/> {'This album is a SmartAlbum'|@translate}</label></p>

  <fieldset id="SmartAlbum_options" style="margin-top:1em;{if !isset($filters) AND !isset($new_smart)}display:none;{/if}">
    <legend>{'Filters'|@translate}</legend>
      
    <ul id="filterList">
      {counter start=0 assign=i}
      {foreach from=$filters item=filter}
        <li class="filter_{$filter.TYPE}" id="filter_{$i}">
          <span class="filter-title">
            <a href="#" class="removeFilter" title="{'remove this filter'|@translate}"><span>[x]</span></a>
            <input type="hidden" name="filters[{$i}][type]" value="{$filter.TYPE}"/>
            {$filter.TYPE|cat:'_filter'|@translate}
          </span>
          
          <span class="filter-cond">
            <select name="filters[{$i}][cond]">
              {html_options options=$options[$filter.TYPE] selected=$filter.COND}
            </select>
          </span>
          
          <span class="filter-value">
          {if $filter.TYPE == 'tags'}
            <select name="filters[{$i}][value]" class="tagSelect">
            {foreach from=$filter.VALUE item=tag}
              <option value="{$tag.id}" class="selected">{$tag.name}</option>
            {/foreach}
            </select>
          {elseif $filter.TYPE == 'level'}
            <select name="filters[{$i}][value]">
              {html_options options=$level_options selected=$filter.VALUE}
            </select>
          {else}
            <input type="text" name="filters[{$i}][value]" value="{$filter.VALUE}" size="30"/>
          {/if}
          {if $filter.TYPE == 'author'}
            <i>{'For "Is (not) in", separate each author by a comma'|@translate}</i>
          {/if}
          </span>
        </li>
        {counter}
      {/foreach}
      
      {footer_script}var i={$i};{/footer_script}
    </ul>

    <p class="actionButtons">
        <select id="addFilter">
          <option value="-1">{'Add a filter'|@translate}</option>
          <option disabled="disabled">------------------</option>
          <option value="tags">{'tags_filter'|@translate}</option>
          <option value="date">{'date_filter'|@translate}</option>
          <option value="name">{'name_filter'|@translate}</option>
          <option value="author">{'author_filter'|@translate}</option>
          <option value="hit">{'hit_filter'|@translate}</option>
          <option value="level">{'level_filter'|@translate}</option>
          <option value="limit">{'limit_filter'|@translate}</option>
        </select>
        <a id="removeFilters">{'Remove all filters'|@translate}</a>
        {if isset($new_smart)}<span class="new_smart">{'Add filters here'|@translate}</span>{/if}
    </p>
  </fieldset>

  <p class="actionButtons" id="applyFilterBlock">
    <input class="submit" type="submit" value="{'Submit'|@translate}" name="submitFilters"/>
    <input class="submit" type="submit" value="{'Count'|@translate}" name="countImages" {if !isset($filters) AND !isset($new_smart)}style="display:none;"{/if}/>
    <span class="count_images_wrapper" {if !isset($filters) AND !isset($new_smart)}style="display:none;"{/if}><span class="count_image">{$IMAGE_COUNT}</span></span>
  </p>

</form>
</div>