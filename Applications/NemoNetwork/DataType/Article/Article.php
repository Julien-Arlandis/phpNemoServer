<?php

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
	if( $jntp->packet{'Data'}{'Control'}[0] === 'cancel' )
	{
		$jid = $jntp->packet{'Data'}{'Control'}[1];
		$article = $jntp->getPacket($jid);

		if($article{'Data'}{'DataID'} === $article{'Jid'}) 
		{
			$data = null;
			$data{'DataType'} = $article{'Data'}{'DataType'};
			$data{'FromName'} = $article{'Data'}{'FromName'};
			$data{'FromMail'} = $article{'Data'}{'FromMail'};
			$data{'Subject'} = $article{'Data'}{'Subject'};
			$data{'References'} = $article{'Data'}{'References'};
			$data{'Newsgroups'} = $article{'Data'}{'Newsgroups'};
			$data{'UserAgent'} = $article{'Data'}{'UserAgent'};
			$data{'Body'} = $article{'Data'}{'Body'};
			$data{'Media'} = $article{'Data'}{'Media'};
			$data{'FollowupTo'} = $article{'Data'}{'FollowupTo'};
			$data{'HashClient'} = $jntp->packet{'Data'}{'Control'}[2];

			$hashClient = $jntp->hashString( $jntp->canonicFormat($data) );

			if( $hashClient === $article{'Data'}{'HashClient'} || $jntp->privilege == 'admin')
			{
				$jntp->deletePacket($jid);
				return true;
			}
			else
			{
				$jntp->reponse{'body'} = "Suppression impossible de ".$jid.", hash ".$hashClient." incorrect";
				return false;
			}
		}
		else
		{
			if( ($jntp->param{'Data'} && $jntp->privilege == 'admin') || $jntp->param{'Packet'} )
			{
				$jntp->deletePacket($jid);
				return true;
			}
			else
			{
				return false;
			}
		}
	}
	return true;
}

class DataType
{
	function __construct() 
	{
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

		if( $jntp->packet{'Data'}{'ThreadID'} == '' )
		{
			$jntp->packet{'Data'}{'ThreadID'} = sha1(uniqid().DOMAIN);
		}

		if ($jntp->userid)
		{
			$jntp->packet{'Data'}{'UserID'} = $jntp->userid;
		}
		else
		{
			$jntp->packet{'Data'}{'UserID'} = '0@'.$jntp->config{'domain'};
			$jntp->packet{'Data'}{'Body'} .= "\n\n[signature]Cet article a été rédigé depuis le serveur JNTP ".$jntp->config{'domain'}." par un utilisateur non inscrit [/signature]";
		}
	}

	function isValidData()
	{
		global $jntp;
		if( $jntp->packet{'Data'}{'Control'}[0] != 'cancel' )
		{
			if(count($jntp->packet{'Data'}{'FollowupTo'}) <= $jntp->config{'application'}{'NemoNetwork'}{'maxFU2'})
			{
				foreach($jntp->packet{'Data'}{'FollowupTo'} as $groupe)
				{
					if($groupe[0] != '#')
					{
						if(!$jntp->mongo->newsgroup->findOne(array('name' => $groupe), array('rules' => 1))) 
						{
							$jntp->reponse{'body'} = "Newsgroups [".$jntp->packet{'Data'}{'FollowupTo'}[0]."] inexistant";
							return false;
						}
					}
					else
					{
						isValidNemoTag($groupe);
					}
				}
			}
			else
			{
				$jntp->reponse{'body'} = $jntp->config{'application'}{'NemoNetwork'}{'maxFU2'}." redirections autorisées au maximum";
				return false;
			}
			if(count($jntp->packet{'Data'}{'FollowupTo'}) == 0 && count($jntp->packet{'Data'}{'Newsgroups'}) > $jntp->config{'application'}{'NemoNetwork'}{'maxCrosspostWithoutFU2'})
			{
				$jntp->reponse{'body'} = "Redirection requise";
				return false;
			}
			if (count($jntp->packet{'Data'}{'Newsgroups'}) > $jntp->config{'application'}{'NemoNetwork'}{'maxCrosspost'})
			{
				$jntp->reponse{'body'} = $jntp->config{'application'}{'NemoNetwork'}{'maxCrosspost'}." newsgroups maximum";
				return false;
			}
		}
		foreach($jntp->packet{'Data'}{'Newsgroups'} as $groupe)
		{
			if($groupe[0] != '#')
			{
				if($groupe != strtolower($groupe)) 
				{
					$jntp->reponse{'body'} = "Pas de majuscules dans le nom des groupes";
					return false;
				}
				$tab = $jntp->mongo->newsgroup->findOne(array('name' => $groupe), array('rules' => 1, 'rulesIfNotConnected'=>1));

				if(!$tab) 
				{ 
					$jntp->reponse{'body'} = "Newsgroups [".$groupe."] inexistant";
					return false;
				}
				if($tab['rules']['m'] == "1" && $jntp->privilege != "admin") 
				{
					$jntp->reponse{'body'} = "Le newsgroup [".$groupe."] est modéré";
					return false;
				}
				if(!$jntp->userid)
				{
					if(!$tab['rulesIfNotConnected']['w']=='1')
					{
						$jntp->reponse{'body'} = "Le newsgroup [".$groupe."] requiert une authentification";
						return false;
					}
				}
			}
			else
			{
				return isValidNemoTag($groupe);
			}
		}

		if( strlen($jntp->packet{'Data'}{'FromName'}) < 1 )
		{
			$jntp->reponse{'body'} = "Expéditeur absent";
			return false;
		}
		if( strlen($jntp->packet{'Data'}{'FromMail'}) < 1 )
		{
			$jntp->reponse{'body'} = "Email absent";
			return false;
		}
		$jntp->packet{'Data'}{'Subject'}; // String
		$jntp->packet{'Data'}{'Newsgroups'}; // Tableau(String)
		$jntp->packet{'Data'}{'FollowupTo'}; // String
		$jntp->packet{'Data'}{'References'}; // Tableau
		$jntp->packet{'Data'}{'UserAgent'}; // String
		$jntp->packet{'Data'}{'HashClient'}; // String
		if( strlen($jntp->packet{'Data'}{'Subject'}) < 1 )
		{
			$jntp->reponse{'body'} = "Sujet manquant";
			return false;
		}
		if( strlen($jntp->packet{'Data'}{'Body'}) < 1 )
		{
			$jntp->reponse{'body'} = "Article vide";
			return false;
		}
		if( !isset($jntp->packet{'Data'}{'ThreadID'}) )
		{
			$jntp->reponse{'body'} = "ThreadID manquant";
			return false;
		}
		$jntp->packet{'Data'}{'Media'}; // Tableau

		return true;
	}

