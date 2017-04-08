<?php
$cfg = json_decode(file_get_contents(__DIR__.'/../../NemoNetwork/conf/newsgroups.json'));

if($jntp->privilege == 'admin')
{
	$jntp->mongo->newsgroup->remove();
	$jntp->mongo->newsgroup->save(array('description'=>'Newsgroup system', 'rules' => array("w" => "0", "m" => "1")));
	$body = array();
	for($i=0; $i<count($cfg->hierarchy); $i++)
	{
		for($j=0; $j<count($jntp->config{'publicServer'}); $j++)
		{
			// Mettre à jour la requête : Penser à remplacer les / par des ., total => limit
			$query = '["get", {"filter":{"Data.DataType":"ListGroup","Data.Hierarchy":"'.$cfg->hierarchy[$i].'"},"limit":1 } ]';
			$jntp->exec($query, $jntp->config{'publicServer'}[$j]);
			$jntp->packet = $jntp->reponse{'body'}[0];
			
			if(is_array($jntp->packet))
			{
  			$jntp->loadDataType();
  
  			if ( $jntp->datatype->beforeInsertion() )
  			{
  				// Insère le packet dans la base de données.
  				if($jntp->insertPacket())
  				{
  					$jntp->datatype->afterInsertion($jntp->packet{'ID'});
  				}
  			}
  			$jntp->insertPacket();
			  array_push($body, $cfg->hierarchy[$i]);
			  break;
			}
		}
	}
	$jntp->reponse{'body'} = $body;
	$jntp->reponse{'info'} = "Synchronisation done : ".count($body)." hiérarchies";
}
else
{
	$jntp->reponse{'code'} = "400";
	$jntp->reponse{'info'} = "Not autorised to synchronize";
}
