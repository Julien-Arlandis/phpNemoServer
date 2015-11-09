<?php
$this->setSession();
// Query from Client
if($this->param{'Data'})
{
	$this->packet{'Data'} = $this->param{'Data'};

	// Check if Data.DataType is valid
	if ($this->loadDataType())
	{
		if( isset($this->param{'From'}) && (!$this->config['feed'][$this->param{'From'}]['actif'] == 1 || !in_array($_SERVER['REMOTE_ADDR'], $this->getIPs() )) )
		{
			$this->reponse{'code'} = "500";
			$this->reponse{'body'} = $_SERVER['REMOTE_ADDR']." not autorised to feed for ".$this->param{'From'};
		}
		else
		{
			// Check if Data is conform to DataType declaration
			if( $this->datatype->isValidData() )
			{
				// Traitment before insertion
				if ( $this->datatype->beforeInsertion() )
				{
					// Complete Data
					$this->datatype->forgeData();
					// Forge packet
					$this->forgePacket();
					// Insert packet in database
					$this->insertPacket();
					$this->datatype->afterInsertion($this->packet{'ID'});

					$this->reponse{'code'} = "200";
					$this->reponse{'body'}{'Data'}{'DataID'} = $this->packet{'Data'}{'DataID'};
					$this->reponse{'body'}{'Data'}{'DataType'} = $this->packet{'Data'}{'DataType'};
					$this->reponse{'body'}{'Jid'} = $this->packet{'Jid'};
					$this->reponse{'body'}{'ID'} = $this->packet{'ID'};
				}
			}
			else
			{
				$this->reponse{'code'} = "500";
			}
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

	// Check feed autorisation
	if( $this->config['feed'][$this->param{'From'}]['actif'] == 1 && in_array($_SERVER['REMOTE_ADDR'], $this->getIPs() ) )
	{
		$want = false;
		// Check if packet exists
		if( !isset($this->packet{'Data'}{'DataID'}) || $this->packet{'Jid'} == $this->packet{'Data'}{'DataID'} )
		{
			if( !$this->isStorePacket( array('Jid' => $this->packet{'Jid'}) ) )
			{
				$want = true;
			}
			else
			{
				$this->reponse{'code'} = "300";
				$this->reponse{'body'} = 'Jid ' . $this->packet{'Jid'} . " already inserted";
			}
		}
		else
		{
			if( !$this->isStorePacket( array('Data.DataID'=>$this->packet{'Data'}{'DataID'}, 'Data.DataType'=>$this->packet{'Data'}{'DataType'} ) ) )
			{
				$want = true;
			}
			else
			{
				$this->reponse{'code'} = "300";
				$this->reponse{'body'} = $this->packet{'Data'}{'DataType'} .':'. $this->packet{'Data'}{'DataID'} . " already inserted";
			}
		}

		if( $want )
		{
			if( $this->isValidPacket() )
			{
				// Traitment before insertion
				if ( $this->datatype->beforeInsertion() )
				{
					// Insert packet in database
					if($this->insertPacket())
					{
						$this->datatype->afterInsertion($this->packet{'ID'});
						$this->reponse{'code'} = "200";
						$this->reponse{'body'} = $this->packet{'Jid'} . " : inserted";
					}
					else
					{
						$this->reponse{'code'} = "300";
						$this->reponse{'body'} = $this->packet{'Jid'} . " : not inserted";
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
			$this->reponse{'code'} = "300";
			$this->reponse{'body'} = $this->packet{'Jid'} . " already inserted";
		}
	}
	else
	{
		$this->reponse{'code'} = "500";
		$this->reponse{'body'} = $_SERVER['REMOTE_ADDR']." not autorised to feed for ".$this->param{'From'};
		break;
	}
}
elseif($this->param{'Propose'})
{
	// Check feed autorisation
	if( $this->config['feed'][$this->param{'From'}]['actif'] == 1 && in_array($_SERVER['REMOTE_ADDR'], $this->getIPs() ) )
	{
		for($i=0; $i<count($this->param{'Propose'}); $i++)
		{
			$pack = $this->param{'Propose'}[$i];
			$res = array();
			if( !isset($pack{'Data'}{'DataID'}) || $pack{'Jid'} == $pack{'Data'}{'DataID'} )
			{
				if( !$this->isStorePacket( array('Jid' => $pack{'Jid'}) ) )
				{
					array_push($res, $pack{'Jid'});
				}
			}
			else
			{
				if( !$this->isStorePacket( array('Data.DataID'=>$pack{'Data'}{'DataID'}, 'Data.DataType'=>$pack{'Data'}{'DataType'} ) ) )
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
