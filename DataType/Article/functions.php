<?php

function getThreadID()
{
	global $jntp;
	if(count($jntp->packet{'Data'}{'References'}) > 0)
	{
		$obj = $jntp->mongo->packet->findOne(   array("Data.ThreadID" => array('$exists'=>1), "Data.DataID" => array('$in'=>$jntp->packet{'Data'}{'References'})),
						array("Data.ThreadID" => 1) 
				     	    );
		return (count($obj) > 0) ? $obj{'Data'}{'ThreadID'} : $jntp->packet{'Data'}{'References'}[0];	
	}
	else
	{
		return $jntp->packet{'Data'}{'DataID'};	
	}
}

// Vérifie la validité d'un nemotag
function isValidNemoTag($groupe)
{
	global $jntp;
	if(strlen($groupe)>32)
	{
		$jntp->reponse{'body'} = "Le Nemotag [".$groupe."] est trop long, 32 caractères maxi";
		return false;
	}
	if (!preg_match('/^#[a-zA-Z]*$/', $groupe)) 
	{
		$jntp->reponse{'body'} = "Le Nemotag [".$groupe."] contient des caractères non autorisés";
		return false;
	}
	if($jntp->userid == false)
	{
		$jntp->reponse{'body'} = "Le Nemotag [".$groupe."] requiert une authentification";
		return false;
	}
	return true;
}

// Retourne les hiérarchies et les sous hiérarchies qui contiennent les newsgroups déclarés dans Data/Newsgroups
function getHierarchy()
{
	global $jntp;

	$hierarchy = array();
	foreach($jntp->packet{'Data'}{'Newsgroups'} as $oneGroup)
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
	global $jntp;
	if( $jntp->packet{'Data'}{'Control'} )
	{
		$typeCancel = $jntp->packet{'Data'}{'Control'}[0];
		if( $typeCancel === 'cancelUser' || $typeCancel === 'cancelServer' || $typeCancel === 'cancelLocal')
		{
			$dataid = $jntp->packet{'Data'}{'Control'}[1];

			if( $typeCancel === 'cancelUser' )
			{
				if(substr($article{'Data'}{'DataID'},0,27) === $article{'Jid'}) 
				{
					$article = $jntp->getPacket( array('Data.DataID'=>$dataid) );
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
					$data{'HashClient'} = $jntp->packet{'Data'}{'Control'}[2];

					$hashClient = $jntp->hashString( $jntp->canonicFormat($data) );

					if( $hashClient === $article{'Data'}{'HashClient'} )
					{
						$jntp->deletePacket( array('Data.DataID'=> $dataid) );
						return true;
					}
					else
					{
						$jntp->reponse{'code'} = "500";
						$jntp->reponse{'body'} = "Suppression impossible de ".$jid."\nhash ".$hashClient." incorrect";
						return false;
					}
				}
				else
				{
					$jntp->deletePacket( array('Data.DataID'=> $dataid) );
					return true;
				}
			}
			elseif( $typeCancel === 'cancelServer' )
			{
				if($jntp->param{'Data'})
				{
					if($jntp->privilege == "admin")
					{
						$jntp->deletePacket( array('Data.DataID'=> $dataid) );
						return true;
					}
					else
					{
						$jntp->reponse{'code'} = "500";
						$jntp->reponse{'body'} = "User not autorised to cancel";
						return false;
					}
				}
				else
				{
					$article = $jntp->getPacket( array('Data.DataID'=>$dataid) );
					if($article{'Data'}{'OriginServer'} == $jntp->param{'From'} )
					{
						$jntp->deletePacket( array('Data.DataID'=> $dataid) );
						return true;
					}
					else
					{
						if( in_array($jntp->param{'From'}, $jntp->config{'adminServer'} ) )
						{
							$jntp->deletePacket( array('Data.DataID'=> $dataid) );
							return true;
						}
						else
						{
							$jntp->reponse{'code'} = "500";
							$jntp->reponse{'body'} = "Server not autorised to cancel";
							return false;
						}
					}
				}
			}
			elseif( $typeCancel === 'cancelLocal' )
			{
				if($jntp->param{'Data'})
				{
					if($jntp->privilege == "admin")
					{
						$jntp->deletePacket( array('Data.DataID'=> $dataid) );
						return true;
					}
					else
					{
						$jntp->reponse{'code'} = "500";
						$jntp->reponse{'body'} = "User not autorised to make a local cancel";
						return false;
					}
				}
				else
				{
					$jntp->reponse{'code'} = "500";
					$jntp->reponse{'body'} = "Server not autorised to make a local cancel";
					return false;

				}
			}
		}
		else
		{
			$jntp->reponse{'code'} = "500";
			$jntp->reponse{'body'} = "Invalid operation : ".$jntp->packet{'Data'}{'Control'}[0];
			return false;
		}
	}
	return true;
}
