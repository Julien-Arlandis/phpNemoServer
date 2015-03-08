<?php

// Retourne les hiérarchies et les sous hiérarchies qui contiennent les newsgroups déclarés dans Data/Newsgroups
function getHierarchy($newsgroups = false)
{
	global $jntp;

	if(!$newsgroups)
	{
		$newsgroups = $jntp->packet{'Data'}{'Newsgroups'};
	}
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

class DataType
{
	function __construct() 
	{
	}

	function isValidData()
	{
		global $jntp;
		if($jntp->privilege != "admin")
		{
			$jntp->reponse{'body'} = "Cette action requiert une authentification";
			return false;
		}
		return true;
	}

	function forgeData()
	{
		global $jntp;

		$jntp->packet{'Data'}{'DataID'} = "";
		$jntp->packet{'Data'}{'InjectionDate'} = date("Y-m-d")."T".date("H:i:s")."Z";
		$jntp->packet{'Data'}{'OriginServer'} = $jntp->config{'domain'};
		$jntp->packet{'Data'}{'Organization'} = $jntp->config{'organization'};
		$jntp->packet{'Data'}{'Browser'} = $_SERVER['HTTP_USER_AGENT'];
		$jntp->packet{'Data'}{'PostingHost'} = sha1($_SERVER['REMOTE_ADDR']);
		$jntp->packet{'Data'}{'ComplaintsTo'} = $jntp->config{'administrator'};
		$jntp->packet{'Data'}{'ProtocolVersion'} = $jntp->config{'protocolVersion'};
		$jntp->packet{'Data'}{'Server'} = "PhpNemoServer/".$jntp->config{'serverVersion'};
		$jntp->packet{'Meta'}{'ServerPublicKey'} = PUBLIC_KEY;

		if ($jntp->userid)
		{
			$jntp->packet{'Data'}{'UserID'} = $jntp->userid;
		}
	}

	function beforeInsertion()
	{
		global $jntp;

		$cfg = json_decode(file_get_contents(__DIR__.'/../../conf/general.json'), true);

		if( $jntp->packet{'Data'}{'Newsgroups'}[0] == '@newsgroups' && $jntp->packet{'Data'}{'DataType'} == 'ListGroup' && $jntp->packet{'Data'}{'ListGroup'} )
		{
			$value = str_replace(".","\.", $jntp->packet{'Data'}{'Hierarchy'});
			$value = new MongoRegex("/^$value/");
			$jntp->mongo->newsgroup->remove(array("name"=>$value));

			foreach($jntp->packet{'Data'}{'ListGroup'} as $cle => $obj)
			{
				if($cfg->rules{$obj['name']})
				{
					$obj['rulesIfNotConnected'] = $cfg->rules{$obj['name']}{'rulesIfNotConnected'};
				}

				$obj['type'] = 'N';
				$obj['level'] = substr_count($obj['name'],'.') + 1;

				unset( $obj{'_id'} );
				try {
					$jntp->mongo->newsgroup->save($obj);
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
					$jntp->mongo->newsgroup->save($obj2);
					} catch(MongoCursorException $e) { }
				}
			}
		}

		$jntp->packet{'Meta'}{'Size'} = array(strlen($jntp->packet{'Data'}{'Body'}));
		$jntp->packet{'Meta'}{'Hierarchy'} = getHierarchy();
		if($jntp->packet{'Data'}{'Media'})
		{
			foreach($jntp->packet{'Data'}{'Media'} as $cle => $value)
			{
				$size = strlen($jntp->packet{'Data'}{'Media'}[$cle]{'data'});
				array_push($jntp->packet{'@Size'}, $size);
			}
		}
	}

	function afterInsertion($idPacket)
	{
		global $jntp;
		$jntp->superDiffuse();
		return $idPacket;
	}
}
