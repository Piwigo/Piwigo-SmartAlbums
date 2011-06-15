<div class="titrePage">
	<h2>SmartAlbums</h2>
</div>

<form method="post" action="" class="properties" ENCTYPE="multipart/form-data"> 
	<fieldset>
		<legend>{'Configuration'|@translate}</legend>	  
		<ul>			
			<li>
				<span class="property">{'Update albums on file upload'|@translate}</span>
				<label><input type="radio" name="update_on_upload" value="true" {if $update_on_upload == 'true'}checked="checked"{/if}/> {'Yes'|@translate}</label>
				<label><input type="radio" name="update_on_upload" value="false" {if $update_on_upload == 'false'}checked="checked"{/if}/> {'No'|@translate}</label>
			</li>
		</ul>
	</fieldset>
		
	<p><input class="submit" type="submit" value="{'Submit'|@translate}" name="submit" /></p>
</form>
