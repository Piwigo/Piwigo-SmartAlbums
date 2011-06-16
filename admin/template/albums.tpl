<div class="titrePage">
	<h2>SmartAlbums</h2>
</div>

<h3>
  <a href="{$F_ACTION}">{'All SmartAlbums'|@translate}</a>
  {if count($categories)>9 }
  <a href="#EoP" class="button" style="border:0;">
  <img src="{$themeconf.admin_icon_dir}/page_end.png" title="{'Page end'|@translate}" class="button" alt="page_end" style="margin-bottom:-0.6em;"></a>
  {/if}
</h3>

<form id="addVirtual" action="{$F_ACTION}" method="post">
  <fieldset>
    <legend>{'Add a SmartAlbum'|@translate}</legend>
  
    <input type="hidden" name="pwg_token" value="{$PWG_TOKEN}">
    {'Album name'|@translate} : <input type="text" name="virtual_name">
    {'Parent album'|@translate} :
    <select class="categoryDropDown" name="parent_id">
      <option value="0">------------</option>
      {html_options options=$category_options}
    </select>
    <input class="submit" type="submit" value="{'Submit'|@translate}" name="submitAdd">
  
  </fieldset>
</form>

{if count($categories) }
<form id="categoryOrdering" action="{$F_ACTION}" method="post">
  <input type="hidden" name="pwg_token" value="{$PWG_TOKEN}">

  <div class="orderParams">
    
  </div>

  <ul class="categoryUl">

    {foreach from=$categories item=category}
    <li class="categoryLi virtual_cat" id="cat_{$category.ID}">
      <!-- category {$category.ID} -->
      <ul class="categoryActions">
        {if cat_admin_access($category.ID)}
        <li><a href="{$category.U_JUMPTO}" title="{'jump to album'|@translate}"><img src="{$themeconf.admin_icon_dir}/category_jump-to.png" class="button" alt="{'jump to album'|@translate}"></a></li>
        {/if}
        <li><a href="{$category.U_EDIT}" title="{'edit album'|@translate}"><img src="{$themeconf.admin_icon_dir}/category_edit.png" class="button" alt="{'edit'|@translate}"></a></li>
        {if isset($category.U_MANAGE_ELEMENTS) }
        <li><a href="{$category.U_MANAGE_ELEMENTS}" title="{'manage album photos'|@translate}"><img src="{$themeconf.admin_icon_dir}/category_elements.png" class="button" alt="{'Photos'|@translate}"></a></li>
        {/if}
        {if isset($category.U_MANAGE_PERMISSIONS) }
        <li><a href="{$category.U_MANAGE_PERMISSIONS}" title="{'edit album permissions'|@translate}" ><img src="{$themeconf.admin_icon_dir}/category_permissions.png" class="button" alt="{'Permissions'|@translate}"></a></li>
        {/if}
        <li><a href="{$category.U_SMART}" title="{'Regenerate photos list of this SmartAlbum'|@translate}"><img src="{$themeconf.admin_icon_dir}/synchronize.png" class="button" alt="{'Regenerate photos list of this SmartAlbum'|@translate}"></a></li>
        {if isset($category.U_DELETE) }
        <li><a href="{$category.U_DELETE}" title="{'delete album'|@translate}" onclick="return confirm('{'Are you sure?'|@translate|@escape:javascript}');"><img src="{$themeconf.admin_icon_dir}/category_delete.png" class="button" alt="{'delete album'|@translate}"></a></li>
        {/if}
      </ul>

      <p>
        <strong>{$category.NAME}</strong>
      </p>

    </li>
    {/foreach}
  </ul>
</form>
{/if}

<form method="post" action="{$F_ACTION}&amp;smart_generate=all">
  <input type="hidden" name="pwg_token" value="{$PWG_TOKEN}">
  <p><input class="submit" type="submit" value="{'Regenerate photos list of all SmartAlbums'|@translate}"></p>
</form>

<a name="EoP"></a>
