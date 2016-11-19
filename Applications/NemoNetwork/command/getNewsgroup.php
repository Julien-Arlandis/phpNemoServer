<?php

$query = array();
$sort = array('name' => 1);

if(isset($this->param{'name'}))
{
	if(!preg_match('/^#?[a-zA-Z0-9*.-]+$/', $this->param{'name'}))
	{
		$this->reponse{'code'} = "400";
		$this->reponse{'info'} = "char not allowed";
		$this->send();
	}
	else
	{
		$search = str_replace("*","[a-zA-Z0-9*.-]*",$this->param{'name'});
		$regexObj = new MongoRegex("/^".$search."/i");
		array_push($query, array('name' => $regexObj));
	}
}

if(isset($this->param{'level'}))
{
	$level = substr_count($this->param{'name'}, '.') + $this->param{'level'};
	array_push($query, array('level' => $level));
}

if(isset($this->param{'type'}))
{
	array_push($query, array('type' => $this->param{'type'}));
}

if(isset($this->param{'category'}))
{
	array_push($query, array('category' => array('$exists' => true) ));
	$sort = array('sort' => 1, 'name' => 1);
}

if(isset($this->param{'total'}) && is_numeric($this->param{'total'}) )
{
	$cursor = $this->mongo->newsgroup->find( array('$and' => $query) )->sort($sort)->limit($this->param{'total'});
}else{
	$cursor = $this->mongo->newsgroup->find( array('$and' => $query) )->sort($sort);
}

$this->reponse{'code'} = "200";
$this->reponse{'body'} = array();

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
	if(!$this->userid) $data{'rules'}{'w'} = 0;
	if(!$this->userid && $obj['rulesIfNotConnected']) $data{'rules'}{'w'} = $obj['rulesIfNotConnected']['w'];
	if($obj['rules']['m'] == '1') $data{'rules'}{'w'} = '0';
	array_push($this->reponse{'body'}, $data);
}
$this->reponse{'info'} = "Get ".count($this->reponse{'body'})." Newsgroup(s)";
