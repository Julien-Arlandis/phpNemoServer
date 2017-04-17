<?php

require_once(__DIR__."/../Article/lib/functions.php");

class DataType
{
	function __construct()
	{
	}

	function isValidData()
	{
		global $jntp;
		return true;
	}

	function forgeData()
	{
		global $jntp;
		if( !$jntp->isStorePacket( array('Data.DataID' => $jntp->packet{'Data'}{'DataID'}) ) )
		{
			$article = array();
			$article{'Data'}{'DataID'} = $jntp->packet{'Data'}{'DataID'};
			$article{'Data'}{'DataType'} = "Article";
			$article{'Data'}{'References'} = array();
			$article{'Data'}{'FollowupTo'} = array();
			$article{'Data'}{'NNTPHeaders'} = null;
			$article{'Data'}{'Origin'} = "NNTP";
			$pos =  strpos($jntp->packet{'Data'}{'Body'}, "\n\n");

			$head = substr($jntp->packet{'Data'}{'Body'}, 0, $pos);
			$body = substr($jntp->packet{'Data'}{'Body'}, $pos+2);
			$lignes = preg_split("/\n(?![ \t])/", $head);
			$isContentType = false;
			$isInjectionDate = false;
			mb_internal_encoding('UTF-8');

			foreach ($lignes as $ligne)
			{
				$pos = strpos($ligne, ": ");
				$header = substr($ligne, 0, $pos);
				$champ = strtolower($header);
				$value = substr($ligne, $pos + 2);

				if($champ === "path" && strrpos($value, "!from-jntp"))
				{
					die();
				}
				elseif($champ === "jntp-protocol")
				{
					die();
				}
				elseif($champ === "control")
				{
					$args = array();
					$args = explode(" ", trim($value));

					if ($args[0] === 'cancel')
					{		
						$args[0] = 'cancelUser';
						$args[1] = substr($args[1], 1, strlen($args[1])-2);
						$article{'Data'}{'Control'} = $args;
					}
					elseif ($args[0] === 'newgroup')
					{
						$article{'Data'}{'Control'} = $args;
					}
					elseif ($args[0] === 'rmgroup')
					{
						$article{'Data'}{'Control'} = $args;
					}
					elseif ($args[0] === 'checkgroups')
					{
						$article{'Data'}{'Control'} = $args;
					}
				}
				elseif($champ === "supersedes") 
				{
					$article{'Data'}{'Supersedes'} = substr($value, 1, strlen($value)-2);
					$notSupersedes = false;
				}
				elseif($champ === "message-id") 
				{
					// nothing
				}
				elseif($champ === "from") 
				{
					$from = iconv_mime_decode($value, 2, 'UTF-8');
					preg_match('#<(.*?)>#', $from, $mail);
					preg_match('#\s*(.*?)\s*<#', $from, $name);
					$article{'Data'}{'FromName'} = "".$name[1];
					$article{'Data'}{'FromMail'} = ($mail[1]) ? $mail[1] : $from;

				}
				elseif($champ === "subject")
				{
					$article{'Data'}{'Subject'} = iconv_mime_decode($value, 2, 'UTF-8');
				}
				elseif($champ === "newsgroups")
				{
					$groupes = explode(",", $value);
					foreach($groupes as $groupe)
					{
						$groupe = trim($groupe);
					}
					$article{'Data'}{'Newsgroups'} = $groupes;
				}
				elseif($champ === "followup-to") 
				{
					$groupes = explode(",", $value);
					foreach($groupes as $groupe)
					{
						$groupe = trim($groupe);
					}
					$article{'Data'}{'FollowupTo'} = $groupes;
				}
				elseif($champ === "references")
				{
					$references = preg_split("/[<>, \n\t]+/", $value, 0, PREG_SPLIT_NO_EMPTY);
					if(count($references) > 0)
					{
						$article{'Data'}{'References'} = preg_split("/[<>, \n\t]+/", $value, 0, PREG_SPLIT_NO_EMPTY);
					}
				}
				elseif($champ === "user-agent")
				{
					$article{'Data'}{'UserAgent'} = $value;
				}
				elseif($champ === "reply-to")
				{
					$article{'Data'}{'ReplyTo'} = $value;
				}
				elseif($champ === "organization")
				{
					$article{'Data'}{'Organization'} = iconv_mime_decode($value, 2, 'UTF-8');
				}
				elseif($champ === "content-type")
				{
					$token = "[A-Za-z0-9\\-_.]+";
					$optFWS = "(?:\n?[ \t])*";
					$pattern = "charset{$optFWS}={$optFWS}(\"?)({$token})\\1";
					$regexp = "/;{$optFWS}{$pattern}{$optFWS}(?:[(;]|$)/i";
					$charset = preg_match($regexp, $value, $matches) ? $matches[2] : "UTF-8";
					$isContentType = true;
					$article{'Data'}{'NNTPHeaders'}{'Content-Type'} = $value;
				}
				elseif($champ === "content-transfer-encoding")
				{
					if(strstr(strtolower($value), "quoted-printable"))
					{
						$body = quoted_printable_decode($body);
					}
					elseif(strstr($value, "base64"))
					{
						$body = base64_decode($body);
					}
					$article{'Data'}{'NNTPHeaders'}{'Content-Transfer-Encoding'} = $value;
				}
				elseif($champ === "nntp-posting-date")
				{
					$article{'Data'}{'NNTPHeaders'}{'NNTP-Posting-Date'} = $value;
				}
				elseif($champ === "injection-date")
				{
					$article{'Data'}{'NNTPHeaders'}{'Injection-Date'} = $value;		
				}
				elseif($champ === "date")
				{
					$article{'Data'}{'NNTPHeaders'}{'Date'} = $value;
				}
				elseif($champ === "x-trace")
				{
					$article{'Data'}{'NNTPHeaders'}{'X-Trace'} = $value;
					if (strpos($value,"("))
					{
						$start = strpos($value,"(") + 1;
						$end =  strpos($value,")",$start);
						$xtracedate = substr($xtrace, $start, $end - $start);
					}
				}
				elseif ($champ !== "xref")
				{
					$article{'Data'}{'NNTPHeaders'}{$header} = $value;
				}
			}

			if(count($article{'Data'}{'References'}) == 0)
			{
				$article{'Data'}{'ThreadID'} = $article{'Jid'};
			}

			$article{'Data'}{'NNTPHeaders'}{'Gateway'} = "JsonNewsGateway/".GATEWAY_VERSION;
			if(!$isContentType){
				$charset = mb_detect_encoding($body);
				$article{'Data'}{'NNTPHeaders'}{'CharsetDetect'} = $charset;
			}

			if($article{'Data'}{'NNTPHeaders'}{'NNTP-Posting-Date'})
			{
				$injection_date = new DateTime($article{'Data'}{'NNTPHeaders'}{'NNTP-Posting-Date'});
				$injection_date->setTimezone(new DateTimeZone('UTC'));
			}
			elseif($article{'Data'}{'NNTPHeaders'}{'Injection-Date'})
			{
				$injection_date = new DateTime($article{'Data'}{'NNTPHeaders'}{'Injection-Date'});
				$injection_date->setTimezone(new DateTimeZone('UTC'));			
			}
			elseif(isset($xtracedate))
			{
				$injection_date = new DateTime($xtracedate);
				$injection_date->setTimezone(new DateTimeZone('UTC'));
			}
			elseif($article{'Data'}{'NNTPHeaders'}{'Date'})
			{
				$injection_date = new DateTime($article{'Data'}{'NNTPHeaders'}{'Date'});
				$injection_date->setTimezone(new DateTimeZone('UTC'));		
			}
			else
			{
				die();
			}

			$now = new DateTime("now");
			$now->setTimezone(new DateTimeZone('UTC'));

			if ($injection_date->getTimestamp() > $now->getTimestamp()) 
			{
				$injection_date = $now;
			}

			$article{'Data'}{'InjectionDate'} = $injection_date->format("Y-m-d\TH:i:s\Z");
			$body = preg_replace('/\n-- \n((.|\n)*|$)/', "\n["."signature]$1[/signature"."]", $body);
			$article{'Data'}{'Body'} = mb_convert_encoding($body, "UTF-8", $charset);
			$jntp->packet{'Data'} = $article{'Data'};
			return true;
		}
		else
		{
			$this->reponse{'code'} = "400";
			$this->reponse{'info'} = 'DataID ' . $jntp->packet{'Data'}{'DataID'} . " already inserted";
		}
	}

	function beforeInsertion()
	{
		global $jntp;
		if(checkControl())
		{
			$jntp->packet{'Meta'}{'Size'} = array(strlen($jntp->packet{'Data'}{'Body'}));
			//$jntp->packet{'Meta'}{'Hierarchy'} = getHierarchy();
			$jntp->packet{'Meta'}{'Like'} = 0;
			if(!$jntp->packet{'Data'}{'ThreadID'}) 
			{
				$jntp->packet{'Data'}{'ThreadID'} = getThreadID();
				$jntp->forgePacket();
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
		return true;
	}
}
