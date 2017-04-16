<?php
class Tools
{

  static function logFeed($post, $server, $direct = '<')
	{
		if(JNTP::$config{'activeLog'})
		{
			$post = is_array($post) ? json_encode($post) : $post;
			$handle = fopen(JNTP::$config{'logFeedPath'}, 'a');
			$put = '['.date(DATE_RFC822).'] ['.$server.'] '.$direct.' '.rtrim(mb_strimwidth($post, 0, 300))."\n";
			fwrite($handle, $put);
			fclose($handle);
		}
	}

	static function log($post, $direct = '<')
	{
		if(JNTP::$config{'activeLog'} && $post != '')
		{
			$handle = fopen(JNTP::$config{'logPath'}, 'a');
			$put = '['.date(DATE_RFC822).'] ['.$_SERVER['REMOTE_ADDR'].'] '.$direct.' '.mb_strimwidth($post, 0, 300)."\n";
			fwrite($handle, $put);
			fclose($handle);
		}
	}

  static function getConfig()
	{
		JNTP::$config = json_decode(file_get_contents(__DIR__.'/../conf/config.json'),true);
		JNTP::$config{'outFeeds'} = json_decode(file_get_contents(__DIR__.'/../conf/feeds.json'),true);
		$dir = array_diff(scandir( __DIR__.'/../Applications/' ), array('..', '.'));
		foreach ($dir as $app)
		{
      if ($app[0] == '.') continue;
			JNTP::$config{'Applications'}{$app} = json_decode(file_get_contents(__DIR__.'/../Applications/'.$app.'/conf/conf.json'),true);
			foreach( JNTP::$config{'Applications'}{$app}{'commands'} as $command)
			{
				JNTP::$commandByApplication[$command] = $app;
			}
			foreach( JNTP::$config{'Applications'}{$app}{'DataType'} as $datatype => $value)
			{
				JNTP::$datatypeByApplication[$datatype] = $app;
			}
		}
	}

  static function sortJSON($json)
	{
		if (is_array($json) )
		{
			ksort($json);
			foreach ($json as $key => $value)
			{
				if(is_array($value) || is_int($key) )
				{
					$json[$key] = self::sortJSON($value);
				}
			}
		}
		return $json;
	}

  static function getIPs()
	{
		$record = dns_get_record ( JNTP::$param{'From'}, DNS_ALL );
		$ip = array();
		for($i=0; $i < count($record); $i++)
		{
			if($record[$i]['type'] == 'A')
			{
				array_push($ip, $record[$i]['ip']);
			}
			else if($record[$i]['type'] == 'AAAA')
			{
				array_push($ip, $record[$i]['ipv6']);
			}
		}
		return $ip;
	}

  static function getTpl($tpl, $assign)
	{
	  $tpl = file_get_contents($tpl);
	  foreach ($assign as $key => $value)
	  {
	    $tpl = str_replace('%'.$key.'%', $value, $tpl);
	  }
	  return $tpl;
	}

}