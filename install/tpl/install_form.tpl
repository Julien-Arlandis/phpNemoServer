<!DOCTYPE HTML>
<html>
<head>
<title>Installation de PHP Nemo Server %server_version%</title>
<script src="http://code.jquery.com/jquery-2.1.3.min.js"></script>
<style>
textarea, .champ, input {
	font-family: monospace;
	white-space: pre;
}
.champ {
	display: block;
	float: left;
	width: 350px;
}
input[type=text], input[type=password] {
	width: 280px;
}
</style>
<meta charset="UTF-8">
</head>

<body>

<h1>Installation de PHP Nemo Server %server_version%</h1>

%checkModules%

<form action="" method="post" enctype="multipart/form-data">

<h2> Configuration générale </h2>
<span class="champ">[DB_NAME] Nom de la base de données : </span>
<input name="DB_NAME" type="text" value="nemonews">
<input name="DEL_DB" type="checkbox" value="1"> Supprimer la base si existante.
<br>
<span class="champ">[PHP_PATH] Chemin de l'interpréteur php CLI : </span>
<input id="PHP_PATH" name="PHP_PATH" type="text" value="%php_path%"><button id="check_php_path" type="button">check</button>
<br>
<span class="champ">[PUBLIC_KEY] Clé publique du serveur : </span>
<textarea id="PUBLIC_KEY" name="PUBLIC_KEY" cols="64" rows="6">%publicKey%</textarea>
<br>
<span class="champ">[PRIVATE_KEY] Clé privée du serveur : </span>
<textarea id="PRIVATE_KEY" name="PRIVATE_KEY" cols="64" rows="16">%privateKey%</textarea>

<h2> Compte administrateur</h2>
<span class="champ">User : </span>
<input disabled id="user_checked" name="USER" type="text" value="%user_admin%">
<input id="check_add_user" name="ADD_ADMIN" type="checkbox"> Ajouter l'utilisateur dans la base.
<br>
<span class="champ">Password : </span>
<input disabled id="PASSWORD" name="PASSWORD" type="text" value="%password%">
<br>
<span class="champ">Email: </span>
<input disabled id="EMAIL" name="EMAIL" type="text" value="%email%">
<br><br>
Le fichier /jntp/conf/config.php va être crée, vous devrez le modifier ultérieurement pour paramétrer les feeds et pour activer les logs.
<br>
<input name="action" type="submit" value="Installer">
</form>

<script>
$(document).ready(function() {
  
	$('#check_php_path').click(function() {
		window.open('/jntp/?php_path='+$('#PHP_PATH').val());
	});
	
	$('#check_add_user').change(function() {
	  
	  if( this.checked ) {
		  $( "#user_checked, #PASSWORD, #EMAIL" ).prop( "disabled", false );
	  }else{
	    $( "#user_checked, #PASSWORD, #EMAIL" ).prop( "disabled", true );
	  }
	  
	});
	
})
</script>

</body>
</html>