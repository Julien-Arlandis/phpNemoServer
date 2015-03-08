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

class JNTP
{
	var $reponse; // reponse du serveur
	var $param; // contient les arguments de la requête JNTP
	var $command; // Commande JNTP requêtée par le client
	var $packet; // Packet JNTP
	var $id = false; // id de l'utilisateur
	var $userid = false; // UserID de l'utilisateur
	var $privilege = false; // Privilege de l'utilisateur
	var $datatype; // class DataType
	var $mongo; // class MongoClient
	var $session = false; // JNTP-Session
	var $listCommand;
	var $config;
	var $maxDataLength;

	// Constructeur
	function __construct()
	{
		require_once(__DIR__."/../../../conf/config.php");
		date_default_timezone_set('UTC');
		$m = new MongoClient();
		$this->mongo = $m->selectDB(DB_NAME);
		$this->config = json_decode(file_get_contents(__DIR__.'/../../../conf/description.json'),true);
		$this->maxDataLength = $this->config['maxDataLength'];
		foreach($this->config['application'] as $application => $content)
		{
			foreach($content['commands'] as $command)
			{
				$this->listCommand[$command] = $application;
			}
		}
	}
	
	// Execute une commande JNTP sur le présent serveur ou sur un serveur distant
	function exec($post, $server = false)
	{
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
				$this->reponse = json_decode($reponse, true);
			}
			else
			{
				$this->reponse{'code'} = "500";
				$this->reponse{'body'} = "Connection failed";
			}
			return;
		}

		$json = json_decode($post, true);

		$this->command = $json[0];
		$this->param = (count($json)> 1) ? $json[1] : null;

		if (!is_array($json)) 
		{
			$this->reponse{'code'} = "500";
			$this->reponse{'body'} = "Bad Syntax, type help command";
			$this->send();
		}
		$application = $this->listCommand[$this->command];
		if ($application)
		{
			require(__DIR__.'/../../'.$application.'/command/'.$this->command.'.php');
		}
		else
		{
			$this->reponse{'code'} = "500";
			$this->reponse{'body'} = "Command not found, [".$this->command."]";
		}
	}

	// Met à jour les informations de l' utilisateur
	function updateUserConfig($arr)
	{
		$this->mongo->user->update(
		    array("UserID" => $this->id),
		    array('$set' => $arr)
		);
	}

	// Retourne le résultat de la requête et stoppe le script
	function send()
	{
		$res = json_encode( $this->reponse );
		$this->log($res, '>');
		die( $res );
	}

	function createIndex()
	{
		$this->mongo->newsgroup->ensureIndex(array('name' => 1), array('unique' => true));
		$this->mongo->user->ensureIndex(array('email' => 1), array('unique' => true));
		$this->mongo->user->ensureIndex(array('UserID' => 1), array('unique' => true));
		$this->mongo->packet->ensureIndex(array('ID' => 1), array('unique' => true));
		$this->mongo->packet->ensureIndex(array('Jid' => 1), array('unique' => true));

		foreach($this->config['DataType'] as $datatype => $content)
		{
			foreach($content['filter'] as $key)
			{
				if($key != 'ID' && $key != 'Jid')
				{
					$this->mongo->packet->ensureIndex(array($key => 1, 'ID' => 1));
				}
			}
		}
	}

	function loadDataType()
	{
		$isdatatype = false;
		$datatype = $this->packet{'Data'}{'DataType'};
		if ($this->config{'DataType'})
		{
			$datatype = $this->packet{'Data'}{'DataType'};
			$isdatatype = true;
		}
		else
		{
			$datatype = 'ProtoData';
		}
		require_once(__DIR__.'/../../../DataType/'.$datatype.'/'.$datatype.'.php');
		$this->datatype = new DataType();
		return $isdatatype;
	}

	function startSession($session, $userid, $privilege)
	{
		if(!$session)
		{
			$session = sha1(rand(0, 9e16).uniqid());
			$this->mongo->user->update(
			    array("UserID" => $userid),
			    array('$set' => array('Session'=>$session))
			);
		}
		setcookie("JNTP-Session", $session, time()+360000);

		$this->id = $userid;
		$this->userid = $userid.'@'.$this->config{'domain'};
		$this->privilege = $privilege;
		$this->session = $session;
	}

	// Initialise la session
	function setSession()
	{
		$session = $_COOKIE["JNTP-Session"];

		if(!$session)
		{
			$headers = getallheaders();
			$session = $headers["JNTP-Session"];
		}

		$obj = $this->mongo->user->findOne( array('Session' => $session) );
		if(count($obj) > 0)
		{
			$this->id = $obj{'UserID'};
			$this->userid = $obj{'UserID'}.'@'.$this->config{'domain'};
			$this->privilege = $obj{'privilege'};
			$this->session = $obj{'Session'};
		}
	}

	// Détruit la session
	function destroySession()
	{
		$this->mongo->user->update(
		    array("UserID" => $this->id),
		    array('$set' => array('Session'=>false))
		);
		$this->id = false;
		$this->userid = false;
		$this->privilege = false;
		$this->session = false;
	}

	// Vérifie la validité d'un packet JNTP
	function isValidPacket()
	{
		return true;
	}

	// Remplace les data longues par leur hash.
	function replaceHash( $packet )
	{
		if($packet{'Data'}{'Media'})
		{
			foreach($packet{'Data'}{'Media'} as $ind => $val)
			{
				foreach($packet{'Data'}{'Media'}[$ind] as $key => $value)
				{
					if($this->maxDataLength!=0 && is_string($packet{'Data'}{'Media'}[$ind][$key]) && strlen($packet{'Data'}{'Media'}[$ind][$key]) > $this->maxDataLength) 
					{
						$packet{'Data'}{'Media'}[$ind]['#'.$key] = $this->hashString($packet{'Data'}{'Media'}[$ind][$key]);
						unset ( $packet{'Data'}{'Media'}[$ind][$key] );
					}
				}
			}
		}
		return $packet;
	}

	// Fabrique le paquet JNTP qui encapsule la Data
	function forgePacket()
	{
		$this->packet{'Jid'} = $this->hashString( $this->canonicFormat($this->packet{'Data'}) ).'@'.$this->config{'domain'};
		if($this->packet{'Data'}{'DataID'} === "")
		{
			$this->packet{'Data'}{'DataID'} = $this->packet{'Jid'};
		}
		$this->packet{'Route'} = array();
		if(!$this->packet{'Meta'}) $this->packet{'Meta'} = array();

		if (!$privateKey = openssl_pkey_get_private(PRIVATE_KEY)) die('Loading Private Key failed');
		openssl_private_encrypt($this->packet{'Jid'}, $signature, $privateKey);
		
		$this->packet{'Meta'}{'ServerSign'} = base64_encode($signature);
		$this->packet{'Meta'}{'ServerPublicKey'} = PUBLIC_KEY;
	}

	// Insère le packet dans la base
	function insertPacket()
	{
		array_push($this->packet{'Route'}, $this->config{'domain'});
		$res = $this->mongo->counters->findAndModify(
			array("_id"=>"packetID"),
			array('$inc'=>array("seq"=>1)),     
			null,
			array("new" => true, "upsert"=>true)
		);

		$this->packet{'ID'} = $res['seq'];

		try {
			$this->mongo->packet->save($this->packet);
			return true;
		} catch(MongoCursorException $e) {
			return false;
		}
	}

	// Supprime un packet JNTP
	function deletePacket( $query )
	{
		return $this->mongo->packet->remove( $query );
	}

	// Récupère un packet JNTP
	function getPacket( $query )
	{
		return $this->mongo->packet->findOne( $query, array('_id'=>0) );
	}

	// Vérifie si un packet d'un Jid donné est stocké dans la base
	function isStorePacket( $query )
	{
		$bool = ($this->mongo->packet->find( $query )->count() > 0 ) ? true : false;
		return $bool;
	}

	// Retourne la ressource d'un packet requêtée au format URI ex : http://[server]/jntp/[Jid]/Data/FromName
	function getResource($path)
	{
		$tab = split('/', $path);
		$json = $this->getPacket( array('Jid'=>$tab[0]) );
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

		if($tab[2] == 'Media') // Spécifique à Article
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

	// Contacte les feeds pour distribuer $this->packet
	function superDiffuse()
	{
		foreach($this->config{'feed'} as $server => $value)
		{
			if(!$this->config{'feed'}{$server}{'actif'}) continue;
			$startFeed = false;
			foreach($this->config{'feed'}{$server}{'match'} as $key => $match)
			{
				if ($match === "*" || (is_array($match) && $match[0] === "*" ) ) 
				{
					$startFeed = true;
					continue;
				}
				$keys = explode(".", $key);
				$val = $this->packet;
				foreach($keys as $oneKey)
				{
					$val = $val{$oneKey};
				}

				if( 
					( $val === $match ) || 
					( is_array($match) && is_string($val) && (in_array($val, $match)) )   || 
					( is_array($match) && is_array($val) && count(array_diff($val, $match)) < count($val) )   
				)
				{
					$startFeed = true;
				}
				else
				{
					$startFeed = false;
					break;
				}
			}

			if(!$startFeed || in_array($server, $this->packet{'Route'})) continue;
			$jid = str_replace("'","\'",$this->packet{'Jid'});
			$datatype = str_replace("'","\'",$this->packet{'Data'}{'DataType'});
			$dataid = str_replace("'","\'",$this->packet{'Data'}{'DataID'});
			$cmd = PHP_PATH.' '.__DIR__.'/../../../connector/'.$this->config{'feed'}{$server}{'type'}[1].' '.$server." '$jid' '$dataid' '$datatype'";
			shell_exec($cmd. ' >> /dev/null &');
		}
	}

	function getIPs()
	{
		$record = dns_get_record ( $this->param{'From'}, DNS_ALL );
		$ip = array();
		for($i=0; $i < count($record); $i++)
		{

			if($record[$i]['type'] == 'A') 
			{
				array_push($ip, $record[$i]['ip']);
			}
			else if($record[$i]['type'] == 'AAAA') 
			{
				array_push($ip, $record[$i]['ipv6']);
			}
		}
		return $ip;
	}

	static function logFeed($post, $server, $direct = '<')
	{
		if(ACTIVE_LOG)
		{
			$post = is_array($post) ? json_encode($post) : $post;
			$handle = fopen(LOG_FEED_PATH, 'a');
			$put = '['.date(DATE_RFC822).'] ['.$server.'] '.$direct.' '.rtrim(mb_strimwidth($post, 0, 300))."\n";
			fwrite($handle, $put);
			fclose($handle);
		}
	}

	static function log($post, $direct = '<')
	{
		if(ACTIVE_LOG)
		{
			$handle = fopen(LOG_PATH, 'a');
			$put = '['.date(DATE_RFC822).'] ['.$_SERVER['REMOTE_ADDR'].'] '.$direct.' '.mb_strimwidth($post, 0, 300)."\n";
			fwrite($handle, $put);
			fclose($handle);
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
		if( $firstRecursivLevel ) return (json_encode(self::sortJSON($json), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return $json;
	}

	static function sortJSON($json)
	{
		if (is_array($json) ) 
		{
			ksort($json);
			foreach ($json as $key => $value) 
			{
				if(is_array($value) || is_int($key) )
				{
					$json[$key] = self::sortJSON($value);
				}
			}
		}
		return $json;
	}

	static function hashString($str)
	{
		return rtrim(strtr(base64_encode(sha1($str, true)), '+/', '-_'), '='); 
	}

}

