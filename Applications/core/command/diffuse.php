<?php

// Query from Client
if($jntp->param{'Data'})
{
	$jntp->packet{'Data'} = $jntp->param{'Data'};

	// Check if Data.DataType is valid
	if ($jntp->loadDataType())
	{
		// Check if Data is conform to DataType declaration
		if( $jntp->datatype->isValidData() )
		{
			// Traitment before insertion
			if ( $jntp->datatype->beforeInsertion() )
			{
				// Complete Data
				$jntp->datatype->forgeData();
				// Forge packet
				$jntp->forgePacket();
				// Insert packet in database
				$jntp->insertPacket();
				$jntp->datatype->afterInsertion($jntp->packet{'ID'});

				$jntp->reponse{'code'} = "200";
				$jntp->reponse{'body'}{'Data'}{'DataID'} = $jntp->packet{'Data'}{'DataID'};
				$jntp->reponse{'body'}{'Data'}{'DataType'} = $jntp->packet{'Data'}{'DataType'};
				$jntp->reponse{'body'}{'Jid'} = $jntp->packet{'Jid'};
				$jntp->reponse{'body'}{'ID'} = $jntp->packet{'ID'};
				$jntp->reponse{'info'} = 'Diffuse '.$jntp->packet{'Data'}{'DataID'}.' OK';
			}
		}
		else
		{
			$jntp->reponse{'code'} = "400";
		}
	}
	else
	{
		$jntp->reponse{'code'} = "400";
		$jntp->reponse{'info'} = "DataType ".$jntp->packet{'Data'}{'DataType'}." unsupported";
	}
}

// Query from Server
elseif($jntp->param{'Packet'})
{
	$jntp->packet = $jntp->param{'Packet'};
	$jntp->loadDataType();

	$want = false;
	// Check if packet exists
	if( !isset($jntp->packet{'Data'}{'DataID'}) || $jntp->packet{'Jid'} == substr($jntp->packet{'Data'}{'DataID'},0,27) )
	{
		if( !$jntp->isStorePacket( array('Jid' => $jntp->packet{'Jid'}) ) )
		{
			$want = true;
		}
		else
		{
			$jntp->reponse{'code'} = "400";
			$jntp->reponse{'info'} = 'Jid ' . $jntp->packet{'Jid'} . " already inserted";
		}
	}
	else
	{
		if( !$jntp->isStorePacket( array('Data.DataID'=>$jntp->packet{'Data'}{'DataID'}, 'Data.DataType'=>$jntp->packet{'Data'}{'DataType'} ) ) )
		{
			$want = true;
		}
		else
		{
			$jntp->reponse{'code'} = "400";
			$jntp->reponse{'info'} = $jntp->packet{'Data'}{'DataType'} .':'. $jntp->packet{'Data'}{'DataID'} . " already inserted";
		}
	}

	if( $want )
	{
		if( $jntp->isValidPacket() )
		{
			// Traitment before insertion
			if ( $jntp->datatype->beforeInsertion() )
			{
				// Insert packet in database
				if($jntp->insertPacket())
				{
					$jntp->datatype->afterInsertion($jntp->packet{'ID'});
					$jntp->reponse{'code'} = "200";
					$jntp->reponse{'info'} = $jntp->packet{'Jid'} . " : inserted";
				}
				else
				{
					$jntp->reponse{'code'} = "400";
					$jntp->reponse{'info'} = $jntp->packet{'Jid'} . " : not inserted";
				}
			}
		}
		else
		{
			$jntp->reponse{'code'} = "400";
			$jntp->reponse{'info'} = "invalid packet";
		}
	}
	else
	{
		$jntp->reponse{'code'} = "400";
		$jntp->reponse{'info'} = $jntp->packet{'Jid'} . " already inserted";
	}
}
elseif($jntp->param{'Propose'})
{
	for($i=0; $i<count($jntp->param{'Propose'}); $i++)
	{
		$pack = $jntp->param{'Propose'}[$i];
		$jid = array();
		$dataid = array();
		if( !isset($pack{'Data'}{'DataID'}) || $pack{'Jid'} == substr($pack{'Data'}{'DataID'},0,27) )
		{
			if( !$jntp->isStorePacket( array('Jid' => $pack{'Jid'}) ) )
			{
				array_push($jid, $pack{'Jid'});
			}
		}
		else
		{
			if( !$jntp->isStorePacket( array('Data.DataID'=>$pack{'Data'}{'DataID'}, 'Data.DataType'=>$pack{'Data'}{'DataType'} ) ) )
			{
				array_push($dataid, $pack{'Data'}{'DataID'});
			}
		}
	}

	$commande = array();
	$jntp->reponse{'code'} = "200";
	$jntp->reponse{'body'}{'Jid'} = $jid;
	$jntp->reponse{'body'}{'Data.DataID'} = $dataid;
	$jntp->reponse{'info'} = 'Proposition processed';
}
