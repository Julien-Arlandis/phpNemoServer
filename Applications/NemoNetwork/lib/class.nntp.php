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

define('GATEWAY_VERSION', '0.96');

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

	static function articleJ2N($json)
	{
		if(!is_array($json))
		{
			die();
		}
		$isCancel = false;
		$notSupersedes = true;
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

		$article .= "Message-ID: <".$json{'Data'}{'DataID'}.">\r\n";
		$article .= "JNTP-Route: ".implode("|", $json{'Route'})."\r\n";

		foreach ($json{'Data'} as $cle=>$value) 
		{
			if(empty($value)) continue;

			if($cle === 'Control')
			{
				$control = 'cancel';
				$article .= "Control: ".$control." <".$value[1].">\r\n";
				$isCancel = true;

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
				$notSupersedes=false;
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
			elseif($cle === 'PostingHost')
			{
				// RFC 5536 <URL:https://tools.ietf.org/html/rfc5536> Header
				$article .= "Injection-Info: " . $json{'Data'}{'OriginServer'} . '; posting-host="'.$json{'Data'}{'PostingHost'}. '"; logging-data="' . $json{'ID'} . '"; posting-account="' . $json{'Data'}{'UserID'} . '"; mail-complaints-to="' . $json{'Data'}{'ComplaintsTo'}.'"'."\r\n";
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
				$body = str_replace('#DataID#', $json{'Data'}{'DataID'}, $body);
				$body = str_replace('#ThreadID#', $json{'Data'}{'ThreadID'}, $body);

				$body = preg_replace('/jntp:([A-Za-z0-9@.\/\_\-\:]+)/', '<http://'.$json{'Data'}{'OriginServer'}.'/jntp?$1>', $body);
				$body = str_replace("\r\n.\r\n", "\r\n..\r\n", $body);
			}
			elseif($cle === 'Media' || $cle === 'UserID' || $cle === 'DataID' || $cle === 'ComplaintsTo')
			{
				// Nothing
			}
			else
			{
				$value = str_replace('#Jid#', $json{'Jid'}, $value);
				$value = str_replace('#DataID#', $json{'Data'}{'DataID'}, $value);
				$value = str_replace("\r\n", "\n", $value);
				$article .= "JNTP-".$cle.": ".str_replace("\n", "\r\n ", $value)."\r\n";
			}
		}

		if(count($nemotags)!=0)
		{
			$article .= "JNTP-Nemotags: ".implode(",", $nemotags)."\r\n";
		}

		$article .= "MIME-Version: 1.0\r\n";
		$article .= "Content-Type: text/plain; charset=UTF-8; format=flowed\r\n";
		$article .= "Content-Transfer-Encoding: 8bit\r\n";
		$article .= "X-JNTP-JsonNewsGateway: ".GATEWAY_VERSION."\r\n";
		$article .= "From: ".$fromName." <".$fromMail.">\r\n";
		$article .= "\r\n";
		$article .= $body;

		if($isCancel && $notSupersedes)
		{
			return "Path: from-devjntp!cyberspam!usenet\r\n".$article;
		}
		else
		{
			return "Path: from-devjntp\r\n".$article;
		}
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
