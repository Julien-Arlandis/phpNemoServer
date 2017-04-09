<?php

function setProjection($key)
{
	/* 
	Syntaxe pour extraire un tableau :
	Data.References:3,5 => renvoie les cellules 3, 4 et 5 => $slice: [ 3, 5 ]
	Data.References:2,N => renvoie les cellules 2 à N
	Data.References:2,N-5 => renvoie les cellules de 2 à N-5
	Data.References:N-2,N => renvoie les cellules de N-2 à N => $slice:-2
	*/
    $key = explode(':', $key);
    $item = $key[0];
    $res = array();
    
    if( count($key) != 2) {
        return array($item => 1);
    }else{
        $key = explode(',', $key[1]);
        if( count($key) <= 2) {
            foreach($key as $ind => $value) {
                if(is_numeric($value)) {
                    $key[$ind] = $value;
                }else{
                    if($key[$ind] == 'N') {
                        $key[$ind] = 'end';
                    } else {
                        $attr = explode('N-', $key[$ind]);
                        if( count($attr) == 2 ) {
                            $key[$ind] = -$attr[1];
                        }else{
                            $key[$ind] = 0;
                        }
                    }
                }
                array_push($res, $key[$ind]);
            }
            if(count($res) == 1) {
                if( is_numeric($res[0]) ) { return(array($item=>array('$slice'=>array( $res[0]-1, 1) ))); }
                elseif($res[0] == 'end') { return(array($item=>array('$slice'=>-1))); }
                else { return(array($item=>array('$slice'=>array($res[0], 1)))); }
            }else{
                if( is_numeric($res[0]) ) {
                        if( is_numeric($res[1]) && $res[1] > 0) { return( array($item=>array('$slice'=>array( $res[0]-1, $res[1]-1) ))); }
                        elseif($res[1] == 'end') { return(array($item=>array('$slice'=>array( $res[0]-1, 999999) ))); }
                        else { return(array($item=>array('$slice'=>array( $res[0]-1, 999999) ))); }
                }else{
                    if( $res[0] == 'end' ) {
                        return array($item => 1);
                    }else{
                        if( is_numeric($res[1]) && $res[1] > 0 ) { return(array($item=>array('$slice'=>$res[0] ))); }
                        elseif($res[1] == 'end') { return( array($item=> array('$slice'=>$res[0])) ); }
                        else { return( array($item=>array('$slice'=>array($res[0], $res[1]-$res[0])) )); }
                    }
                }
            }
        }else{
            return array($item => 1);
        }
    }
}

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

// 
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
	$projection['ID'] = 1;
	$projection['Jid'] = 1;
	
	foreach($jntp->param{'select'} as $field)
	{		
		if($field == '@2References') // Spécifique à Article
		{
			$projection['Data.References'] = array('$slice'=>-2);
		}
		else
		{
			$projection[$field] = setProjection($field);
			//$projection[$field] = 1;
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
		if( !in_array($key, $jntp->config['Applications']['core']['DataType']['ProtoData']['filter'] ) && !in_array($key, $jntp->config['Applications'][$application]['DataType'][$jntp->param{'filter'}{'Data.DataType'}]['filter'] ) )
		{
			$jntp->reponse{'code'} = "400";
			$jntp->reponse{'info'} = "Filter [".$key."] not allowed";
			$jntp->send();
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

