<?php

require_once(__DIR__."/lib/functions.php");

class DataType extends NemoNetwork
{
	var $moderationArticle;
	var $publicKeyForModeration;

	function __construct()
	{
	}

	function forgeData()
	{
		JNTP::$packet{'Data'}{'DataID'} = "@jntp";
		JNTP::$packet{'Data'}{'OriginServer'} = JNTP::$config{'domain'};
		JNTP::$packet{'Data'}{'InjectionDate'} = date("Y-m-d")."T".date("H:i:s")."Z"; // à insérer dans class.jntp.php
		JNTP::$packet{'Data'}{'Organization'} = JNTP::$config{'organization'};
		JNTP::$packet{'Data'}{'Browser'} = $_SERVER['HTTP_USER_AGENT'];
		JNTP::$packet{'Data'}{'PostingHost'} = (JNTP::$config{'cryptPostingHost'} == "ifconnected" && !JNTP::$userid) ? $_SERVER['REMOTE_ADDR'] : sha1($_SERVER['REMOTE_ADDR']);
		JNTP::$packet{'Data'}{'ComplaintsTo'} = JNTP::$config{'administrator'};
		JNTP::$packet{'Data'}{'ProtocolVersion'} = JNTP::$config{'protocolVersion'};
		JNTP::$packet{'Data'}{'Server'} = "PhpNemoServer/".JNTP::$config{'serverVersion'};
		JNTP::$packet{'Meta'}{'ForAdmin'}{'IP'} = $_SERVER['REMOTE_ADDR'];
		if( JNTP::$packet{'Data'}{'ThreadID'} == '' )
		{
			JNTP::$packet{'Data'}{'ThreadID'} = JNTP::hashString(sha1(uniqid().JNTP::$config{'domain'}));
		}

		if (JNTP::$userid)
		{
			JNTP::$packet{'Data'}{'UserID'} = JNTP::$userid;
		}
		else
		{
			JNTP::$packet{'Data'}{'UserID'} = '0@'.JNTP::$config{'domain'};
			JNTP::$packet{'Data'}{'Body'} .= "\n\n[signature]Cet article a été rédigé depuis le serveur JNTP ".JNTP::$config{'domain'}." par un utilisateur non inscrit [/signature]";
		}
		if($this->moderationArticle)
		{
			JNTP::forgePacket();
			return forModeration($this);
		}
	}

