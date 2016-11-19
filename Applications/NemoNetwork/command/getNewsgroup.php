<?php

$query = array();
$sort = array('name' => 1);

if(isset($jntp->param{'name'}))
{
	if(!preg_match('/^#?[a-zA-Z0-9*.-]+$/', $jntp->param{'name'}))
	{
		$jntp->reponse{'code'} = "400";
		$jntp->reponse{'info'} = "char not allowed";
		$jntp->send();
	}
	else
	{
		$search = str_replace("*","[a-zA-Z0-9*.-]*",$jntp->param{'name'});
		$regexObj = new MongoRegex("/^".$search."/i");
		array_push($query, array('name' => $regexObj));
	}
}

if(isset($jntp->param{'level'}))
{
	$level = substr_count($jntp->param{'name'}, '.') + $jntp->param{'level'};
	array_push($query, array('level' => $level));
}

if(isset($jntp->param{'type'}))
{
	array_push($query, array('type' => $jntp->param{'type'}));
}

if(isset($jntp->param{'category'}))
{
	array_push($query, array('category' => array('$exists' => true) ));
	$sort = array('sort' => 1, 'name' => 1);
}

if(isset($jntp->param{'total'}) && is_numeric($jntp->param{'total'}) )
{
	$cursor = $jntp->mongo->newsgroup->find( array('$and' => $query) )->sort($sort)->limit($jntp->param{'total'});
}else{
	$cursor = $jntp->mongo->newsgroup->find( array('$and' => $query) )->sort($sort);
}

$jntp->reponse{'code'} = "200";
$jntp->reponse{'body'} = array();

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
	if(!$jntp->userid) $data{'rules'}{'w'} = 0;
	if(!$jntp->userid && $obj['rulesIfNotConnected']) $data{'rules'}{'w'} = $obj['rulesIfNotConnected']['w'];
	if($obj['rules']['m'] == '1') $data{'rules'}{'w'} = '0';
	array_push($jntp->reponse{'body'}, $data);
}
$jntp->reponse{'info'} = "Get ".count($jntp->reponse{'body'})." Newsgroup(s)";
