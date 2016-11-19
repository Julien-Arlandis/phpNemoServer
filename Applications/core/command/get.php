<?php

$projection = array('_id'=>0);
$count = false;
$listen = ($this->param{'listen'} && $this->param{'listen'} == 1 ) ? true : false;
$delay = 1;
$limit = 500;
$count_packet = 0;

if($this->param{'limit'} && is_numeric($this->param{'limit'}) )
{
	$limit = ($this->param{'limit'} > $limit) ? $limit : $this->param{'limit'};
}

if(is_int($this->param{'maxDataLength'}) && ($this->param{'maxDataLength'} > 27 || $this->param{'maxDataLength'} == 0) )
{
	$this->maxDataLength = $this->param{'maxDataLength'};
}

if($this->param{'group'})
{
	foreach($this->param{'group'} as $field)
	{
		if($field == 'count') 
		{
			$count = true;
		}
	}
}
elseif($this->param{'select'}) 
{
	foreach($this->param{'select'} as $field)
	{
		$projection['ID'] = 1;
		$projection['Jid'] = 1;

		if($field == '@2References') // Spécifique à Article
		{
			$projection['Data.References'] = array('$slice'=>-2);
		}
		else
		{
			$projection[$field] = 1;
		}
	}
}

if( $this->param{'filter'})
{
	if( !$this->param{'filter'}{'Data.DataType'} || !$this->config['DataType'][$this->param{'filter'}{'Data.DataType'}])
	{
		$this->reponse{'code'} = "500";
		$this->reponse{'info'} = "DataType not found";
		$this->send();
	}

	$query = array();

	foreach($this->param{'filter'} as $key => $value)
	{
		$key = explode(":", $key);
		$ind = (isset($key[1]) && is_numeric($key[1])) ? $key[1] : 0;
		$key = $key[0];
		if( !in_array($key, $this->config['DataType']['ProtoData']['filter']) && !in_array($key, $this->config['DataType'][$this->param{'filter'}{'Data.DataType'}]['filter'] ) )
		{
			$this->reponse{'code'} = "400";
			$this->reponse{'info'} = "Filter [".$key."] not allowed";
			$this->send();
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
			$cursor = $this->mongo->packet->find( array('$and'=>$query), $projection )->limit($limit)->sort(array('ID' => -1));
		}
		else
		{
			$cursor = $this->mongo->packet->count(array('$and'=>$query));
		}
		if(!$firstQuery) 
		{
			sleep($delay);
		}
		$firstQuery = false;
		$time_execution += $delay;
		if($time_execution >= $time_execution_max) 
		{
			$this->reponse{'code'} = "200";
			$this->reponse{'body'} = array();
			$this->send();
		}
	} while($listen && $cursor->count()==0);
}

if(!$count)
{
	$this->reponse{'code'} = "200";
	$this->reponse{'body'} = array();
	foreach($cursor as $packet)
	{
		$count_packet++;
		if( $this->privilege != 'admin' && $this->privilege != 'moderator')
		{
			unset( $packet{'Meta'}{'ForAdmin'} );
		}
		array_push($this->reponse{'body'}, $this->replaceHash( $packet ) );
	}
	$this->reponse{'info'} = "Get ".$count_packet." packet(s)";
	if ($this->param{'select'}) $this->reponse{'info'} .= " with projection";
}
else
{
	$this->reponse{'code'} = "200";
	$this->reponse{'body'} = array("count"=>$cursor);
	$this->reponse{'info'} = "Count ".$cursor->count()." packet(s)";
}