	function beforeInsertion()
	{
		global $jntp;
		if(checkControl())
		{
			if($jntp->packet{'Data'}{'Protocol'} === 'JNTP-Transitional' )
			{
				$jntp->packet{'Data'}{'DataType'} = 'Article';
				// Affectation de Data/ThreadID
				if( !$jntp->packet{'Data'}{'ThreadID'} || !$jntp->packet{'Data'}{'ReferenceUserID'} )
				{
					$nb_ref = count($jntp->packet{'Data'}{'References'});
					if($nb_ref == 0) 
					{
						if( !$jntp->packet{'Data'}{'ThreadID'})
						{
							$jntp->packet{'Data'}{'ThreadID'} = $jntp->packet{'Jid'};
						}
					}
					else
					{
						// Affectation de Data/ReferenceUserID
						$packet = $jntp->getPacket($jntp->packet{'Data'}{'References'}[$nb_ref-1]);
						if(!$jntp->packet{'Data'}{'ReferenceUserID'} && $packet{'Data'}{'Protocol'} === "JNTP-Strict")
						{
							$jntp->packet{'Data'}{'ReferenceUserID'} = $packet{'Data'}{'UserID'};
						}
				
						// Affectation du ThreadID
						if($cursor = $jntp->mongo->article->findOne( array('Jid' => array('$in'=>$jntp->packet{'Data'}{'References'}) , 'Data.ThreadID'=>array('$exists'=>1)  ), array('Data.ThreadID'=>1) ))
						{
							$jntp->packet{'Data'}{'ThreadID'} = $cursor['Data']['ThreadID'];
						}
					}
				}

				// Suppression de l'ancien article supersédé.
				if($jid = $jntp->packet{'Data'}{'Supersedes'})
				{
					$article = $jntp->getPacket($jid);
					if($article{'Data'}{'Protocol'} === 'JNTP-Transitional') 
					{
						$jntp->deletePacket($jid);
					}
				}
			}

			$jntp->packet{'Meta'}{'Size'} = array(strlen($jntp->packet{'Data'}{'Body'}));
			$jntp->packet{'Meta'}{'Hierarchy'} = getHierarchy();
			$jntp->packet{'Meta'}{'Like'} = 0;
			if($jntp->packet{'Data'}{'Media'})
			{
				foreach($jntp->packet{'Data'}{'Media'} as $cle => $value)
				{
					$size = strlen($jntp->packet{'Data'}{'Media'}[$cle]{'data'});
					array_push($jntp->packet{'Meta'}{'Size'}, $size);
				}
			}
			return true;
		}
		else
		{
			$jntp->reponse{'code'} = "500";
			$jntp->reponse{'body'} =  $jntp->packet{'Jid'} . " : invalid control";
			return false;
		}
	}

	function afterInsertion()
	{
		global $jntp;

		$jntp->superDiffuse();

		if ($jntp->userid)
		{
			$jntp->updateUserConfig( array("FromName" => $jntp->packet{'Data'}{'FromName'}, "FromMail" => $jntp->packet{'Data'}{'FromMail'}, "ReplyTo" => $jntp->packet{'Data'}{'ReplyTo'}) );
		}

		return true;
	}
}
