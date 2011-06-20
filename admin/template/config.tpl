<div class="titrePage">
	<h2>SmartAlbums</h2>
</div>

<form method="post" action="" class="properties" ENCTYPE="multipart/form-data"> 
	<fieldset>
		<legend>{'Configuration'|@translate}</legend>	  
		<ul>			
			<li>
        <label>
          <span class="property">{'Update albums on file upload'|@translate}</span>
          <input type="checkbox" name="update_on_upload" value="true" {if $update_on_upload}checked="checked"{/if}/>
          {'(can cause slowdowns on admin pages)'|@translate}
        </label>
			</li>
		</ul>
	</fieldset>
		
	<p><input class="submit" type="submit" value="{'Submit'|@translate}" name="submit" /></p>
</form>
