<?php
class Tools
{

  static function logFeed($post, $server, $direct = '<')
	{
		if(JNTP::config{'activeLog'})
		{
			$post = is_array($post) ? json_encode($post) : $post;
			$handle = fopen(JNTP::config{'logFeedPath'}, 'a');
			$put = '['.date(DATE_RFC822).'] ['.$server.'] '.$direct.' '.rtrim(mb_strimwidth($post, 0, 300))."\n";
			fwrite($handle, $put);
			fclose($handle);
		}
	}

	static function log($post, $direct = '<')
	{
		if(JNTP::config{'activeLog'} && $post != '')
		{
			$handle = fopen(JNTP::config{'logPath'}, 'a');
			$put = '['.date(DATE_RFC822).'] ['.$_SERVER['REMOTE_ADDR'].'] '.$direct.' '.mb_strimwidth($post, 0, 300)."\n";
			fwrite($handle, $put);
			fclose($handle);
		}
	}

  static function getConfig()
	{
		JNTP::config = json_decode(file_get_contents(__DIR__.'/../../../conf/config.json'),true);
		JNTP::config{'outFeeds'} = json_decode(file_get_contents(__DIR__.'/../../../conf/feeds.json'),true);
		$dir = array_diff(scandir( __DIR__.'/../../../Applications/' ), array('..', '.'));
		foreach ($dir as $app)
		{
			JNTP::config{'Applications'}{$app} = json_decode(file_get_contents(__DIR__.'/../../'.$app.'/conf/conf.json'),true);
			foreach( JNTP::config{'Applications'}{$app}{'commands'} as $command)
			{
				JNTP::commandByApplication[$command] = $app;
			}
			foreach( JNTP::config{'Applications'}{$app}{'DataType'} as $datatype => $value)
			{
				JNTP::datatypeByApplication[$datatype] = $app;
			}
		}
	}

}
