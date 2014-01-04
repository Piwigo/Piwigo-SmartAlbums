{footer_script require='jquery'}
jQuery("#addAlbumOpen").click(function(){
  jQuery("#createAlbum").toggle();
  jQuery("input[name=virtual_name]").focus();
  jQuery("#autoOrder").hide();
});

jQuery("#addAlbumClose").click(function(){
  jQuery("#createAlbum").hide();
});
{/footer_script}


<div class="titrePage">
	<h2>SmartAlbums</h2>
</div>

<form id="categoryOrdering" action="{$F_ACTION}" method="post">
<input type="hidden" name="pwg_token" value="{$PWG_TOKEN}">

<p class="showCreateAlbum">
  <span id="notManualOrder">
    <a href="#" id="addAlbumOpen">{'create a new SmartAlbum'|translate}</a>
    | <a href="{$F_ACTION}&amp;smart_generate=all">{'Regenerate photos list of all SmartAlbums'|translate}</a>
  </span>
</p>

<fieldset id="createAlbum" style="display:none;">
  <legend>{'create a new SmartAlbum'|translate}</legend>
  <input type="hidden" name="pwg_token" value="{$PWG_TOKEN}">

  <p>
    <strong>{'Album name'|translate}</strong> :
    <input type="text" name="virtual_name">
  </p>
  <p>
    <strong>{'Parent album'|translate}</strong>  :
    <select class="categoryDropDown" name="parent_id">
      <option value="0">------------</option>
      {html_options options=$category_options}
    </select>
  </p>
  <p class="actionButtons">
    <input class="submit" type="submit" value="{'Create'|translate}" name="submitAdd">
    <a href="#" id="addAlbumClose">{'Cancel'|translate}</a>
  </p>
</fieldset>

{if count($categories) }

  <ul class="categoryUl">

    {foreach from=$categories item=category}
    <li class="categoryLi virtual_cat" id="cat_{$category.ID}">
      <!-- category {$category.ID} -->
      <p class="albumTitle">
        <strong><a href="{$category.U_CHILDREN}" title="{'manage sub-albums'|translate}">{$category.NAME}</a></strong>
        <img src="{$SMART_PATH}admin/template/lightning.png">
        {'%d photos'|translate:$category.IMG_COUNT}
      </p>

      <p class="albumActions">
        <a href="{$category.U_EDIT}">{'Edit'|translate}</a>
        | <a href="{$category.U_SMART}" title="{$category.LAST_UPDATE}">{'Regenerate photos list of this SmartAlbum'|translate}</a>
        {if isset($category.U_DELETE) }
        | <a href="{$category.U_DELETE}" onclick="return confirm('{'Are you sure?'|translate|escape:javascript}');">{'delete album'|translate}</a>
        {/if}
        {if cat_admin_access($category.ID)}
        | <a href="{$category.U_JUMPTO}">{'jump to album'|translate} ?</a>
        {/if}
      </p>
    </li>
    {/foreach}
  </ul>
{/if}
</form>