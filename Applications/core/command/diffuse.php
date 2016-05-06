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
			$this->reponse{'code'} = "400";
			$this->reponse{'info'} = $_SERVER['REMOTE_ADDR']." not autorised to diffuse Data for ".$this->param{'From'};
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
					$this->reponse{'info'} = 'Diffuse '.$this->packet{'Data'}{'DataID'}.' OK';
				}
			}
			else
			{
				$this->reponse{'code'} = "400";
			}
		}
	}
	else
	{
		$this->reponse{'code'} = "400";
		$this->reponse{'info'} = "DataType ".$this->packet{'Data'}{'DataType'}." unsupported";
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
		if( !isset($this->packet{'Data'}{'DataID'}) || $this->packet{'Jid'} == substr($this->packet{'Data'}{'DataID'},0,27) )
		{
			if( !$this->isStorePacket( array('Jid' => $this->packet{'Jid'}) ) )
			{
				$want = true;
			}
			else
			{
				$this->reponse{'code'} = "400";
				$this->reponse{'info'} = 'Jid ' . $this->packet{'Jid'} . " already inserted";
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
				$this->reponse{'code'} = "400";
				$this->reponse{'info'} = $this->packet{'Data'}{'DataType'} .':'. $this->packet{'Data'}{'DataID'} . " already inserted";
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
						$this->reponse{'info'} = $this->packet{'Jid'} . " : inserted";
					}
					else
					{
						$this->reponse{'code'} = "400";
						$this->reponse{'info'} = $this->packet{'Jid'} . " : not inserted";
					}
				}
			}
			else
			{
				$this->reponse{'code'} = "400";
				$this->reponse{'info'} = "invalid packet";
			}
		}
		else
		{
			$this->reponse{'code'} = "400";
			$this->reponse{'info'} = $this->packet{'Jid'} . " already inserted";
		}
	}
	else
	{
		$this->reponse{'code'} = "400";
		$this->reponse{'info'} = $_SERVER['REMOTE_ADDR']." not autorised to feed for ".$this->param{'From'};
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
			$jid = array();
			$dataid = array();
			if( !isset($pack{'Data'}{'DataID'}) || $pack{'Jid'} == substr($pack{'Data'}{'DataID'},0,27) )
			{
				if( !$this->isStorePacket( array('Jid' => $pack{'Jid'}) ) )
				{
					array_push($jid, $pack{'Jid'});
				}
			}
			else
			{
				if( !$this->isStorePacket( array('Data.DataID'=>$pack{'Data'}{'DataID'}, 'Data.DataType'=>$pack{'Data'}{'DataType'} ) ) )
				{
					array_push($dataid, $pack{'Data'}{'DataID'});
				}
			}
		}

		$commande = array();
		$this->reponse{'code'} = "200";
		$this->reponse{'body'}{'Jid'} = $jid;
		$this->reponse{'body'}{'Data.DataID'} = $dataid;
		$this->reponse{'info'} = 'Proposition processed';
	}
	else
	{
		$this->reponse{'code'} = "400";
		$this->reponse{'info'} = $_SERVER['REMOTE_ADDR']." not autorised to propose for ".$this->param{'From'};
	}
}
