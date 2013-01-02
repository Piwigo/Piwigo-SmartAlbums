{combine_css path=$SMART_PATH|@cat:"admin/template/style.css"}
{combine_script id='sprintf' load='footer' path=$SMART_PATH|@cat:"admin/template/sprintf.js"}
{include file='include/datepicker.inc.tpl'}
{combine_script id='jquery.tokeninput' load='footer' require='jquery' path='themes/default/js/plugins/jquery.tokeninput.js'}
{combine_css path="themes/default/js/plugins/chosen.css"}
{combine_script id='jquery.chosen' load='footer' path='themes/default/js/plugins/chosen.jquery.min.js'}
{combine_css path="themes/default/js/ui/theme/jquery.ui.slider.css"}
{combine_script id='jquery.ui.slider' require='jquery.ui' load='footer' path='themes/default/js/ui/minified/jquery.ui.slider.min.js'}

{footer_script}{literal}
var count=0;
var limit_count=0;
var level_count=0;

// MAIN EVENT HANDLERS
$('#addFilter').change(function() {
  add_filter($(this).attr('value'));
  $(this).attr('value', '-1');
});
  
$('#removeFilters').click(function() {
  $('#filtersList li').each(function() {
    $(this).remove();
  });
  
  limit_level=0;
  level_count=0;
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


// ADD FILTER FUNCTIONS
function add_filter(type, cond, value) {
  count++;
  
  content = $("#filtersRepo #filter_"+type).html().replace(/iiii/g, count);
  $block = $(content);
  $("#filtersList").append($block);
  
  if (cond) {
    select_cond($block, type, cond);
  }
  
  if (value) {
    if (type == "tags") {
      $block.find(".filter-value .tagSelect").html(value);
    }
    else if (type == "album") {
      select_options($block, value);
    }
    else if (type == "level") {
      select_options($block, value);
    }
    else if (type != "dimensions") {
      $block.find(".filter-value input").val(value);
    }
  }
  
  init_jquery_handlers($block);
  
  if (type == "dimensions") {
    select_dimensions($block, cond, value);
  }
  
  if (type == 'limit') {
    limit_count=1;
    $("#addFilter option[value='limit']").attr('disabled','disabled');
  }
  else if (type == 'level') {
    level_count=1;
    $("#addFilter option[value='level']").attr('disabled','disabled');
  }
}

function select_cond($block, type, cond) {
  $block.find(".filter-cond option").removeAttr('selected');
  $block.find(".filter-cond option[value='"+cond+"']").attr('selected', 'selected');
}

function select_dimensions($block, cond, value) {
  if (!cond) cond = 'width';
  
  $block.find(".filter-value span:not(.filter_dimension_info)").hide();
  $block.find(".filter-value .dimension_"+cond).show();
  
  if (value) {
    values = value.split(',');
  }
  else {
    values = $block.find(".filter_dimension_"+cond+"_slider").slider("values");
  }
  $block.find(".filter_dimension_"+cond+"_slider").slider("values", values);
}

function select_options($block, value) {  
  values = value.split(',');
  for (j in values) {
    $block.find(".filter-value option[value='"+ values[j] +"']").attr('selected', 'selected');
  }
}


// DECLARE JQUERY PLUGINS AND VERSATILE HANDLERS
function init_jquery_handlers($block) {
  // remove filter
  $block.find(".removeFilter").click(function() {
    type = $(this).next("input").val();
    if (type == 'limit') {
      limit_count=1;
      $("#addFilter option[value='limit']").removeAttr('disabled');
    }
    else if (type == 'level') {
      level_count=1;
      $("#addFilter option[value='level']").removeAttr('disabled');
    }
    
    $(this).parents('li').remove();
    return false;
  });

  // date filter
  if ($block.hasClass('filter_date')) {
    $block.find("input[type='text']").each(function() {
      $(this).datepicker({dateFormat:'yy-mm-dd', firstDay:1});
    });
  }

  // tags filter
  if ($block.hasClass('filter_tags')) {
    $block.find(".tagSelect").tokenInput(
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
    });
  }
  
  // album filter
  if ($block.hasClass('filter_album')) {
    $block.find(".albumSelect").chosen();
  }
  
  // dimension filter
  if ($block.hasClass('filter_dimensions')) {
    $block.find(".filter-cond select").change(function() {
      select_dimensions($block, $(this).attr("value"));
    });
    {/literal}
    
    $block.find(".filter_dimension_width_slider").slider({ldelim}
      range: true,
      min: {$dimensions.bounds.min_width},
      max: {$dimensions.bounds.max_width},
      values: [{$dimensions.bounds.min_width}, {$dimensions.bounds.max_width}],
      slide: function(event, ui) {ldelim}
        change_dimension_info($block, ui.values, "{'between %d and %d pixels'|@translate}");
      },
      change: function(event, ui) {ldelim}
        change_dimension_info($block, ui.values, "{'between %d and %d pixels'|@translate}");
      }
    });
    
    $block.find(".filter_dimension_height_slider").slider({ldelim}
      range: true,
      min: {$dimensions.bounds.min_height},
      max: {$dimensions.bounds.max_height},
      values: [{$dimensions.bounds.min_height}, {$dimensions.bounds.max_height}],
      slide: function(event, ui) {ldelim}
        change_dimension_info($block, ui.values, "{'between %d and %d pixels'|@translate}");
      },
      change: function(event, ui) {ldelim}
        change_dimension_info($block, ui.values, "{'between %d and %d pixels'|@translate}");
      }
    });
    
    $block.find(".filter_dimension_ratio_slider").slider({ldelim}
      range: true,
      step: 0.01,
      min: {$dimensions.bounds.min_ratio},
      max: {$dimensions.bounds.max_ratio},
      values: [{$dimensions.bounds.min_ratio}, {$dimensions.bounds.max_ratio}],
      slide: function(event, ui) {ldelim}
        change_dimension_info($block, ui.values, "{'between %.2f and %.2f'|@translate}");
      },
      change: function(event, ui) {ldelim}
        change_dimension_info($block, ui.values, "{'between %.2f and %.2f'|@translate}");
      }
    });
    {literal}
    
    $block.find("a.dimensions-choice").click(function() {
      $block.find(".filter_dimension_"+ $(this).data("type") +"_slider").slider("values", 
        [$(this).data("min"), $(this).data("max")]
      );
    });
  }
}


