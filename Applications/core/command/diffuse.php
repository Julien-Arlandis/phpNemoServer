<?php

// Query from Client
if(JNTP::$param{'Data'})
{
	JNTP::$packet{'Data'} = JNTP::$param{'Data'};

	// Check if Data.DataType is valid
	if (JNTP::loadDataType())
	{
		// Check if Data is conform to DataType declaration
		if( JNTP::$datatype->isValidData() )
		{
			// Traitment before insertion
			if ( JNTP::$datatype->beforeInsertion() )
			{
				// Complete Data
				JNTP::$datatype->forgeData();
				// Forge packet
				JNTP::forgePacket();
				// Insert packet in database
				JNTP::insertPacket();
				JNTP::$datatype->afterInsertion(JNTP::$packet{'ID'});

				JNTP::$reponse{'code'} = "200";
				JNTP::$reponse{'body'}{'Data'}{'DataID'} = JNTP::$packet{'Data'}{'DataID'};
				JNTP::$reponse{'body'}{'Data'}{'DataType'} = JNTP::$packet{'Data'}{'DataType'};
				JNTP::$reponse{'body'}{'Jid'} = JNTP::$packet{'Jid'};
				JNTP::$reponse{'body'}{'ID'} = JNTP::$packet{'ID'};
				JNTP::$reponse{'info'} = 'Diffuse '.JNTP::$packet{'Data'}{'DataID'}.' OK';
			}
		}
		else
		{
			JNTP::$reponse{'code'} = "400";
		}
	}
	else
	{
		JNTP::$reponse{'code'} = "400";
		JNTP::$reponse{'info'} = "DataType ".JNTP::$packet{'Data'}{'DataType'}." unsupported";
	}
}

// Query from Server
elseif(JNTP::$param{'Packet'})
{
	JNTP::$packet = JNTP::$param{'Packet'};
	JNTP::loadDataType();

	$want = false;
	// Check if packet exists
	if( !isset(JNTP::$packet{'Data'}{'DataID'}) || JNTP::$packet{'Jid'} == substr(JNTP::$packet{'Data'}{'DataID'},0,27) )
	{
		if( !JNTP::isStorePacket( array('Jid' => JNTP::$packet{'Jid'}) ) )
		{
			$want = true;
		}
		else
		{
			JNTP::$reponse{'code'} = "400";
			JNTP::$reponse{'info'} = 'Jid ' . JNTP::$packet{'Jid'} . " already inserted";
		}
	}
	else
	{
		if( !JNTP::isStorePacket( array('Data.DataID'=>JNTP::$packet{'Data'}{'DataID'}, 'Data.DataType'=>JNTP::$packet{'Data'}{'DataType'} ) ) )
		{
			$want = true;
		}
		else
		{
			JNTP::$reponse{'code'} = "400";
			JNTP::$reponse{'info'} = JNTP::$packet{'Data'}{'DataType'} .':'. JNTP::$packet{'Data'}{'DataID'} . " already inserted";
		}
	}

	if( $want )
	{
		if( JNTP::isValidPacket() )
		{
			// Traitment before insertion
			if ( JNTP::$datatype->beforeInsertion() )
			{
				// Insert packet in database
				if(JNTP::insertPacket())
				{
					JNTP::$datatype->afterInsertion(JNTP::$packet{'ID'});
					JNTP::$reponse{'code'} = "200";
					JNTP::$reponse{'info'} = JNTP::$packet{'Jid'} . " : inserted";
				}
				else
				{
					JNTP::$reponse{'code'} = "400";
					JNTP::$reponse{'info'} = JNTP::$packet{'Jid'} . " : not inserted";
				}
			}
		}
		else
		{
			JNTP::$reponse{'code'} = "400";
			JNTP::$reponse{'info'} = "invalid packet";
		}
	}
	else
	{
		JNTP::$reponse{'code'} = "400";
		JNTP::$reponse{'info'} = JNTP::$packet{'Jid'} . " already inserted";
	}
}
elseif(JNTP::$param{'Propose'})
{
	for($i=0; $i<count(JNTP::$param{'Propose'}); $i++)
	{
		$pack = JNTP::$param{'Propose'}[$i];
		$jid = array();
		$dataid = array();
		if( !isset($pack{'Data'}{'DataID'}) || $pack{'Jid'} == substr($pack{'Data'}{'DataID'},0,27) )
		{
			if( !JNTP::isStorePacket( array('Jid' => $pack{'Jid'}) ) )
			{
				array_push($jid, $pack{'Jid'});
			}
		}
		else
		{
			if( !JNTP::isStorePacket( array('Data.DataID'=>$pack{'Data'}{'DataID'}, 'Data.DataType'=>$pack{'Data'}{'DataType'} ) ) )
			{
				array_push($dataid, $pack{'Data'}{'DataID'});
			}
		}
	}

	$commande = array();
	JNTP::$reponse{'code'} = "200";
	JNTP::$reponse{'body'}{'Jid'} = $jid;
	JNTP::$reponse{'body'}{'Data.DataID'} = $dataid;
	JNTP::$reponse{'info'} = 'Proposition processed';
}
