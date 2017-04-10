<?php
$cfg = json_decode(file_get_contents(__DIR__.'/../../NemoNetwork/conf/newsgroups.json'));

if(JNTP::$privilege == 'admin')
{
	JNTP::$mongo->newsgroup->remove();
	JNTP::$mongo->newsgroup->save(array('description'=>'Newsgroup system', 'rules' => array("w" => "0", "m" => "1")));
	$body = array();
	for($i=0; $i<count($cfg->hierarchy); $i++)
	{
		for($j=0; $j<count(JNTP::$config{'publicServer'}); $j++)
		{
			$query = '["get", {"filter":{"Data.DataType":"ListGroup","Data.Hierarchy":"'.$cfg->hierarchy[$i].'"},"limit":1 } ]';
			JNTP::exec($query, JNTP::$config{'publicServer'}[$j]);
			JNTP::$packet = JNTP::$reponse{'body'}[0];

			if(is_array(JNTP::$packet))
			{
				JNTP::loadDataType();

				if ( JNTP::$datatype->beforeInsertion() )
				{
					JNTP::insertPacket();
					array_push($body, $cfg->hierarchy[$i]);
					break;
				}
			}
		}
	}
	JNTP::$reponse{'body'} = $body;
	JNTP::$reponse{'info'} = "Synchronisation done : ".count($body)." hiérarchies";
}
else
{
	JNTP::$reponse{'code'} = "400";
	JNTP::$reponse{'info'} = "Not autorised to synchronize";
}
