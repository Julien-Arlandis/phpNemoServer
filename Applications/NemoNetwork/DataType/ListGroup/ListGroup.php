<?php

class DataType
{
	function __construct()
	{
	}

	function isValidData()
	{
		if(JNTP::$privilege != "admin")
		{
			JNTP::$reponse{'info'} = "Authentification required";
			return false;
		}
		return true;
	}

	function forgeData()
	{
		JNTP::$packet{'Data'}{'DataID'} = "@jntp";
		JNTP::$packet{'Data'}{'InjectionDate'} = date("Y-m-d")."T".date("H:i:s")."Z";
		JNTP::$packet{'Data'}{'OriginServer'} = JNTP::$config{'domain'};
		JNTP::$packet{'Data'}{'Organization'} = JNTP::$config{'organization'};
		JNTP::$packet{'Data'}{'Browser'} = $_SERVER['HTTP_USER_AGENT'];
		JNTP::$packet{'Data'}{'PostingHost'} = sha1($_SERVER['REMOTE_ADDR']);
		JNTP::$packet{'Data'}{'ComplaintsTo'} = JNTP::$config{'administrator'};
		JNTP::$packet{'Data'}{'ProtocolVersion'} = JNTP::$config{'protocolVersion'};
		JNTP::$packet{'Data'}{'Server'} = "PhpNemoServer/".JNTP::$config{'serverVersion'};

		if (JNTP::$userid)
		{
			JNTP::$packet{'Data'}{'UserID'} = JNTP::$userid;
		}
	}

	function beforeInsertion()
	{
		$cfg = json_decode(file_get_contents(__DIR__.'/../../conf/newsgroups.json'), true);

		if( JNTP::$packet{'Data'}{'DataType'} == 'ListGroup' && JNTP::$packet{'Data'}{'ListGroup'} )
		{
			$value = new MongoRegex("/^".preg_quote(substr(JNTP::$packet{'Data'}{'Hierarchy'},0,-1))."/");
			JNTP::$mongo->newsgroup->remove(array("name"=>$value));
			JNTP::$mongo->newsgroup->ensureIndex(array('name' => 1), array('unique' => true)); // à vérifier
			foreach(JNTP::$packet{'Data'}{'ListGroup'} as $cle => $obj)
			{
				if(isset($cfg['rules'][$obj['name']]))
				{
					$obj['rulesIfNotConnected'] = $cfg['rules'][$obj['name']]['rulesIfNotConnected'];
				}

				$obj['type'] = 'N';
				$obj['level'] = substr_count($obj['name'],'.') + 1;

				unset( $obj{'_id'} );
				try {
					JNTP::$mongo->newsgroup->save($obj);
				} catch(MongoCursorException $e) { }

				$hierarchies = getHierarchy(array($obj['name']));

				foreach($hierarchies as $oneHierarchy)
				{
					$obj2 = array();
					foreach($cfg{'categories'} as $desc => $value)
					{
						if( in_array($oneHierarchy, $cfg{'categories'}{$desc}{'Hierarchies'}) )
						{
							$obj2['category'] = $desc;
							$obj2['tri'] = $cfg->categories{$desc}{'tri'};
							break;
						}
					}
					$obj2['name'] = $oneHierarchy;
					$obj2['type'] = 'H';
					$obj2['level'] = substr_count($oneHierarchy,'.');
					try {
					JNTP::$mongo->newsgroup->save($obj2);
					} catch(MongoCursorException $e) { }
				}
			}
			return true;
		}
		return false;
	}

	function afterInsertion($idPacket)
	{
		JNTP::superDiffuse();
		return $idPacket;
	}
}

// Retourne les hiérarchies et les sous hiérarchies qui contiennent les newsgroups déclarés dans Data/Newsgroups
function getHierarchy($newsgroups = false)
{
	$hierarchy = array();
	foreach($newsgroups as $oneGroup)
	{
		if(substr($oneGroup, 0, 1) == '#')
		{
			array_push($hierarchy, '#*');
		}
		else
		{
			$tab = explode(".", $oneGroup);
			if(count($tab) >= 2)
			{
				$str = "";
				for ($i=0; $i<count($tab)-1; $i++)
				{
					$str .= $tab[$i].".";
					if( !in_array($str."*", $hierarchy) )
					{
						array_push($hierarchy, $str."*");
					}
				}
			}
		}
	}
	return $hierarchy;
}
