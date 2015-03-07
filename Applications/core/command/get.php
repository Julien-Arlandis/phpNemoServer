<?php
$projection = array('_id'=>0);
$count = false;
$listen = false;
$delay = 1;
$limit = 500;

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
		if($field == '@2References') // Spécifique à Article
		{
			$projection['ID'] = 1;
			$projection['Jid'] = 1;
			$projection['Data.References'] = array('$slice'=>-2);
		}
		else
		{
			$projection['ID'] = 1;
			$projection['Jid'] = 1;
			$projection[$field] = 1;
		}
	}
}

if( $this->param{'filter'})
{
	if( !$this->param{'filter'}{'Data.DataType'} || !$this->config['DataType'][$this->param{'filter'}{'Data.DataType'}])
	{
		$this->reponse{'body'} = "DataType not found";
		$this->reponse{'code'} = "500";
		$this->send();
	}

	$query = array();
	$order = -1;

	if($this->param{'listen'} && $this->param{'listen'} == 1 )
	{
		$listen = true;
	}else{
		$listen = false;
	}

	foreach($this->param{'filter'} as $key => $value)
	{
		if( !in_array($key, $this->config['DataType']['ProtoData']['filter']) && !in_array($key, $this->config['DataType'][$this->param{'filter'}{'Data.DataType'}]['filter'] ) )
		{
			$this->reponse{'body'} = "filtre [".$key."] non autorisé";
			$this->reponse{'code'} = "500";
			$this->send();
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
				$value = new MongoRegex("/^".preg_quote(str_replace(".","\.", $value[0]))."/");
				array_push($query, array($key => $value));
			}
			elseif( $value[1] == 'min' )
			{
				$order = 1;
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
			$cursor = $this->mongo->packet->find( array('$and'=>$query), $projection )->limit($limit)->sort(array('ID' => $order));
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
			$this->reponse{'body'} = array();
			$this->reponse{'code'} = "200";
			$this->send();
		}
	} while($listen && $cursor->count()==0);
}

if(!$count)
{
	$this->reponse{'body'} = array();
	$this->reponse{'code'} = "200";
	foreach($cursor as $packet)
	{
		if($order == 1)
		{
			array_push($this->reponse{'body'}, $this->replaceHash( $packet ) );
		}
		else
		{
			array_unshift($this->reponse{'body'}, $this->replaceHash( $packet ) );
		}
	}
}
else
{
	$this->reponse{'body'} = array("count"=>$cursor);
	$this->reponse{'code'} = "200";
}