	function isValidData()
	{
		if(count(JNTP::$packet{'Data'}{'FollowupTo'}) <= JNTP::$config{'Applications'}{'NemoNetwork'}{'maxFU2'})
		{
			foreach(JNTP::$packet{'Data'}{'FollowupTo'} as $groupe)
			{
				if($groupe[0] != '#')
				{
					if(!JNTP::$mongo->newsgroup->findOne(array('name' => $groupe), array('rules' => 1)))
					{
						JNTP::$reponse{'info'} = "Newsgroups [".JNTP::$packet{'Data'}{'FollowupTo'}[0]."] inexistant";
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
			JNTP::$reponse{'info'} = JNTP::$config{'Applications'}{'NemoNetwork'}{'maxFU2'}." redirections autorisées au maximum";
			return false;
		}
		if(count(JNTP::$packet{'Data'}{'FollowupTo'}) == 0 && count(JNTP::$packet{'Data'}{'Newsgroups'}) > JNTP::$config{'Applications'}{'NemoNetwork'}{'maxCrosspostWithoutFU2'})
		{
			JNTP::$reponse{'info'} = "Redirection requise";
			return false;
		}
		if (count(JNTP::$packet{'Data'}{'Newsgroups'}) > JNTP::$config{'Applications'}{'NemoNetwork'}{'maxCrosspost'})
		{
			JNTP::$reponse{'info'} = JNTP::$config{'Applications'}{'NemoNetwork'}{'maxCrosspost'}." newsgroups maximum";
			return false;
		}

		foreach(JNTP::$packet{'Data'}{'Newsgroups'} as $groupe)
		{
			if($groupe[0] != '#')
			{
				if($groupe != strtolower($groupe))
				{
					JNTP::$reponse{'info'} = "Pas de majuscules dans le nom des groupes";
					return false;
				}
				$tab = JNTP::$mongo->newsgroup->findOne(array('name' => $groupe));

				if(!$tab)
				{
					JNTP::$reponse{'info'} = "Newsgroups [".$groupe."] inexistant";
					return false;
				}
				if($tab['rules']['m'] == "1")
				{
					if($tab['PublicKey'])
					{
						$this->moderationArticle = true;
						$this->publicKeyForModeration = $tab['PublicKey'];
					}
					else
					{
						JNTP::$reponse{'info'} = "Le newsgroup [".$groupe."] est modéré, pas de clé publique définie";
						return false;
					}

				}
				if(!JNTP::$userid)
				{
					if(!$tab['rulesIfNotConnected']['w']=='1')
					{
						JNTP::$reponse{'info'} = "Le newsgroup [".$groupe."] requiert une authentification";
						return false;
					}
				}
			}
			else
			{
				return isValidNemoTag($groupe);
			}
		}

		if( strlen(JNTP::$packet{'Data'}{'FromName'}) < 1 )
		{
			JNTP::$reponse{'info'} = "Expéditeur absent";
			return false;
		}
		if( strlen(JNTP::$packet{'Data'}{'FromMail'}) < 1 )
		{
			JNTP::$reponse{'info'} = "Email absent";
			return false;
		}
		JNTP::$packet{'Data'}{'Subject'}; // String
		JNTP::$packet{'Data'}{'Newsgroups'}; // Tableau(String)
		JNTP::$packet{'Data'}{'FollowupTo'}; // String
		JNTP::$packet{'Data'}{'References'}; // Tableau
		JNTP::$packet{'Data'}{'UserAgent'}; // String
		JNTP::$packet{'Data'}{'HashClient'}; // String
		if( strlen(JNTP::$packet{'Data'}{'Subject'}) < 1 )
		{
			JNTP::$reponse{'info'} = "Sujet manquant";
			return false;
		}
		if( strlen(JNTP::$packet{'Data'}{'Body'}) < 1 )
		{
			JNTP::$reponse{'info'} = "Article vide";
			return false;
		}
		if( !isset(JNTP::$packet{'Data'}{'ThreadID'}) )
		{
			JNTP::$reponse{'info'} = "ThreadID manquant";
			return false;
		}
		JNTP::$packet{'Data'}{'Media'}; // Tableau
		return true;
	}

	function beforeInsertion()
	{
		if(checkControl())
		{
			JNTP::$packet{'Meta'}{'Size'} = array(strlen(JNTP::$packet{'Data'}{'Body'}));
			JNTP::$packet{'Meta'}{'Hierarchy'} = getHierarchy();
			JNTP::$packet{'Meta'}{'Like'} = 0;
			if(JNTP::$packet{'Data'}{'Media'})
			{
				foreach(JNTP::$packet{'Data'}{'Media'} as $cle => $value)
				{
					$size = strlen(JNTP::$packet{'Data'}{'Media'}[$cle]{'data'});
					array_push(JNTP::$packet{'Meta'}{'Size'}, $size);
				}
			}

			if(substr(JNTP::$packet{'Data'}{'DataID'},0,27) != JNTP::$packet{'Jid'})
			{
				$forgePacket = false;
				$msg = array();
				if(!JNTP::$packet{'Data'}{'ThreadID'})
				{
					array_push($msg, 'compute ThreadID by '.JNTP::$config{'domain'});
					JNTP::$packet{'Data'}{'ThreadID'} = getThreadID();
					$forgePacket = true;
				}
				if(!JNTP::$packet{'Data'}{'ReferenceUserID'} && $RefUserID = getReferenceUserID() )
				{
					array_push($msg, 'compute ReferenceUserID by '.JNTP::$config{'domain'});
					JNTP::$packet{'Data'}{'ReferenceUserID'} = $RefUserID;
					$forgePacket = true;
				}
				if($forgePacket)
				{
					JNTP::$packet{'Data'}{'HistoricForge'} = $msg;
					JNTP::forgePacket();
				}
			}
			return true;
		}
		return false;
	}

	function afterInsertion()
	{
		if(!JNTP::$stopSuperDiffuse)
		{
			JNTP::superDiffuse();
		}
		if (JNTP::$userid)
		{
		    $this->updateUserConfig( array(
		        "FromName" => JNTP::$packet{'Data'}{'FromName'},
		        "FromMail" => JNTP::$packet{'Data'}{'FromMail'},
		        "ReplyTo" => JNTP::$packet{'Data'}{'ReplyTo'}
		        )
		    );
		}
		return true;
	}
}
