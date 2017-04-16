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
    $res = array();

    if( count($key) != 2) {
        return 1;
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
                if( is_numeric($res[0]) ) { return(array('$slice'=>array( $res[0]-1, 1) )); }
                elseif($res[0] == 'end') { return(array('$slice'=>-1)); }
                else { return(array('$slice'=>array($res[0], 1))); }
            }else{
                if( is_numeric($res[0]) ) {
                        if( is_numeric($res[1]) && $res[1] > 0) { return(array('$slice'=>array( $res[0]-1, $res[1]-1) )); }
                        elseif($res[1] == 'end') { return(array('$slice'=>array( $res[0]-1, 999999) )); }
                        else { return(array('$slice'=>array( $res[0]-1, 999999) )); }
                }else{
                    if( $res[0] == 'end' ) {
                        return 1;
                    }else{
                        if( is_numeric($res[1]) && $res[1] > 0 ) { return(array('$slice'=>$res[0] )); }
                        elseif($res[1] == 'end') { return(array('$slice'=>$res[0])); }
                        else { return(array('$slice'=>array($res[0], $res[1]-$res[0]) )); }
                    }
                }
            }
        }else{
            return 1;
        }
    }
}

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
		$projection[$item[0]] = setProjection($field);
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