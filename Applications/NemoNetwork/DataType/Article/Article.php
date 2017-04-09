<?php

require_once(__DIR__."/functions.php");

class DataType
{
	var $moderationArticle;

	function __construct()
	{
	}

	function forgeData()
	{
		global $jntp;
		$jntp->packet{'Data'}{'DataID'} = "@jntp";
		$jntp->packet{'Data'}{'OriginServer'} = $jntp->config{'domain'};
		$jntp->packet{'Data'}{'InjectionDate'} = date("Y-m-d")."T".date("H:i:s")."Z";
		$jntp->packet{'Data'}{'Organization'} = $jntp->config{'organization'};
		$jntp->packet{'Data'}{'Browser'} = $_SERVER['HTTP_USER_AGENT'];
		$jntp->packet{'Data'}{'PostingHost'} = ($jntp->config{'cryptPostingHost'} == "ifconnected" && !$jntp->userid) ? $_SERVER['REMOTE_ADDR'] : sha1($_SERVER['REMOTE_ADDR']);
		$jntp->packet{'Data'}{'ComplaintsTo'} = $jntp->config{'administrator'};
		$jntp->packet{'Data'}{'ProtocolVersion'} = $jntp->config{'protocolVersion'};
		$jntp->packet{'Data'}{'Server'} = "PhpNemoServer/".$jntp->config{'serverVersion'};
		$jntp->packet{'Meta'}{'ForAdmin'}{'IP'} = $_SERVER['REMOTE_ADDR'];
		if( $jntp->packet{'Data'}{'ThreadID'} == '' )
		{
			$jntp->packet{'Data'}{'ThreadID'} = $jntp->hashString(sha1(uniqid().$jntp->config{'domain'}));
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
		if($this->moderationArticle)
		{
			$jntp->forgePacket();
			return forModeration();
		}
	}

	function isValidData()
	{
		global $jntp;
		if(count($jntp->packet{'Data'}{'FollowupTo'}) <= $jntp->config{'Applications'}{'NemoNetwork'}{'maxFU2'})
		{
			foreach($jntp->packet{'Data'}{'FollowupTo'} as $groupe)
			{
				if($groupe[0] != '#')
				{
					if(!$jntp->mongo->newsgroup->findOne(array('name' => $groupe), array('rules' => 1)))
					{
						$jntp->reponse{'info'} = "Newsgroups [".$jntp->packet{'Data'}{'FollowupTo'}[0]."] inexistant";
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
			$jntp->reponse{'info'} = $jntp->config{'Applications'}{'NemoNetwork'}{'maxFU2'}." redirections autorisées au maximum";
			return false;
		}
		if(count($jntp->packet{'Data'}{'FollowupTo'}) == 0 && count($jntp->packet{'Data'}{'Newsgroups'}) > $jntp->config{'Applications'}{'NemoNetwork'}{'maxCrosspostWithoutFU2'})
		{
			$jntp->reponse{'info'} = "Redirection requise";
			return false;
		}
		if (count($jntp->packet{'Data'}{'Newsgroups'}) > $jntp->config{'Applications'}{'NemoNetwork'}{'maxCrosspost'})
		{
			$jntp->reponse{'info'} = $jntp->config{'Applications'}{'NemoNetwork'}{'maxCrosspost'}." newsgroups maximum";
			return false;
		}

		foreach($jntp->packet{'Data'}{'Newsgroups'} as $groupe)
		{
			if($groupe[0] != '#')
			{
				if($groupe != strtolower($groupe))
				{
					$jntp->reponse{'info'} = "Pas de majuscules dans le nom des groupes";
					return false;
				}
				$tab = $jntp->mongo->newsgroup->findOne(array('name' => $groupe));

				if(!$tab)
				{
					$jntp->reponse{'info'} = "Newsgroups [".$groupe."] inexistant";
					return false;
				}
				if($tab['rules']['m'] == "1")
				{
					if($tab['PublicKey'])
					{
						$this->moderationArticle = true;
						$jntp->publicKeyForModeration = $tab['PublicKey'];
					}
					else
					{
						$jntp->reponse{'info'} = "Le newsgroup [".$groupe."] est modéré, pas de clé publique définie";
						return false;
					}
					
				}
				if(!$jntp->userid)
				{
					if(!$tab['rulesIfNotConnected']['w']=='1')
					{
						$jntp->reponse{'info'} = "Le newsgroup [".$groupe."] requiert une authentification";
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
			$jntp->reponse{'info'} = "Expéditeur absent";
			return false;
		}
		if( strlen($jntp->packet{'Data'}{'FromMail'}) < 1 )
		{
			$jntp->reponse{'info'} = "Email absent";
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
			$jntp->reponse{'info'} = "Sujet manquant";
			return false;
		}
		if( strlen($jntp->packet{'Data'}{'Body'}) < 1 )
		{
			$jntp->reponse{'info'} = "Article vide";
			return false;
		}
		if( !isset($jntp->packet{'Data'}{'ThreadID'}) )
		{
			$jntp->reponse{'info'} = "ThreadID manquant";
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
			
			if(substr($jntp->packet{'Data'}{'DataID'},0,27) != $jntp->packet{'Jid'})
			{
				$forgePacket = false;
				$msg = array();
				if(!$jntp->packet{'Data'}{'ThreadID'})
				{
					array_push($msg, 'compute ThreadID by '.$jntp->config{'domain'});
					$jntp->packet{'Data'}{'ThreadID'} = getThreadID();
					$forgePacket = true;
				}
				if(!$jntp->packet{'Data'}{'ReferenceUserID'} && $RefUserID = getReferenceUserID() )
				{
					array_push($msg, 'compute ReferenceUserID by '.$jntp->config{'domain'});
					$jntp->packet{'Data'}{'ReferenceUserID'} = $RefUserID;
					$forgePacket = true;
				}
				if($forgePacket) 
				{
					$jntp->packet{'Data'}{'HistoricForge'} = $msg;
					$jntp->forgePacket();
				}
			}
			return true;
		}
		return false;
	}

	function afterInsertion()
	{
		global $jntp;
		if(!$jntp->stopSuperDiffuse)
		{
			$jntp->superDiffuse();
		}
		if ($jntp->userid)
		{
			$jntp->updateUserConfig( array("FromName" => $jntp->packet{'Data'}{'FromName'}, "FromMail" => $jntp->packet{'Data'}{'FromMail'}, "ReplyTo" => $jntp->packet{'Data'}{'ReplyTo'}) );
		}
		return true;
	}
}
