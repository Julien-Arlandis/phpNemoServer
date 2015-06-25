<?php

/**
Copyright © 2013-2014 Julien Arlandis
    @author : Julien Arlandis <julien.arlandis_at_gmail.com>
    @Licence : http://www.gnu.org/licenses/agpl-3.0.txt

This file is part of PhpNemoServer.

    PhpNemoServer is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    PhpNemoServer is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with PhpNemoServer.  If not, see <http://www.gnu.org/licenses/>.
*/

if( !file_exists( __DIR__ . '/delete.txt'))
{
	die( '500 You must create delete.txt file to continue installation' );
}

$server_version = '0.89e';
$config = array(
    "private_key_bits" => 1024,
    "private_key_type" => OPENSSL_KEYTYPE_RSA,
);

$res = openssl_pkey_new($config);
openssl_pkey_export($res, $privateKey);

$pubkey = openssl_pkey_get_details($res);
$publicKey = $pubkey['key'];

$publicKey = (substr($publicKey, -1, 1) == "\n") ? substr($publicKey, 0, -1) : $publicKey;
$privateKey = (substr($privateKey, -1, 1) == "\n") ? substr($privateKey, 0, -1) : $privateKey;

function checkModules()
{
?>
<h2>Check modules</h2>
<ul>

<li>curl : <?=extension_loaded('curl')?'oui':'non';?></li>
<li>mongo : <?=extension_loaded('mongo')?'oui':'non';?></li>
<li>shell_exec : <?=function_exists('shell_exec')?'oui':'non';?></li>
<li>openssl_pkey_get_public : <?=function_exists('openssl_pkey_get_public')?'oui':'non';?></li>
</ul>
<?php
}

if(isset($_GET['php_path'])) 
{
	$php_path = $_GET['php_path'];
	$fp = popen($php_path.' '.__DIR__.'/install.php phpcli', 'r');

	while(!feof($fp)) 
	{ 
		print fread($fp, 1024); 
		flush(); 
	} 
	fclose($fp); 
	exit();
}

if (!isset($_POST['action']) )
{
?>
<!DOCTYPE HTML>
<html>
<head>
<title>Installation de PHP Nemo Server <?=$server_version?></title>
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

<?php
if(count($argv)>0)
{
?>
<h1>CHECK MODE PHP CLI</h1>
<?php
checkModules();
?>
</body>
</html>
<?php
exit();
}
?>

<h1>Installation de PHP Nemo Server <?=$server_version?></h1>

<?=checkModules();?>

<form action="" method="post">

<h2> Configuration générale </h2>
<span class="champ">[DB_NAME] Nom de la base de données : </span>
<input name="DB_NAME" type="text" value="nemonews">
<input name="DEL_DB" type="checkbox" value="1"> Supprimer la base si existante.
<br>
<span class="champ">[PHP_PATH] Chemin de l'interpréteur php CLI : </span>
<input id="PHP_PATH" name="PHP_PATH" type="text" value="/usr/bin/php"><button id="check_php_path" type="button">check</button>
<br>
<span class="champ">[PUBLIC_KEY] Clé publique du serveur : </span>
<textarea id="PUBLIC_KEY" name="PUBLIC_KEY" cols="64" rows="6"><?=$publicKey?></textarea>
<br>
<span class="champ">[PRIVATE_KEY] Clé privée du serveur : </span>
<textarea id="PRIVATE_KEY" name="PRIVATE_KEY" cols="64" rows="16"><?=$privateKey?></textarea>
<br>

<h2> Compte administrateur</h2>
<span class="champ">User : </span>
<input name="USER" type="text" value="<?='admin'?>">
<input name="ADD_ADMIN" type="checkbox"> Ajouter l'utilisateur dans la base.
<br>
<span class="champ">Password : </span>
<input name="PASSWORD" type="text" value="<?=substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"),0,8);?>">
<br>
<span class="champ">Email: </span>
<input name="EMAIL" type="text" value="<?='newsmaster@'.$_SERVER['SERVER_NAME']?>">
<br><br>
Le fichier /NemoServer/conf/config.php va être crée, vous devrez le modifier ultérieurement pour paramétrer les feeds et pour activer les logs.
<br>
<input name="action" type="submit" value="Installer">
</form>

