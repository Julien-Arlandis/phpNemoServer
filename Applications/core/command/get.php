<?php

$projection = array('_id'=>0);
$count = false;
$listen = ($jntp->param{'listen'} && $jntp->param{'listen'} == 1 ) ? true : false;
$delay = 1;
$limit = 500;
$count_packet = 0;

if($jntp->param{'limit'} && is_numeric($jntp->param{'limit'}) )
{
	$limit = ($jntp->param{'limit'} > $limit) ? $limit : $jntp->param{'limit'};
}

if(is_int($jntp->param{'maxDataLength'}) && ($jntp->param{'maxDataLength'} > 27 || $jntp->param{'maxDataLength'} == 0) )
{
	$jntp->maxDataLength = $jntp->param{'maxDataLength'};
}

if($jntp->param{'group'})
{
	foreach($jntp->param{'group'} as $field)
	{
		if($field == 'count')
		{
			$count = true;
		}
	}
}
elseif($jntp->param{'select'})
{
	foreach($jntp->param{'select'} as $field)
	{
		$projection['ID'] = 1;
		$projection['Jid'] = 1;
		
		/* 
		Syntaxe pour extraire un tableau :
		Data.References:3,5 => renvoie les cellules 3, 4 et 5 => $slice: [ 3, 5 ]
		Data.References:2,N => renvoie les cellules 2 à N
		Data.References:2,N-5 => renvoie les cellules de 2 à N-5
		Data.References:N-2,N => renvoie les cellules de N-2 à N => $slice:-2
		*/
		
		$inds = explode(":", $field);
		$inds = (isset($inds[1])) ? $inds[1] : 0;
		if($inds)
		{
			$inds = explode(",", $inds);
			if ( is_numeric($inds[0]) && is_numeric($inds[1]) ) // cas i,j
			{
				$projection[$field] = array('$slice'=> array($inds[0], $inds[1]) );
			}
			else // cas N-i,N
			{
				$ind0 = explode("-", $ind0);
				$projection[$field] = array('$slice'=> -$ind0 );
			}
		}
		else
		{
			$projection[$field] = 1;
		}
	}
}

if( $jntp->param{'filter'})
{
	$application = $jntp->datatypeByApplication[ $jntp->param{'filter'}{'Data.DataType'} ];
	if( !$application )
	{
		$jntp->reponse{'code'} = "500";
		$jntp->reponse{'info'} = "DataType not found";
		$jntp->send();
	}

	$query = array();

	foreach($jntp->param{'filter'} as $key => $value)
	{
		$key = explode(":", $key);
		$ind = (isset($key[1]) && is_numeric($key[1])) ? $key[1] : 0;
		$key = $key[0];
		if( !in_array($key, $jntp->config['Applications']['core']['DataType']['ProtoData']['filter'] ) && !in_array($key, $jntp->config['Applications'][$application]['DataType'][$jntp->param{'filter'}{'Data.DataType'}]['filter'] ) )
		{
			$jntp->reponse{'code'} = "400";
			$jntp->reponse{'info'} = "Filter [".$key."] not allowed";
			$jntp->send();
		}

		if($ind)
		{
			$key = $key.".".($ind-1);
		}

		if( is_string($value) || is_numeric($value) )
		{
			if($value != "" && $value != "*")
			{
				if($key === "Data.Newsgroups" && substr($value, -1) == "*")  // Spécifique à Article
				{
					array_push($query, array("Meta.Hierarchy" => $value));
				}
				else
				{
					array_push($query, array($key => $value));
				}
			}
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
		if(!$count)
		{
			$cursor = $jntp->mongo->packet->find( array('$and'=>$query), $projection )->limit($limit)->sort(array('ID' => -1));
		}
		else
		{
			$cursor = $jntp->mongo->packet->count(array('$and'=>$query));
		}
		if(!$firstQuery)
		{
			sleep($delay);
		}
		$firstQuery = false;
		$time_execution += $delay;
		if($time_execution >= $time_execution_max)
		{
			$jntp->reponse{'code'} = "200";
			$jntp->reponse{'body'} = array();
			$jntp->send();
		}
	} while($listen && $cursor->count()==0);
}

if(!$count)
{
	$jntp->reponse{'code'} = "200";
	$jntp->reponse{'body'} = array();
	foreach($cursor as $packet)
	{
		$count_packet++;
		if( $jntp->privilege != 'admin' && $jntp->privilege != 'moderator')
		{
			unset( $packet{'Meta'}{'ForAdmin'} );
		}
		array_push($jntp->reponse{'body'}, $jntp->replaceHash( $packet ) );
	}
	$jntp->reponse{'info'} = "Get ".$count_packet." packet(s)";
	if ($jntp->param{'select'}) $jntp->reponse{'info'} .= " with projection";
}
else
{
	$jntp->reponse{'code'} = "200";
	$jntp->reponse{'body'} = array("count"=>$cursor);
	$jntp->reponse{'info'} = "Count ".$cursor->count()." packet(s)";
}

