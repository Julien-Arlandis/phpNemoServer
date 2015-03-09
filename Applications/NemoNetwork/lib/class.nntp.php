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

define('GATEWAY_VERSION', '0.95');

class NNTP
{
	function __construct() {}

	static function getArticleNNTP($mid, $server) 
	{
		$fp = fsockopen($server, 119, $errno, $errstr, 10);
		$rep = fgets($fp, 128);
		if (!$fp) { die ($errstr." ".$errno."\n"); }
		fputs($fp, "CHECK <".$mid.">\n");

		$reponses = preg_split("/[\s]+/", $reponse);
		if ($reponses[0] == "238")
		{
			$article = articleN2J($article);

		}
		fclose($fp);
	}

	static function articleN2J($txt)
	{
		$article = null;
		$article{'Jid'} = null;
		$article{'Route'} = array();
		$article{'Data'}{'DataType'} = "Article";
		$article{'Data'}{'References'} = array();
		$article{'Data'}{'FollowupTo'} = array();
		$article{'Data'}{'NNTPHeaders'} = null;
		$article{'Data'}{'Protocol'} = "JNTP-Transitional";
		$article{'Data'}{'Origin'} = "NNTP";

		$pos =  strpos($txt, "\n\n");
		$head = substr($txt, 0, $pos);
		$body = substr($txt, $pos+2);
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
			}
			elseif($champ === "message-id") 
			{
				$value = trim($value);
				$article{'Jid'} = substr($value, 1, strlen($value)-2);
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

		return $article;
	}

