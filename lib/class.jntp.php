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

define('SERVER_VERSION', '0.94.3');
require_once(__DIR__."/class.tools.php");

class JNTP
{
	static $config; // configuration du serveur
	static $reponse; // reponse du serveur
	static $param; // contient les arguments de la requête JNTP
	static $command; // Commande JNTP requêtée par le client
	static $packet; // Packet JNTP
	static $id = false; // id de l'utilisateur
	static $userid = false; // UserID de l'utilisateur
	static $privilege = false; // Privilege de l'utilisateur
	static $datatype; // class DataType
	static $mongo; // class MongoClient
	static $session = false; // JNTP-Session
	static $commandByApplication;
	static $datatypeByApplication;
	static $stopSuperDiffuse = false;
	static $publicKeyForModeration = false;
	static $app; // class de l'application

	// Constructeur
	static function init($withSession = true)
	{
		date_default_timezone_set('UTC');
		Tools::getConfig();
		self::$config{'serverVersion'} = SERVER_VERSION;
		$m = new MongoClient();
		self::$mongo = $m->selectDB(self::$config{'dbName'});
		if( $withSession ) self::setSession();
	}

	static function go($app, $cmd)
	{
		require(__DIR__.'/../Applications/'.$app.'/lib/class.app.php');
		self::$app = new App();
		require(__DIR__.'/../Applications/'.$app.'/command/'.$cmd.'.php');
	}
	
	// Execute une commande JNTP sur le présent serveur ou sur un serveur distant
	static function exec($post, $server = false)
	{
		Tools::log($post);
		if($post === '')
		{
			$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == true) ? 'https' : 'http';
			die( "200 ".$protocol.'://'.self::$config{'domain'}.'/jntp/ - PhpNemoServer/'.self::$config{'serverVersion'}.' - JNTP Service Ready - '.self::$config{'administrator'}.' - Type ["help"] for help' );
		}
		if($server)
		{
			$post = is_array($post) ? json_encode($post) : $post;

			$options = array(
				CURLOPT_URL            => "http://".$server."/jntp/",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HEADER         => false,
				CURLOPT_FAILONERROR    => true,
				CURLOPT_POST           => true,
				CURLOPT_TIMEOUT	       => 20,
				CURLOPT_POSTFIELDS     => $post
			);

			$CURL = curl_init();
			if(!empty($CURL))
			{
				curl_setopt_array($CURL, $options);
				$reponse = curl_exec($CURL);
				curl_close($CURL);
				self::$reponse = json_decode($reponse, true);
			}
			else
			{
				self::$reponse{'code'} = "500";
				self::$reponse{'info'} = "Connection failed";
			}
			return;
		}

		$json = json_decode($post, true);

		self::$command = $json[0];
		self::$param = (count($json)> 1) ? $json[1] : null;

