<?php
$cfg = json_decode(file_get_contents(__DIR__.'/../../../conf/newsgroups.json'));

$this->setSession();
if($this->privilege == 'admin')
{
	$this->mongo->newsgroup->remove();
	$this->mongo->newsgroup->save(array('description'=>'Newsgroup system', 'rules' => array("w" => "0", "m" => "1")));
	$body = array();
	for($i=0; $i<count($cfg->hierarchy); $i++)
	{
		for($j=0; $j<count($this->config{'publicServer'}); $j++)
		{
			// Mettre à jour la requête : Penser à remplacer les / par des ., total => limit
			$query = '["get", {"filter":{"Data.DataType":"ListGroup","Data.Hierarchy":"'.$cfg->hierarchy[$i].'"},"limit":1 } ]';
			$this->exec($query, $this->config{'publicServer'}[$j]);
			$this->packet = $this->reponse{'body'}[0];
			
			if(is_array($this->packet))
			{
  			$this->loadDataType();
  
  			if ( $this->datatype->beforeInsertion() )
  			{
  				// Insère le packet dans la base de données.
  				if($this->insertPacket())
  				{
  					$this->datatype->afterInsertion($this->packet{'ID'});
  				}
  			}
  			$this->insertPacket();
			  array_push($body, $cfg->hierarchy[$i]);
			  break;
			}
		}
	}
	$this->reponse{'body'} = $body;
	$this->reponse{'info'} = "Synchronisation done : ".count($body)." newsgroups";
}
else
{
	$this->reponse{'code'} = "400";
	$this->reponse{'info'} = "Not autorised to synchronize";
}