<br><br>
<script>
$(document).ready(function() {
	$('#check_php_path').click(function() {
		window.open('/NemoServer/install.php?php_path='+$('#PHP_PATH').val());
	});
})
</script>

</body>
</html>

<?php
die();
}
else
{

/* 
* Création du fichier de config.php
*/

$file_config_copy = __DIR__."/config.inc.php";
$file_config_final = __DIR__."/conf/config.php";
$buffer = file_get_contents($file_config_copy);

$variables = array(
	'#DB_NAME#' => $_POST['DB_NAME'],
	'#PHP_PATH#' => $_POST['PHP_PATH'],
	'#PUBLIC_KEY#' => $_POST['PUBLIC_KEY'],
	'#PRIVATE_KEY#' => $_POST['PRIVATE_KEY']
);

foreach($variables as $code_variable => $valeur)
{
	$buffer = str_replace($code_variable, $valeur, $buffer);
}

$file = fopen($file_config_final, 'w');
fputs($file, $buffer);
fclose($file);

/* 
* Création du fichier general.json
*/

$file_general_copy = __DIR__."/general.json.inc";
$file_general_final = __DIR__."/conf/general.json";
$buffer = json_decode(file_get_contents($file_general_copy), true);
$buffer['domain'] = $_SERVER['SERVER_NAME'];
$buffer['administrator'] = 'newsmaster@'.$_SERVER['SERVER_NAME'];
$buffer = json_encode($buffer, JSON_PRETTY_PRINT);
$file = fopen($file_general_final, 'w');
fputs($file, $buffer);
fclose($file);

require_once($file_config_final);
require_once('Applications/core/lib/class.jntp.php');

$jntp = new JNTP();

/*
* Création de la base de données
*/

if(isset($_POST['DEL_DB']))
{
	$jntp->mongo->drop();
	$jntp->createIndex();
}

/*
* Création du compte administrateur
*/

if(isset($_POST['ADD_ADMIN']))
{
	$hashkey = (string)rand(100000000000, 99999999999999);
	$session = $hashkey = (string)rand(100000000000, 99999999999999);
	$checksum = sha1(uniqid());
	$password_crypt = sha1($checksum.$_POST['PASSWORD']);
	$date = date("Y-m-d").'T'.date("H:i:s").'Z';

	$res = $jntp->mongo->counters->findAndModify(
		array("_id"=>"UserID"),
		array('$inc'=>array("seq"=>1)),
		null,
		array("new" => true, "upsert"=>true)
	);
	$userid = $res['seq'];
	$user = array('UserID' => $userid, 'user' => $_POST['USER'], 'email' => $_POST['EMAIL'], 'password' => $password_crypt, 'privilege' => 'admin', 'hashkey' => $hashkey, 'date' => $date, 'checksum' => $checksum, 'Session'=>$session);

	$jntp->mongo->user->insert($user);
}

/*
* Synchronisation des groupes
*/
$jntp->privilege = 'admin';
$jntp->exec('["synchronizeNewsgroup"]');

}
?>

<!DOCTYPE HTML>
<html>
<head>
<title>Installation de PHP Nemo Server (version <?=$server_version?>)</title>
<meta charset="UTF-8">
</head>
<body>

Installation de PHP Nemo Server (version <?=$server_version?>) terminée. La base <?=DB_NAME?> a bien été crée. 
<p>
Vous devez supprimer le fichier install.php pour continuer.
<p>
Vous pouvez désormais configurez les feeds dans le fichier conf/general.json.

</body>
</html>
