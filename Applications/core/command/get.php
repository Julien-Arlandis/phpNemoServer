<?php

$projection = array('_id'=>0); // fixe le tableau des projections (propriété select)
$delay = 1; // délai entre deux push
$limit = 500; // nombre max de résultat
$listen = (JNTP::$param{'listen'} && JNTP::$param{'listen'} == 1 ) ? true : false; // active le push

// modifie le nombre maximal de résultat renvoyé par le get
if(JNTP::$param{'limit'} && is_numeric(JNTP::$param{'limit'}) )
{
	$limit = (JNTP::$param{'limit'} > $limit) ? $limit : JNTP::$param{'limit'};
}

// utile pour renvoyer dans ll paquet le contenu d'une pièce jointe contenue dans Media
if(is_int(JNTP::$param{'maxDataLength'}) && (JNTP::$param{'maxDataLength'} > 27 || JNTP::$param{'maxDataLength'} == 0) )
{
	JNTP::$config{'maxDataLength'} = JNTP::$param{'maxDataLength'};
}

// définit les projections
if(JNTP::$param{'select'})
{
	$projection['ID'] = 1;
	$projection['Jid'] = 1;
	foreach(JNTP::$param{'select'} as $field)
	{
		$item = explode(':',$field);
		$projection[$item[0]] = JNTP::$app->setProjection($field);
	}
}

if( JNTP::$param{'filter'})
{
	$application = JNTP::$datatypeByApplication[ JNTP::$param{'filter'}{'Data.DataType'} ];
	if( !$application )
	{
		JNTP::$reponse{'code'} = "500";
		JNTP::$reponse{'info'} = "DataType not found";
		JNTP::send();
	}

	$query = array();
	
	foreach(JNTP::$param{'filter'} as $key => $value)
	{
	    $arrKey = explode(':',$key);
	    $trueKey = $arrKey[0];
	    if(count($arrKey) > 1)
	    {
	        $index = intval($arrKey[1])-1;
	        $key = $trueKey.".".$index;
	    }
		if( !in_array($trueKey, JNTP::$config['Applications']['core']['DataType']['ProtoData']['filter'] ) && !in_array($trueKey, JNTP::$config['Applications'][$application]['DataType'][JNTP::$param{'filter'}{'Data.DataType'}]['filter'] ) )
		{
			JNTP::$reponse{'code'} = "400";
			JNTP::$reponse{'info'} = "Filter [".$trueKey."] not alloweddddd";
			JNTP::send();
		}
        
		if( is_string($value) || is_numeric($value) )
		{
			array_push($query, array($key => $value));
		}
		elseif( is_array($value) && count($value) == 2 )
		{
			if( $value[1] == 'contain' )
			{
				$value = new MongoRegex("/^".preg_quote($value[0])."/");
				array_push($query, array($key => $value));
			}
			elseif( $value[1] == 'min' )
			{
				array_push($query, array($key => array('$gt' => $value[0])));
			}
			elseif( $value[1] == 'max' )
			{
				array_push($query, array($key => array('$lt' => $value[0])));
			}
			elseif( $value[1] == 'not' )
			{
				array_push($query, array($key => array('$ne' => $value[0])));
			}
			elseif( $value[1] == 'equal' )
			{
				array_push($query, array($key => $value));
			}
		}
	}

	// Push if Listen
	$firstQuery = true;
	$time_execution = 0;
	$time_execution_max = 30;
	do {
		if(!JNTP::$param{'group'})
		{
			$cursor = JNTP::$mongo->packet->find( array('$and'=>$query), $projection )->limit($limit)->sort(array('ID' => -1));
		}
		else
		{
			$cursor = JNTP::$mongo->packet->count(array('$and'=>$query));
		}
		if(!$firstQuery) sleep($delay);
		$firstQuery = false;
		$time_execution += $delay;
		if($time_execution >= $time_execution_max)
		{
			JNTP::$reponse{'code'} = "200";
			JNTP::$reponse{'body'} = array();
			JNTP::send();
		}
	} while($listen && $cursor->count()==0);
}

// Renvoie les résultats
if(!JNTP::$param{'group'})
{
	JNTP::$reponse{'body'} = array();
	foreach($cursor as $packet)
	{
		if( JNTP::$privilege != 'admin' && JNTP::$privilege != 'moderator') unset( $packet{'Meta'}{'ForAdmin'} );
		array_push(JNTP::$reponse{'body'}, JNTP::replaceHash( $packet ) );
	}
	JNTP::$reponse{'code'} = "200";
	JNTP::$reponse{'info'} = "Get ".count($packet)." packet(s)";
	if (JNTP::$param{'select'}) JNTP::$reponse{'info'} .= " with projection";
}
else
{
	JNTP::$reponse{'code'} = "200";
	JNTP::$reponse{'body'} = array("count"=>$cursor);
	JNTP::$reponse{'info'} = "Count ".$cursor->count()." packet(s)";
}
