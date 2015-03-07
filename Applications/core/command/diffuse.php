<?php
$this->setSession();
// Query from Client
if($this->param{'Data'})
{
	$this->packet{'Data'} = $this->param{'Data'};

	// Check if /Data/DataType is valid
	if ($this->loadDataType())
	{
		// Check if Data is conform to DataType declaration
		if( $this->datatype->isValidData() )
		{
			// Effectue les actions déclarées dans le Data/Control.
			if ( $this->datatype->beforeInsertion() )
			{
				// Complète la Data
				$this->datatype->forgeData();
				// Fabrique le packet
				$this->forgePacket();
				// Insère le packet dans la base de données.
				$this->insertPacket();
				$this->datatype->afterInsertion($this->packet{'ID'});

				$this->reponse{'code'} = "200";
				$this->reponse{'body'}{'Jid'} = $this->packet{'Jid'};
				$this->reponse{'body'}{'ID'} = $this->packet{'ID'};
			}
		}
		else
		{
			$this->reponse{'code'} = "500";
		}
	}
	else
	{
		$this->reponse{'code'} = "500";
		$this->reponse{'body'} = "DataType ".$this->packet{'Data'}{'DataType'}." non pris en charge";
	}
}

// Query from Server
elseif($this->param{'Packet'})
{
	$this->packet = $this->param{'Packet'};

	$this->loadDataType();
	$feeds = $this->getFeeds();

	// Vérifie si le feed est autorisé
	if( $feeds{$this->param{'From'}}{'actif'} == 1 && in_array($_SERVER['REMOTE_ADDR'], $this->getIPs() ) )
	{
		// Vérifie si le paquet est déjà dans la base
		if(!$this->isStorePacket($this->packet{'Jid'}) )
		{
			if( $this->isValidPacket() )
			{
				// Effectue les actions déclarées dans le Data/Control.
				if ( $this->datatype->beforeInsertion() )
				{
					// Insère le packet dans la base de données.
					if($this->insertPacket())
					{
						$this->datatype->afterInsertion($this->packet{'ID'});
						$this->reponse{'code'} = "200";
						$this->reponse{'body'} = $this->packet{'Jid'} . " : inserted";
					}
				}
			}
			else
			{
				$this->reponse{'code'} = "500";
				$this->reponse{'body'} = "invalid packet";
			}
		}
		else
		{
			$this->reponse{'code'} = "200";
			$this->reponse{'body'} = $this->packet{'Jid'} . " already inserted";
		}
	}
	else
	{
		$this->reponse{'code'} = "500";
		$this->reponse{'body'} = $_SERVER['REMOTE_ADDR']." not autorised to feed";
		break;
	}
}
elseif($this->param{'Propose'})
{
	// Vérifie si le feed est autorisé
	if( $this->param{'From'} == 'julien' || ($this->config['feed'][$this->param{'From'}]['actif'] == 1 && in_array($_SERVER['REMOTE_ADDR'], $this->getIPs() ) ) )
	{
		for($i=0; $i<count($this->param{'Propose'}); $i++)
		{
			$pack = $this->param{'Propose'}[$i];
			$res = array();
			if( !isset($pack{'Data'}{'DataID'}) || $pack{'Jid'} == $pack{'Data'}{'DataID'} )
			{
				if( $this->mongo->packet->find(array('Jid'=>$pack{'Jid'}))->count() == 0)
				{
					array_push($res, $pack{'Jid'});
				}
			}
			else
			{
				if( $this->mongo->packet->find(array('Data.DataID'=>$pack{'Data'}{'DataID'}, 'Data.DataType'=>$pack{'Data'}{'DataType'} ))->count() == 0)
				{
					array_push($res, $pack{'Jid'});
				}
			}
		}

		$commande = array();
		$commande[0] = "iwant";
		$commande[1]{'Jid'} = $res;
		die(json_encode($commande));
	}
	else
	{
		$this->reponse{'code'} = "500";
		$this->reponse{'body'} = $_SERVER['REMOTE_ADDR']." not autorised to propose for ".$this->param{'From'};
	}
}
