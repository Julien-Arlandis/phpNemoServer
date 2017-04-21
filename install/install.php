<?php

header("Cache-Control: no-cache, must-revalidate");

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

function flushCmd($cmd)
{
	$fp = popen($cmd, 'r');

	while(!feof($fp))
	{
		print fread($fp, 1024);
		flush();
	}
	fclose($fp);
}

function checkModules()
{
  $curl = extension_loaded('curl') ? "oui" : "non";
  $mongo = extension_loaded('mongo') ? "oui" : "non";
  $shell_exec = function_exists('shell_exec') ? "oui" : "non";
  $openssl_pkey_get_public = function_exists('openssl_pkey_get_public') ? "oui" : "non";

  $t="<h2>Check modules</h2>";
  $t.="<ul>";
  $t.="<li>curl : ".$curl."</li>";
  $t.="<li>mongo : ".$mongo."</li>";
  $t.="<li>shell_exec : ".$shell_exec."</li>";
  $t.="<li>openssl_pkey_get_public : ".$openssl_pkey_get_public."</li>";
  $t.="</ul>";
  return $t;
}

$die = false;
if( !file_exists( __DIR__ . '/../sleep'))
{
	$die = true;
	echo 'You must create jntp/sleep file to continue installation : <br>';
	echo '<strong>touch jntp/sleep</strong><p>';
}

if( !is_writable( __DIR__ . '/../conf'))
{
	$die = true;
	echo "jntp/conf/ is not writable : <br>";
	echo "<strong>chmod o+w jntp/conf</strong><p>";
}

if($die) die();

if(count($argv) > 1 && $argv[1] == "phpcli" )
{
  die( checkModules() );
}

if(isset($_GET['php_path']))
{
  $cmd = $_GET['php_path'].' '.__DIR__.'/install.php phpcli';
  flushCmd($cmd);
  die();
}

if (!isset($_POST['action']) )
{

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


  echo Tools::getTpl(__DIR__."/tpl/install_form.tpl",
  			array(
  				"server_version" => SERVER_VERSION,
  				"publicKey" => $publicKey,
  				"privateKey" => $privateKey,
  				"checkModules"=>checkModules(),
  				"password"=> substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"),0,8),
  				"email" => "newsmaster@".$_SERVER['SERVER_NAME'],
  				"user_admin" => "admin",
  				"php_path" => "/usr/bin/php"
  			     )
  	);
}
else
{
/*
* Création du fichier general.json
*/

$file_general_copy = __DIR__."/config.inc.json";
$file_general_final = __DIR__."/../conf/config.json";
$buffer = json_decode(file_get_contents($file_general_copy), true);

$buffer['dbName'] = $_POST['DB_NAME'];
$buffer['phpPath'] = $_POST['PHP_PATH'];
$buffer['publicKey'] = str_replace("\r", "", $_POST['PUBLIC_KEY']);
$buffer['privateKey'] = str_replace("\r", "", $_POST['PRIVATE_KEY']);
$buffer['domain'] = $_SERVER['SERVER_NAME'];
$buffer['administrator'] = 'newsmaster@'.$_SERVER['SERVER_NAME'];
$buffer = json_encode($buffer, JSON_PRETTY_PRINT);
$file = fopen($file_general_final, 'w');
fputs($file, $buffer);
fclose($file);

/*
* Création de la base de données
*/

JNTP::init();
if(isset($_POST['DEL_DB']))
{
	JNTP::$mongo->drop();
	JNTP::createIndex();
}

/*
* Création du compte administrateur
*/

if(isset($_POST['ADD_ADMIN']))
{
	$session = $hashkey = sha1(uniqid());
	$checksum = sha1(uniqid());
	$password_crypt = sha1($checksum.$_POST['PASSWORD']);
	$date = date("Y-m-d").'T'.date("H:i:s").'Z';

	$res = JNTP::$mongo->counters->findAndModify(
		array("_id"=>"UserID"),
		array('$inc'=>array("seq"=>1)),
		null,
		array("new" => true, "upsert"=>true)
	);
	$userid = $res['seq'];
	$user = array('UserID' => $userid, 'user' => $_POST['USER'], 'email' => $_POST['EMAIL'], 'password' => $password_crypt, 'privilege' => 'admin', 'hashkey' => $hashkey, 'date' => $date, 'checksum' => $checksum, 'Session'=>$session);

	JNTP::$mongo->user->insert($user);
}

/*
* Synchronisation des groupes
*/
JNTP::$privilege = 'admin';
JNTP::exec('["synchronizeNewsgroup"]');

echo Tools::getTpl(__DIR__."/tpl/install_valid.tpl",
			array(
				"server_version" => SERVER_VERSION,
				"dbName" => JNTP::$config{'dbName'},
				"info" => JNTP::$reponse{'info'}
			     )
	);

}
?>
