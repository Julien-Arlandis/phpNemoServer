<?php
$cfg = json_decode(file_get_contents(__DIR__.'/../../../conf/newsgroups.json'));

$this->setSession();

if($this->privilege == 'admin')
{
	$this->mongo->newsgroup->remove();
	$this->mongo->newsgroup->save(array('name'=>'@newsgroups','description'=>'Newsgroup system', 'rules' => array("w" => "0", "m" => "1")));
	$body = array();
	for($i=0; $i<count($cfg->hierarchy); $i++)
	{
		for($j=0; $j<count($this->config{'publicServer'}); $j++)
		{
			//Mettre à jour la requête : Penser à remplacer les / par des ., total => limit
			$query = '["get", {"filter":{"Data/Newsgroups":"@newsgroups","Data/DataType":"ListGroup","Data/Hierarchy":"'.$cfg->hierarchy[$i].'","total":"1"} } ]';
			$this->exec($query, $this->config{'publicServer'}[$j]);
			$this->packet = $this->reponse{'body'}[0];
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
	$this->reponse{'body'} = $body;
}
else
{
	$this->reponse{'code'} = "500";
	$this->reponse{'body'} = array("Not autorised to synchronize");
}
