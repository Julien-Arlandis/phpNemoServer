<?php

$query = array();
$sort = array('name' => 1);

if(isset(JNTP::$param{'name'}))
{
	if(!preg_match('/^#?[a-zA-Z0-9*.-]+$/', JNTP::$param{'name'}))
	{
		JNTP::$reponse{'code'} = "400";
		JNTP::$reponse{'info'} = "char not allowed";
		JNTP::send();
	}
	else
	{
		$search = str_replace("*","[a-zA-Z0-9*.-]*",JNTP::$param{'name'});
		$regexObj = new MongoRegex("/^".$search."/i");
		array_push($query, array('name' => $regexObj));
	}
}

if(isset(JNTP::$param{'level'}))
{
	$level = substr_count(JNTP::$param{'name'}, '.') + JNTP::$param{'level'};
	array_push($query, array('level' => $level));
}

if(isset(JNTP::$param{'type'}))
{
	array_push($query, array('type' => JNTP::$param{'type'}));
}

if(isset(JNTP::$param{'category'}))
{
	array_push($query, array('category' => array('$exists' => true) ));
	$sort = array('sort' => 1, 'name' => 1);
}

if(isset(JNTP::$param{'total'}) && is_numeric(JNTP::$param{'total'}) )
{
	$cursor = JNTP::$mongo->newsgroup->find( array('$and' => $query) )->sort($sort)->limit(JNTP::$param{'total'});
}else{
	$cursor = JNTP::$mongo->newsgroup->find( array('$and' => $query) )->sort($sort);
}

JNTP::$reponse{'code'} = "200";
JNTP::$reponse{'body'} = array();

foreach ($cursor as $obj)
{
	$data = null;
	$data{'name'} = $obj['name'];
	if($obj['description'])
	{
		$data{'description'} = $obj['description'];
	}
	if($obj['category'])
	{
		$data{'category'} = $obj['category'];
	}
	$data{'rules'}{'w'} = $obj['rules']['w'];
	if($obj['rules']['m']) $data{'rules'}{'m'} = $obj['rules']['m'];
	if(!JNTP::$userid) $data{'rules'}{'w'} = 0;
	if(!JNTP::$userid && $obj['rulesIfNotConnected']) $data{'rules'}{'w'} = $obj['rulesIfNotConnected']['w'];
	if($obj['rules']['m'] == '1') $data{'rules'}{'w'} = '0';
	array_push(JNTP::$reponse{'body'}, $data);
}
JNTP::$reponse{'info'} = "Get ".count(JNTP::$reponse{'body'})." Newsgroup(s)";
