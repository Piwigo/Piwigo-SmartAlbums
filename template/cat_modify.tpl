{combine_css path=$SMART_PATH|@cat:"template/style.css"}
{include file='include/datepicker.inc.tpl'}
{combine_script id='jquery.tokeninput' load='async' require='jquery' path='themes/default/js/plugins/jquery.tokeninput.js'}

{footer_script require='jquery.tokeninput'}
var lang = new Array();
lang['tags filter'] = "{'tags filter'|@translate}";
lang['date filter'] = "{'date filter'|@translate}";
lang['limit filter'] = "{'limit filter'|@translate}";
lang['remove this filter'] = "{'remove this filter'|@translate}";

var options = new Array();
{capture assign="options_tags"}{html_options options=$options.tags}{/capture}
{capture assign="options_date"}{html_options options=$options.date}{/capture}
{capture assign="options_limit"}{html_options options=$options.limit}{/capture}
options['tags'] = "{$options_tags|escape:javascript}";
options['date'] = "{$options_date|escape:javascript}";
options['limit'] = "{$options_limit|escape:javascript}";

{literal}
jQuery(document).ready(function() {
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
  });
  
  $('input[name="countImages"]').click(function() {
    countImages($(this).closest('form'));
    return false;
  });
  
  function add_filter(type) {
    // add line
    $('<li class="filter_'+ type +'" id="filter_'+ i +'"></li>').appendTo('#filterList');
    
    //set content
    content = '<a href="#" class="removeFilter" title="'+ lang['remove this filter'] +'"><span>[x]</span></a>'+
    '<input type="hidden" name="filters['+ i +'][type]" value="'+ type +'"/>&nbsp;'+ lang[type +' filter'] +
    '&nbsp;<select name="filters['+ i +'][cond]">'+ options[type] +'</select>';
    
    if (type == 'tags') {
      content += '&nbsp;<select name="filters['+ i +'][value]" class="tagSelect"></select>';
    } else {
      content += '&nbsp;<input type="text" name="filters['+ i +'][value]"/>';
    }
    
    $('#filter_'+ i).html(content);
    
    // reinit handlers
    init_jquery_handlers();
    i++;
  }
  
  function init_jquery_handlers() {  
    $('.removeFilter').click(function() {
      $(this).parent('li').remove();
      return false;
    });
  
    $('.filter_date input[type="text"]').each(function() {
      $(this).datepicker({dateFormat:'yy-mm-dd', firstDay:1});
    });
    
    jQuery(".tagSelect").tokenInput(
    {/literal}
      [{foreach from=$tags item=tag name=tags}{ldelim}"name":"{$tag.name|@escape:'javascript'}","id":"{$tag.id}"{rdelim}{if !$smarty.foreach.tags.last},{/if}{/foreach}],
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
				jQuery('.count_images_display').html(data);
			}
		);
	}
  
  init_jquery_handlers();
});
{/literal}
{/footer_script}

<div id="batchManagerGlobal">
<form action="{$F_ACTION}" method="POST" id="smart">
<fieldset>

  <legend>{'SmartAlbums'|@translate}</legend>
  
  <label><input type="checkbox" name="is_smart" {if isset($filters)}checked="checked"{/if}/> {'This album is a SmartAlbum'|@translate}</label>
  
<div id="SmartAlbum_options" style="margin-top:1em;{if !isset($filters)}display:none;{/if}">
  <ul id="filterList">
    {counter start=0 assign=i}
    {foreach from=$filters item=filter}
      <li class="filter_{$filter.TYPE}" id="filter_{$i}">
        <a href="#" class="removeFilter" title="{'remove this filter'|@translate}"><span>[x]</span></a>
        <input type="hidden" name="filters[{$i}][type]" value="{$filter.TYPE}"/>
        {$filter.TYPE|cat:' filter'|@translate}
        
        <select name="filters[{$i}][cond]">
          {html_options options=$options[$filter.TYPE] selected=$filter.COND}
        </select>
        
      {if $filter.TYPE == 'tags'}
        <select name="filters[{$i}][value]" class="tagSelect">
        {foreach from=$filter.VALUE item=tag}
          <option value="{$tag.id}" class="selected">{$tag.name}</option>
        {/foreach}
        </select>
      {else}
        <input type="text" name="filters[{$i}][value]" value="{$filter.VALUE}"/>
      {/if}
      </li>
			{counter}
		{/foreach}
    
    {footer_script}var i={$i};{/footer_script}
  </ul>

  <p class="actionButtons">
      <select id="addFilter">
        <option value="-1">{'Add a filter'|@translate}</option>
        <option disabled="disabled">------------------</option>
        <option value="tags">{'tags filter'|@translate}</option>
        <option value="date">{'date filter'|@translate}</option>
        <option value="limit">{'limit filter'|@translate}</option>
      </select>
      <a id="removeFilters">{'Remove all filters'|@translate}</a>
  </p>
</div>
    
  <p class="actionButtons" id="applyFilterBlock">
    <input class="submit" type="submit" value="{'Submit'|@translate}" name="submitFilters"/>
    <input class="submit" type="submit" value="{'Count'|@translate}" name="countImages" {if !isset($filters)}style="display:none;"{/if}/>
    <span class="count_images_display">{$IMAGE_COUNT}</span>
  </p>

</fieldset>
</form>
</div>