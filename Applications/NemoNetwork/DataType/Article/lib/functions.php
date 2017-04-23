<?php

function getReferenceUserID()
{
	if(JNTP::$packet{'Data'}{'References'})
	{
		$nb_ref = count(JNTP::$packet{'Data'}{'References'});
		if($nb_ref > 0)
		{
			$ref = JNTP::$packet{'Data'}{'References'}[$nb_ref-1];
			if(strlen($ref) == 32 && substr($ref,27,5) == '@jntp')
			{
				$packet = JNTP::getPacket( array( 'Data.DataID' => $ref) );
				if ($packet{'Data'}{'UserID'})
				{
					return $packet{'Data'}{'UserID'};
				}

			}
		}
	}
	return false;
}

function forModeration($obj)
{
	global $obj;
	$key_iv = JNTP::randomKeyIv();
	$cryptPacket = JNTP::encryptAES256( json_encode(JNTP::$packet, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $key_iv );
	JNTP::$packet{'Data'}{'Media'} = array();
	JNTP::$packet{'Data'}{'Media'}[0]{'data'} = $cryptPacket;
	JNTP::$packet{'Data'}{'Media'}[0]{'PublicKey'} = $obj->publicKeyForModeration;
	$key_resource = openssl_get_publickey($obj->publicKeyForModeration);
	openssl_public_encrypt($key_iv, $encrypted, $key_resource);
	$encrypted = base64_encode($encrypted);

	JNTP::$packet{'Data'}{'Media'}[0]{'KeyAES256'} = $encrypted;
	JNTP::$packet{'Data'}{'Body'} = 'En attente de modération';
	JNTP::$packet{'Data'}{'Control'} = array('forModeration', JNTP::$packet{'Data'}{'DataID'});
	JNTP::$packet{'Data'}{'Subject'} = '[Non modéré]';
	JNTP::forgePacket();
	return true;
}

function getThreadID()
{
	if(count(JNTP::$packet{'Data'}{'References'}) > 0)
	{
		$obj = JNTP::$mongo->packet->findOne(   array("Data.ThreadID" => array('$exists'=>1), "Data.DataID" => array('$in'=>JNTP::$packet{'Data'}{'References'})),
						array("Data.ThreadID" => 1)
				     	    );
		return (count($obj) > 0) ? $obj{'Data'}{'ThreadID'} : JNTP::$packet{'Data'}{'References'}[0];
	}
	else
	{
		return JNTP::$packet{'Data'}{'DataID'};
	}
}

// Vérifie la validité d'un nemotag
function isValidNemoTag($groupe)
{
	if(strlen($groupe)>32)
	{
		JNTP::$reponse{'info'} = "Le Nemotag [".$groupe."] est trop long, 32 caractères maxi";
		return false;
	}
	if (!preg_match('/^#[a-zA-Z]*$/', $groupe))
	{
		JNTP::$reponse{'info'} = "Le Nemotag [".$groupe."] contient des caractères non autorisés";
		return false;
	}
	if(JNTP::$userid == false)
	{
		JNTP::$reponse{'info'} = "Le Nemotag [".$groupe."] requiert une authentification";
		return false;
	}
	return true;
}

// Retourne les hiérarchies et les sous hiérarchies qui contiennent les newsgroups déclarés dans Data.Newsgroups
function getHierarchy()
{
	$hierarchy = array();
	foreach(JNTP::$packet{'Data'}{'Newsgroups'} as $oneGroup)
	{
		if(substr($oneGroup, 0, 1) == '#' && !in_array("#*", $hierarchy))
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

// Effectue le traitement de Data/Control (suppression d'article).
function checkControl()
{
	if( JNTP::$packet{'Data'}{'Control'} )
	{
		$typeCancel = JNTP::$packet{'Data'}{'Control'}[0];
		if( $typeCancel === 'cancelUser' || $typeCancel === 'cancelServer' || $typeCancel === 'cancelLocal')
		{
			$dataid = JNTP::$packet{'Data'}{'Control'}[1];

			if( $typeCancel === 'cancelUser' )
			{
				if(substr($article{'Data'}{'DataID'},0,27) === $article{'Jid'})
				{
					$article = JNTP::getPacket( array('Data.DataID'=>$dataid) );
					$data = null;
					$data{'DataType'} = $article{'Data'}{'DataType'};
					$data{'FromName'} = $article{'Data'}{'FromName'};
					$data{'FromMail'} = $article{'Data'}{'FromMail'};
					$data{'Subject'} = $article{'Data'}{'Subject'};
					$data{'References'} = $article{'Data'}{'References'};
					$data{'Newsgroups'} = $article{'Data'}{'Newsgroups'};
					$data{'Body'} = $article{'Data'}{'Body'};
					$data{'Media'} = $article{'Data'}{'Media'};
					$data{'FollowupTo'} = $article{'Data'}{'FollowupTo'};
					$data{'HashClient'} = JNTP::$packet{'Data'}{'Control'}[2];

					$hashClient = JNTP::hashString( JNTP::canonicFormat($data) );

					if( $hashClient === $article{'Data'}{'HashClient'} )
					{
						JNTP::deletePacket( array('Data.DataID'=> $dataid) );
						return true;
					}
					else
					{
						JNTP::$reponse{'code'} = "400";
						JNTP::$reponse{'info'} = "Suppression impossible de ".$jid."\nhash ".$hashClient." incorrect";
						return false;
					}
				}
				else
				{
					JNTP::deletePacket( array('Data.DataID'=> $dataid) );
					return true;
				}
			}
			elseif( $typeCancel === 'cancelServer' )
			{
				if(JNTP::$param{'Data'})
				{
					if(JNTP::$privilege == "admin" || JNTP::$privilege == "moderator")
					{
						JNTP::deletePacket( array('Data.DataID'=> $dataid) );
						return true;
					}
					else
					{
						JNTP::$reponse{'code'} = "400";
						JNTP::$reponse{'info'} = "User not autorised to cancel";
						return false;
					}
				}
				else
				{
					$article = JNTP::getPacket( array('Data.DataID'=>$dataid) );
					if($article{'Data'}{'OriginServer'} == JNTP::$param{'From'} )
					{
						JNTP::deletePacket( array('Data.DataID'=> $dataid) );
						return true;
					}
					else
					{
						if( in_array(JNTP::$param{'From'}, JNTP::$config{'adminServer'} ) )
						{
							JNTP::deletePacket( array('Data.DataID'=> $dataid) );
							return true;
						}
						else
						{
							JNTP::$reponse{'code'} = "400";
							JNTP::$reponse{'info'} = "Server not autorised to cancel";
							return false;
						}
					}
				}
			}
			elseif( $typeCancel === 'cancelLocal' )
			{
				if(JNTP::$param{'Data'})
				{
					if(JNTP::$privilege == "admin" || JNTP::$privilege == "moderator" )
					{
						JNTP::deletePacket( array('Data.DataID'=> $dataid) );
						return true;
					}
					else
					{
						JNTP::$reponse{'code'} = "400";
						JNTP::$reponse{'info'} = "User not autorised to make a local cancel";
						return false;
					}
				}
				else
				{
					JNTP::$reponse{'code'} = "400";
					JNTP::$reponse{'info'} = "Server not autorised to make a local cancel";
					return false;

				}
				JNTP::$stopSuperDiffuse = true;
			}
		}
		else
		{
			JNTP::$reponse{'code'} = "400";
			JNTP::$reponse{'info'} = "Invalid operation : ".JNTP::$packet{'Data'}{'Control'}[0];
			return false;
		}
	}
	return true;
}
