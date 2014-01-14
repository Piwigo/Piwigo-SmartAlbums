{combine_css path=$SMART_PATH|cat:'admin/template/style.css'}
{include file='include/datepicker.inc.tpl'}
{combine_script id='common' load='footer' path='admin/themes/default/js/common.js'}

{combine_css path='themes/default/js/plugins/jquery.tokeninput.css'}
{combine_script id='jquery.tokeninput' load='footer' require='jquery' path='themes/default/js/plugins/jquery.tokeninput.js'}

{combine_css path='themes/default/js/plugins/chosen.css'}
{combine_script id='jquery.chosen' load='footer' path='themes/default/js/plugins/chosen.jquery.min.js'}

{combine_css path='themes/default/js/ui/theme/jquery.ui.slider.css'}
{combine_script id='jquery.ui.slider' require='jquery.ui' load='footer' path='themes/default/js/ui/minified/jquery.ui.slider.min.js'}

{combine_script id='smartalbums.filters' load='footer' template=true path=$SMART_PATH|cat:'admin/template/addFilters.js'}

{if isset($new_smart)}
{footer_script require='jquery'}
function doBlink(obj,start,finish) {
  jQuery(obj).fadeOut(400).fadeIn(400);
  if(start!=finish) {
    doBlink(obj,start+1,finish);
  }
  else {
    jQuery(obj).fadeOut(400);
  }
}

doBlink('.new_smart', 0, 3);
{/footer_script}
{/if}


<div class="titrePage">
  <h2><span style="letter-spacing:0">{$CATEGORIES_NAV}</span> &#8250; {'Edit album'|translate} [SmartAlbum]</h2>
</div>

<noscript>
  <div class="errors"><ul><li>JavaScript required!</li></ul></div>
</noscript>

<div id="batchManagerGlobal">
<form action="{$F_ACTION}" method="POST" id="smart">
  <p style="text-align:left;"><label><input type="checkbox" name="is_smart" {if isset($filters) OR isset($new_smart)}checked="checked"{/if}/> {'This album is a SmartAlbum'|translate}</label></p>

  <fieldset id="SmartAlbum_options" style="margin-top:1em;{if !isset($filters) AND !isset($new_smart)}display:none;{/if}">
    <legend>{'Filters'|translate}</legend>

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

      {footer_script}addFilter('{$filter.type}', '{$filter.cond}', '{$value|escape:javascript}');{/footer_script}
    {/strip}{/foreach}
    </ul>

    <div>
      <b>{'Mode'|translate} :</b>
      <label><input type="radio" name="filters[0][value]" value="and" {if $filter_mode=='and'}checked="checked"{/if}> AND</label>
      <label><input type="radio" name="filters[0][value]" value="or" {if $filter_mode=='or'}checked="checked"{/if}> OR</label>
      <input type="hidden" name="filters[0][type]" value="mode">
      <input type="hidden" name="filters[0][cond]" value="mode">
    </div>

    <p class="actionButtons">
      <select id="addFilter">
        <option value="-1">{'Add a filter'|translate}</option>
        <option disabled="disabled">------------------</option>
        <option value="tags">{'Tags'|translate}</option>
        <option value="date">{'Date'|translate}</option>
        <option value="name">{'Photo name'|translate}</option>
        <option value="album">{'Album'|translate}</option>
        <option value="dimensions">{'Dimensions'|translate}</option>
        <option value="author">{'Author'|translate}</option>
        <option value="hit">{'Hits'|translate}</option>
        <option value="rating_score">{'Rating score'|translate}</option>
        <option value="level">{'Privacy level'|translate}</option>
        <option value="limit">{'Max. number of photos'|translate}</option>
      </select>
      <a id="removeFilters">{'Remove all filters'|translate}</a>
      {if isset($new_smart)}<span class="new_smart">{'Add filters here'|translate}</span>{/if}
    </p>
  </fieldset>

  <p class="actionButtons" id="applyFilterBlock">
    <input class="submit" type="submit" value="{'Submit'|translate}" name="submitFilters"/>
    <input class="submit" type="submit" value="{'Count'|translate}" name="countImages" {if !isset($filters) AND !isset($new_smart)}style="display:none;"{/if}/>
    <span class="count_images_wrapper" {if !isset($filters) AND !isset($new_smart)}style="display:none;"{/if}><span class="count_image">{$IMAGE_COUNT}</span></span>
  </p>

</form>
</div>

<div id="filtersRepo" style="display:none;">
  {include file=$SMART_ABS_PATH|cat:'admin/template/filters.inc.tpl'}
</div>