	static function articleJ2N($json)
	{
		if(!is_array($json) || $json{'Data'}{'DataID'} == $json{'Jid'})
		{
			die();
		}
		$wordwrap = true;
		$newsgroups = array();
		$nemotags = array();
		foreach ($json{'Data'}{'Newsgroups'} as $group) 
		{
			if(substr($group,0, 1) != '#')
			{
				array_push($newsgroups, $group);
			}
			else
			{
				array_push($nemotags, $group);
			}
		}

		if(count($newsgroups)==0)
		{
			die();
		}

		mb_internal_encoding('UTF-8');

		$article = "Path: !from-jntp\r\n";
		$article .= "Message-ID: <".$json{'Data'}{'DataID'}.">\r\n";
		$article .= "JNTP-Route: ".implode("|", $json{'Route'})."\r\n";

		foreach ($json{'Data'} as $cle=>$value) 
		{
			if(empty($value)) continue;

			if($cle === 'Control')
			{
				$article .= "Control: ".$value[0]." <".$value[1].">\r\n";
			}
			elseif($cle === 'FromName')
			{
				$fromName = mb_encode_mimeheader($value, mb_internal_encoding(), "Q", " \r\n");
			}
			elseif($cle === 'FromMail')
			{
				$fromMail = $value;
			}
			elseif($cle === 'Subject')
			{
				$article .= "Subject: ".mb_encode_mimeheader($value, mb_internal_encoding(), "Q", " \r\n")."\r\n";
			}
			elseif($cle === 'Newsgroups')
			{
				$article .= "Newsgroups: ".implode(",", $newsgroups)."\r\n";
			}
			elseif($cle === 'FollowupTo')
			{
				$article .= "Followup-To: ".implode(",", $value)."\r\n";
			}
			elseif($cle === 'InjectionDate')
			{
				$article .= "Date: ".date(DATE_RFC822, strtotime($value))."\r\n";
			}
			elseif($cle === 'References')
			{
				$refs = "<".implode("> <", $value).">";
	 			$refs = wordwrap($refs, 120, "\r\n ");

				$article .= "References: " . $refs . "\r\n";
			}
			elseif($cle === 'UserAgent')
			{
				$article .= "User-Agent: ".$value."\r\n";
			}
			elseif($cle === 'Organization')
			{
				$article .= "Organization: ".mb_encode_mimeheader($value, mb_internal_encoding(), "Q", " \r\n")."\r\n";
			}
			elseif($cle === 'Supersedes')
			{
				$article .= "Supersedes: <".$value.">\r\n";
			}
			elseif($cle === 'ComplaintsTo')
			{
				$article .= "X-Complaints-To: ".$value."\r\n";
			}
			elseif($cle === 'ReplyTo')
			{
				$article .= "Reply-To: ".$value."\r\n";
			}
			elseif($cle === 'GatewayOptions')
			{
				if( in_array("nowordwrap", $value) )
				{
					$wordwrap = false;
				}
			}
			elseif($cle === 'Body')
			{
				if($wordwrap)
				{
					$lines = explode("\n", $value);
					$body = array();
					foreach($lines as $line)
					{
						if(substr($line, 0, 1) != ">") 
						{
							array_push($body, wordwrap($line, 74, " \r\n"));
						}
						else
						{
							preg_match("(^>+ ?)", $line, $matches);
							array_push($body, wordwrap($line, 80+strlen($matches[0]), " \r\n".$matches[0]));
						}
					}
					$body = implode("\r\n", $body);
				}
				
				$body = preg_replace('/(\[youtube\])([a-zA-Z0-9_\-]*)(\[\/youtube\])/', "\r\nhttp://youtu.be/$2\r\n", $body);
				$body = preg_replace('/(\[signature\])(.*)(\[\/signature\])/s', "\r\n-- \r\n$2", $body);
				$body = preg_replace('/(\[dailymotion\])([a-zA-Z0-9_\-]*)(\[\/dailymotion\])/', "\r\nhttp://www.dailymotion.com/embed/video/$2\r\n", $body);
				$body = preg_replace('/(\[twitter\])(.*)(\[\/twitter\])/', "\r\nhttps://twitter.com/twitterapi/status/$2\r\n", $body);
				$pattern = '/\[\/?(a|img|file|pdf|tex|b|i|u|s|spoil|signature|abc|pgn|map|twitter|code|cite|audio|youtube|dailymotion|table|td|tr|font(=.{1,15})?|size(=.{1,3})?)\]/';
				$body = preg_replace($pattern, '', $body);

				$body = preg_replace_callback('/\[c=(x?[0-9a-fA-F]+)\]/', function ($matches) {
						return mb_convert_encoding('&#'.$matches[1].';', 'UTF-8', 'HTML-ENTITIES');
				},$body);

				$body = str_replace('#Uri#', $json{'Data'}{'Uri'}, $body);
				$body = str_replace('#Jid#', $json{'Jid'}, $body);
				$body = str_replace('#ThreadID#', $json{'Data'}{'ThreadID'}, $body);

				$body = preg_replace('/jntp:([a-f0-9]+@[.A-Za-z0-9\/\_\-\:]+)/', '<http://'.DOMAIN.'/jntp/$1>', $body);
				$body = str_replace("\r\n.\r\n", "\r\n..\r\n", $body);
			}
			elseif($cle === 'Media')
			{
				// Nothing
			}
			else
			{
				$value = str_replace('#Jid#', $json{'Jid'}, $value);
				$value = str_replace("\r\n", "\n", $value);
				$article .= "JNTP-".$cle.": ".str_replace("\n", "\r\n ", $value)."\r\n";
			}
			if(count($nemotags)!=0)
			{
				$article .= "JNTP-Nemotags: ".implode(",", $nemotags)."\r\n";
			}
		}
	
		$article .= "MIME-Version: 1.0\r\n";
		$article .= "Content-Type: text/plain; charset=UTF-8; format=flowed\r\n";
		$article .= "Content-Transfer-Encoding: 8bit\r\n";
		$article .= "X-JNTP-JsonNewsGateway: ".GATEWAY_VERSION."\r\n";
		$article .= "From: ".$fromName." <".$fromMail.">\r\n";
		$article .= "\r\n";
		$article .= $body;
		return $article;
	}

	static function logGateway($post, $server, $direct = '<')
	{
		if(ACTIVE_LOG)
		{
			$handle = fopen(LOG_FEED_PATH, 'a');
			$put = '['.date(DATE_RFC822).'] ['.$server.'] '.$direct.' '.rtrim(mb_strimwidth($post, 0, 300))."\n";
			fwrite($handle, $put);
			fclose($handle);
		}
	}
}
