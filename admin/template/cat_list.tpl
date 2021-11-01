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

{assign var='color_tab' value=["icon-red", "icon-blue", "icon-yellow", "icon-purple", "icon-green"]}

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

  <div class="search-album-result">

    {foreach from=$categories item=category}
    <div class="search-album-elem" style="">
      <span class="search-album-icon icon-folder-open {$color_tab[$category.ID % 5]}"></span>
      <p class="search-album-name">
        {$category.NAME}</a>
        <img src="{$SMART_PATH}admin/template/lightning.png"> {'%d photos'|translate:$category.IMG_COUNT}
      </p>
      <div class="search-album-action-cont">
        <div class="search-album-action">
          <a class="icon-cog" href="{$category.U_SMART}" title="{$category.LAST_UPDATE}">{'Regenerate photos list of this SmartAlbum'|translate}</a>
        </div>
      </div>
    </div>

    {/foreach}
  </ul>
{/if}
</form>