// GENERAL FUNCTIONS
function change_dimension_info($block, values, text) {
  $block.find("input[name$='[value][min]']").val(values[0]);
  $block.find("input[name$='[value][max]']").val(values[1]);
  $block.find(".filter_dimension_info").html(sprintf(text, values[0], values[1]));
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
{/literal}

{if isset($new_smart)}doBlink('.new_smart', 0, 3);{/if}
{/footer_script}


<div class="titrePage">
  <h2><span style="letter-spacing:0">{$CATEGORIES_NAV}</span> &#8250; {'Edit album'|@translate} [SmartAlbum]</h2>
</div>

<noscript>
<div class="errors"><ul><li>JavaScript required!</li></ul></div>
</noscript>

<div id="batchManagerGlobal">
<form action="{$F_ACTION}" method="POST" id="smart">
  <p style="text-align:left;"><label><input type="checkbox" name="is_smart" {if isset($filters) OR isset($new_smart)}checked="checked"{/if}/> {'This album is a SmartAlbum'|@translate}</label></p>

  <fieldset id="SmartAlbum_options" style="margin-top:1em;{if !isset($filters) AND !isset($new_smart)}display:none;{/if}">
    <legend>{'Filters'|@translate}</legend>
      
    <ul id="filtersList">
    {foreach from=$filters item=filter}{strip}
      {if $filter.type == 'tags'}
        {capture assign='value'}{foreach from=$filter.value item=tag}<option value="{$tag.id}" class="selected">{$tag.name}</option>{/foreach}{/capture}
      {else}
        {assign var='value' value=$filter.value}
      {/if}
      
      {if $filter.type == 'limit'}
        {footer_script}
        limit_count=1;
        $("#addFilter option[value='limit']").attr('disabled','disabled');
        {/footer_script}
      {elseif $filter.type == 'level'}
        {footer_script}
        level_count=1;
        $("#addFilter option[value='level']").attr('disabled','disabled');
        {/footer_script}
      {/if}
      
      {footer_script}add_filter('{$filter.type}', '{$filter.cond}', '{$value|escape:javascript}');{/footer_script}
    {/strip}{/foreach}
    </ul>
    
    <div>
      <b>{'Mode'|@translate} :</b>
      <label><input type="radio" name="filters[0][value]" value="and" {if $filter_mode=='and'}checked="checked"{/if}> AND</label>
      <label><input type="radio" name="filters[0][value]" value="or" {if $filter_mode=='or'}checked="checked"{/if}> OR</label>
      <input type="hidden" name="filters[0][type]" value="mode">
      <input type="hidden" name="filters[0][cond]" value="mode">
    </div>
    
    <p class="actionButtons">
      <select id="addFilter">
        <option value="-1">{'Add a filter'|@translate}</option>
        <option disabled="disabled">------------------</option>
        <option value="tags">{'Tags'|@translate}</option>
        <option value="date">{'Date'|@translate}</option>
        <option value="name">{'Photo name'|@translate}</option>
        <option value="album">{'Album'|@translate}</option>
        <option value="dimensions">{'Dimensions'|@translate}</option>
        <option value="author">{'Author'|@translate}</option>
        <option value="hit">{'Hits'|@translate}</option>
        <option value="rating_score">{'Rating score'|@translate}</option>
        <option value="level">{'Privacy level'|@translate}</option>
        <option value="limit">{'Max. number of photos'|@translate}</option>
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

<div id="filtersRepo" style="display:none;">
  <!-- tags -->
  <div id="filter_tags">
  <li id="filter_iiii" class="filter_tags">
    <span class="filter-title">
      <a href="#" class="removeFilter" title="{'remove this filter'|@translate}"><span>[x]</span></a>
      <input type="hidden" name="filters[iiii][type]" value="tags"/>
      {$options.tags.name}
    </span>
    
    <span class="filter-cond">
      <select name="filters[iiii][cond]">
        {html_options options=$options.tags.options}
      </select>
    </span>
    
    <span class="filter-value">
      <select name="filters[iiii][value]" class="tagSelect">
      </select>
    </span>
  </li>
  </div>
  
  <!-- date -->
  <div id="filter_date">
  <li id="filter_iiii" class="filter_date">
    <span class="filter-title">
      <a href="#" class="removeFilter" title="{'remove this filter'|@translate}"><span>[x]</span></a>
      <input type="hidden" name="filters[iiii][type]" value="date"/>
      {$options.date.name}
    </span>
    
    <span class="filter-cond">
      <select name="filters[iiii][cond]">
        {html_options options=$options.date.options}
      </select>
    </span>
    
    <span class="filter-value">
      <input type="text" name="filters[iiii][value]" size="30"/>
    </span>
  </li>
  </div>
  
  <!-- name -->
  <div id="filter_name">
  <li id="filter_iiii" class="filter_name">
    <span class="filter-title">
      <a href="#" class="removeFilter" title="{'remove this filter'|@translate}"><span>[x]</span></a>
      <input type="hidden" name="filters[iiii][type]" value="name"/>
      {$options.name.name}
    </span>
    
    <span class="filter-cond">
      <select name="filters[iiii][cond]">
        {html_options options=$options.name.options}
      </select>
    </span>
    
    <span class="filter-value">
      <input type="text" name="filters[iiii][value]" size="30"/>
    </span>
  </li>
  </div>
  
  <!-- album -->
  <div id="filter_album">
  <li id="filter_iiii" class="filter_album">
    <span class="filter-title">
      <a href="#" class="removeFilter" title="{'remove this filter'|@translate}"><span>[x]</span></a>
      <input type="hidden" name="filters[iiii][type]" value="album"/>
      {$options.album.name}
    </span>
    
    <span class="filter-cond">
      <select name="filters[iiii][cond]">
        {html_options options=$options.album.options}
      </select>
    </span>
    
    <span class="filter-value">
      <select name="filters[iiii][value][]" class="albumSelect" multiple="multiple" data-placeholder="{'Select albums...'|@translate}">
        {html_options options=$all_albums}
      </select>
    </span>
  </li>
  </div>
  
  <!-- dimensions -->
  <div id="filter_dimensions">
  <li id="filter_iiii" class="filter_dimensions">
    <span class="filter-title">
      <a href="#" class="removeFilter" title="{'remove this filter'|@translate}"><span>[x]</span></a>
      <input type="hidden" name="filters[iiii][type]" value="dimensions"/>
      {$options.dimensions.name}
    </span>

    <span class="filter-cond">
      <select name="filters[iiii][cond]">
        {html_options options=$options.dimensions.options}
      </select>
    </span>

    <span class="filter-value">
      <span class="dimension_width">
      <span class="filter_dimension_info"></span>
        | <a class="dimensions-choice" data-type="width" data-min="{$dimensions.bounds.min_width}" data-max="{$dimensions.bounds.max_width}">{'Reset'|@translate}</a>
        <div class="filter_dimension_width_slider"></div>
      </span>

      <span class="filter-value dimension_height">
      <span class="filter_dimension_info"></span>
        | <a class="dimensions-choice" data-type="height" data-min="{$dimensions.bounds.min_height}" data-max="{$dimensions.bounds.max_height}">{'Reset'|@translate}</a>
        <div class="filter_dimension_height_slider"></div>
      </span>

      <span class="filter-value dimension_ratio">
      <span class="filter_dimension_info"></span>
{if isset($dimensions.ratio_portrait)}
        | <a class="dimensions-choice" data-type="ratio" data-min="{$dimensions.ratio_portrait.min}" data-max="{$dimensions.ratio_portrait.max}">{'Portrait'|@translate}</a>
{/if}
{if isset($dimensions.ratio_square)}
        | <a class="dimensions-choice" data-type="ratio" data-min="{$dimensions.ratio_square.min}" data-max="{$dimensions.ratio_square.max}">{'square'|@translate}</a>
{/if}
{if isset($dimensions.ratio_landscape)}
        | <a class="dimensions-choice" data-type="ratio" data-min="{$dimensions.ratio_landscape.min}" data-max="{$dimensions.ratio_landscape.max}">{'Landscape'|@translate}</a>
{/if}
{if isset($dimensions.ratio_panorama)}
        | <a class="dimensions-choice" data-type="ratio" data-min="{$dimensions.ratio_panorama.min}" data-max="{$dimensions.ratio_panorama.max}">{'Panorama'|@translate}</a>
{/if}
        | <a class="dimensions-choice" data-type="ratio" data-min="{$dimensions.bounds.min_ratio}" data-max="{$dimensions.bounds.max_ratio}">{'Reset'|@translate}</a>
        <div class="filter_dimension_ratio_slider"></div>
      </span>
    </span>

    <input type="hidden" name="filters[iiii][value][min]" value="">
    <input type="hidden" name="filters[iiii][value][max]" value="">
  </li>
  </div>
  
  <!-- author -->
  <div id="filter_author">
  <li id="filter_iiii" class="filter_author">
    <span class="filter-title">
      <a href="#" class="removeFilter" title="{'remove this filter'|@translate}"><span>[x]</span></a>
      <input type="hidden" name="filters[iiii][type]" value="author"/>
      {$options.author.name}
    </span>
    
    <span class="filter-cond">
      <select name="filters[iiii][cond]">
        {html_options options=$options.author.options}
      </select>
    </span>
    
    <span class="filter-value">
      <input type="text" name="filters[iiii][value]" size="30"/>
      <i>{'For "Is (not) in", separate each author by a comma'|@translate}</i>
    </span>
  </li>
  </div>
  
  <!-- hit -->
  <div id="filter_hit">
  <li id="filter_iiii" class="filter_hit">
    <span class="filter-title">
      <a href="#" class="removeFilter" title="{'remove this filter'|@translate}"><span>[x]</span></a>
      <input type="hidden" name="filters[iiii][type]" value="hit"/>
      {$options.hit.name}
    </span>
    
    <span class="filter-cond">
      <select name="filters[iiii][cond]">
        {html_options options=$options.hit.options}
      </select>
    </span>
    
    <span class="filter-value">
      <input type="text" name="filters[iiii][value]" size="5"/>
    </span>
  </li>
  </div>
  
  <!-- rating_score -->
  <div id="filter_rating_score">
  <li id="filter_iiii" class="filter_rating_score">
    <span class="filter-title">
      <a href="#" class="removeFilter" title="{'remove this filter'|@translate}"><span>[x]</span></a>
      <input type="hidden" name="filters[iiii][type]" value="rating_score"/>
      {$options.rating_score.name}
    </span>
    
    <span class="filter-cond">
      <select name="filters[iiii][cond]">
        {html_options options=$options.rating_score.options}
      </select>
    </span>
    
    <span class="filter-value">
      <input type="text" name="filters[iiii][value]" size="5"/>
    </span>
  </li>
  </div>
  
  <!-- level -->
  <div id="filter_level">
  <li id="filter_iiii" class="filter_level">
    <span class="filter-title">
      <a href="#" class="removeFilter" title="{'remove this filter'|@translate}"><span>[x]</span></a>
      <input type="hidden" name="filters[iiii][type]" value="level"/>
      {$options.level.name}
    </span>
    
    <input type="hidden" name="filters[iiii][cond]" value="level"/>
    
    <span class="filter-value">
      <select name="filters[iiii][value]">
        {html_options options=$level_options}
      </select>
    </span>
  </li>
  </div>
  
  <!-- limit -->
  <div id="filter_limit">
  <li id="filter_iiii" class="filter_limit">
    <span class="filter-title">
      <a href="#" class="removeFilter" title="{'remove this filter'|@translate}"><span>[x]</span></a>
      <input type="hidden" name="filters[iiii][type]" value="limit"/>
      {$options.limit.name}
    </span>
    
    <input type="hidden" name="filters[iiii][cond]" value="limit"/>
    
    <span class="filter-value">
      <input type="text" name="filters[iiii][value]" size="5"/>
    </span>
  </li>
  </div>
</div>