		if (!is_array($json))
		{
			self::$reponse{'code'} = "500";
			self::$reponse{'info'} = "Bad Syntax, type help command";
			self::send();
		}
		$application = self::$commandByApplication[self::$command];
		if ($application)
		{
			self::go($application, self::$command);
		}
		else
		{
			self::$reponse{'code'} = "500";
			self::$reponse{'info'} = "Command not found, [".self::$command."]";
		}
	}

	// Retourne le résultat de la requête et stoppe le script
	static function send()
	{
		$res = json_encode( self::$reponse );
		Tools::log($res, '>');
		die( $res );
	}

	static function loadDataType()
	{
		$datatype = self::$packet{'Data'}{'DataType'};
		if( $application = self::$datatypeByApplication[$datatype] )
		{
			require_once(__DIR__.'/../Applications/'.$application.'/DataType/'.$datatype.'/'.$datatype.'.php');
			self::$datatype = new DataType();
			return true;
		}
		return false;
	}

	// Vérifie la validité d'un packet JNTP
	static function isValidPacket()
	{
		return true;
	}

	// Remplace les data longues par leur hash.
	static function replaceHash( $packet )
	{
		if($packet{'Data'}{'Media'})
		{
			foreach($packet{'Data'}{'Media'} as $ind => $val)
			{
				foreach($packet{'Data'}{'Media'}[$ind] as $key => $value)
				{
					if(self::$config{'maxDataLength'}!=0 && is_string($packet{'Data'}{'Media'}[$ind][$key]) && strlen($packet{'Data'}{'Media'}[$ind][$key]) > self::$config{'maxDataLength'})
					{
						$packet{'Data'}{'Media'}[$ind]['#'.$key] = self::hashString($packet{'Data'}{'Media'}[$ind][$key]);
						unset ( $packet{'Data'}{'Media'}[$ind][$key] );
					}
				}
			}
		}
		return $packet;
	}

	// Fabrique le paquet JNTP qui encapsule la Data
	static function forgePacket()
	{
		self::$packet{'Jid'} = self::hashString( self::canonicFormat(self::$packet{'Data'}) );
		if(self::$packet{'Data'}{'DataID'} === "@jntp")
		{
			self::$packet{'Data'}{'DataID'} = self::$packet{'Jid'}.self::$packet{'Data'}{'DataID'};
		}
		if(!self::$packet{'Route'}) self::$packet{'Route'} = array();
		if(!self::$packet{'Meta'}) self::$packet{'Meta'} = array();

		if (!$privateKey = openssl_pkey_get_private(self::$config{'privateKey'})) die('Loading Private Key failed');
		openssl_private_encrypt(self::$packet{'Jid'}, $signature, $privateKey);

		self::$packet{'Meta'}{'ServerSign'} = base64_encode($signature);
		self::$packet{'Meta'}{'ServerPublicKey'} = self::$config{'publicKey'};
	}

	// Insère le packet dans la base
	static function insertPacket()
	{
		array_push(self::$packet{'Route'}, self::$config{'domain'});
		$res = self::$mongo->counters->findAndModify(
			array("_id"=>"packetID"),
			array('$inc'=>array("seq"=>1)),
			null,
			array("new" => true, "upsert"=>true)
		);

		self::$packet{'ID'} = self::$packet{'Data'}{'InjectionDate'}.'/'.$res['seq'];

		try {
			self::$mongo->packet->save(self::$packet);
			return true;
		} catch(MongoCursorException $e) {
			return false;
		}
	}

	// Supprime un packet JNTP
	static function deletePacket( $query )
	{
		return self::$mongo->packet->remove( $query );
	}

	// Récupère un packet JNTP
	static function getPacket( $query )
	{
		return self::$mongo->packet->findOne( $query, array('_id'=>0) );
	}

	// Vérifie si un packet d'un Jid donné est stocké dans la base
	static function isStorePacket( $query )
	{
		$bool = (self::$mongo->packet->find( $query )->count() > 0 ) ? true : false;
		return $bool;
	}

	// Contacte les feeds pour distribuer packets
	static function superDiffuse()
	{
		foreach(self::$config{'outFeeds'} as $server => $value)
		{
			if(!self::$config{'outFeeds'}{$server}{'actif'}) continue;
			if(in_array($server, self::$packet{'Route'})) continue;

			$jid = str_replace("'","\'",self::$packet{'Jid'});
			$datatype = str_replace("'","\'",self::$packet{'Data'}{'DataType'});
			$dataid = str_replace("'","\'",self::$packet{'Data'}{'DataID'});
			if(self::$config{'shellExec'})
			{
				$cmd = self::$config{'phpPath'}.' '.__DIR__.'/../connector/'.self::$config{'outFeeds'}{$server}{'type'}[1].' '.$server." '$jid' '$dataid' '$datatype'";
				shell_exec($cmd. ' >> /dev/null &');
			}
			else
			{
				require_once(__DIR__.'/../connector/'.self::$config{'outFeeds'}{$server}{'type'}[1]);
				J2_($server, $jid, $dataid, $datatype);
			}
		}
	}

	// Retourne la ressource d'un packet requêtée au format URI ex : http://[server]/jntp/[Jid]/Data.FromName
	static function getResource($path) // à corriger.
	{
		$tab = preg_split('/\//', $path);
		$json  = self::$mongo->packet->findOne( array('Data.DataID'=>$tab[0]), array('_id'=>0) );
		$tab = preg_split("/([:\.\/]+)/", $tab[1], -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );

		if(!$json) {
			die( "Paquet inexistant" );
		}

		$dataMedia = false;
		$datatype = $json['Data']['DataType'];

		if(count($tab) >= 1 ) {$json = $json[$tab[0]];}
		if(count($tab) >= 3 && $tab[1] == "." ) {$json = $json[$tab[2]];}
		if(count($tab) >= 3 && $tab[1] == ":" ) {$json = $json[$tab[2]-1];}
		if(count($tab) >= 5 && $tab[3] == "." ) {$json = $json[$tab[4]];}
		if(count($tab) >= 5 && $tab[3] == ":" ) {$json = $json[$tab[4]-1];}

		if($tab[2] == 'Media') // à documenter dans la RFC de JNTP
		{
			$dataMedia = true;
		}

		if((is_object($json) || is_array($json)) && !$dataMedia)
		{
			return json_encode($json);
		}
		else
		{
			if ($dataMedia)
			{
				if(is_array($json))
				{
					$data = explode(",", $json{'data'});
					if($filename = $json{'filename'})
					{
						header('Content-Disposition: attachment; filename="'.$filename.'"');
					}
				}
				else
				{
					$data = explode(",", $json);
				}

				$tab = explode(";", $data[0]);
				$type_mime = explode(":", $tab[0]);
				$type_mime = ($type_mime[1] != "") ? $type_mime[1] : "application/octet-stream";
				header('Content-type: '.$type_mime);
				$expires = 60*60*24*14;
				header("Pragma: public");
				header("Cache-Control: maxage=".$expires);
				header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');

				if($tab[1] == 'base64')
				{
					return base64_decode($data[1]);
				}
				else
				{
					return $data[1];
				}
			}
			else
			{
				return '<html><head><meta charset="UTF-8"></head><body><pre>'.htmlentities(str_replace($json)).'</pre></body></html>';
			}
		}
	}

	static function canonicFormat($json, $firstRecursivLevel = true)
	{
		if (is_array($json) )
		{
			foreach ($json as $key => $value)
			{
				if(is_array($value) || is_int($key) )
				{
					$json[$key] = self::canonicFormat($value, false);
				}
				else
				{
					if(strlen($value) > 27)
					{
						$json['#'.$key] = self::hashString($value);
						unset( $json[$key] );
					}
				}
			}
		}
		if( $firstRecursivLevel ) return (json_encode(Tools::sortJSON($json), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return $json;
	}
	
	/* à migrer */

	static function hashString($str)
	{
		return rtrim(strtr(base64_encode(sha1($str, true)), '+/', '-_'), '=');
	}

	static function randomKeyIv()
	{
		//$key = key(64 hexa in base64) + iv(32 hexa in base64);
		return substr(sha1(uniqid(rand(), true)).sha1(uniqid(rand(), true)).sha1(uniqid(rand(), true)),0,96);
	}

	static function encryptAES256($str, $key_iv)
	{
		$key = pack('H*', substr($key_iv, 0, 64));
		$iv = pack('H*', substr($key_iv, 65));
		return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $str, MCRYPT_MODE_CFB, $iv));
	}

	static function decryptAES256($str, $key_iv)
	{
		$key = pack('H*', substr($key_iv, 0, 64));
		$iv = pack('H*', substr($key_iv, 65));
		$str = base64_decode($str);
		return substr(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $iv.$str, MCRYPT_MODE_CFB, $iv), 16);
	}
	
	static function createIndex()
	{
		self::$mongo->user->ensureIndex(array('email' => 1), array('unique' => true));
		self::$mongo->user->ensureIndex(array('UserID' => 1), array('unique' => true));
		self::$mongo->packet->ensureIndex(array('ID' => 1), array('unique' => true));
		self::$mongo->packet->ensureIndex(array('Jid' => 1), array('unique' => true));

		foreach( self::$config{'Applications'} as $app => $val)
		{
			foreach($val{'DataType'} as $datatype => $content)
			{
				foreach($content{'filter'} as $key)
				{
					if($key != 'ID' && $key != 'Jid')
					{
						self::$mongo->packet->ensureIndex(array($key => 1, 'ID' => 1));
					}
				}
			}
		}
	}
	
	// Met à jour les informations de l' utilisateur
	static function updateUserConfig($arr)
	{
		self::$mongo->user->update(
		    array("UserID" => self::$id),
		    array('$set' => $arr)
		);
	}
	
	static function startSession($session, $userid, $privilege)
	{
		if(!$session)
		{
			$session = sha1(rand(0, 9e16).uniqid());
			self::$mongo->user->update(
			    array("UserID" => $userid),
			    array('$set' => array('Session'=>$session))
			);
		}
		setcookie("JNTP-Session", $session, time()+360000);

		self::$id = $userid;
		self::$userid = $userid.'@'.self::$config{'domain'};
		self::$privilege = $privilege;
		self::$session = $session;
	}

	// Initialise la session
	static function setSession()
	{
		$session = $_COOKIE["JNTP-Session"];

		// Permit local connection
		if(!isset($_SERVER['HTTP_REFERER']) || self::$config['crossDomainAccept'])
		{
			header("Access-Control-Allow-Headers: JNTP-Session");
			header("Access-Control-Allow-Origin: *");
			if(isset( $_SERVER['JNTP-Session'] ) && $_SERVER['JNTP-Session'] != '')
			{
				$session = $_SERVER['JNTP-Session'];
			}
		}

		if(!$session)
		{
			$headers = getallheaders();
			$session = $headers["JNTP-Session"];
			if(!$session) return;
		}

		$obj = self::$mongo->user->findOne( array('Session' => ''.$session) );
		if(count($obj) > 0)
		{
			self::$id = $obj{'UserID'};
			self::$userid = $obj{'UserID'}.'@'.self::$config{'domain'};
			self::$privilege = $obj{'privilege'};
			self::$session = $obj{'Session'};
		}
	}
	
	// Détruit la session
	static function destroySession()
	{
		self::$mongo->user->update(
		    array("UserID" => self::$id),
		    array('$set' => array('Session'=>false))
		);
		self::$id = false;
		self::$userid = false;
		self::$privilege = false;
		self::$session = false;
	}

}