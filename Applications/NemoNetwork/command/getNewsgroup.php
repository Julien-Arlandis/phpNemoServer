<?php
$this->setSession();

$query = array();
$tri = array('name' => 1);

if($this->param{'name'})
{
	if(!preg_match('/^#?[a-zA-Z0-9*.-]+$/', $this->param{'name'}))
	{
		$this->reponse{'body'} = "char not allowed";
		$this->reponse{'code'} = "500";
		$this->send();
	}
	else
	{
		$search = str_replace("*","[a-zA-Z0-9*.-]*",$this->param{'name'});
		$regexObj = new MongoRegex("/^".$search."/i");
		array_push($query, array('name' => $regexObj));
	}
}

if($this->param{'level'})
{
	$level = substr_count($this->param{'name'}, '.') + $this->param{'level'};
	array_push($query, array('level' => $level));
}

if($this->param{'type'})
{
	array_push($query, array('type' => $this->param{'type'}));
}

if($this->param{'category'})
{
	array_push($query, array('category' => array('$exists' => true) ));
	$tri = array('tri' => 1, 'name' => 1);
}

if($this->param{'total'} && is_numeric($this->param{'total'}) )
{
	$cursor = $this->mongo->newsgroup->find( array('$and' => $query) )->sort($tri)->limit($this->param{'total'});
}else{
	$cursor = $this->mongo->newsgroup->find( array('$and' => $query) )->sort($tri);
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
	if(!$this->userid) $data{'rules'}{'w'} = 0;
	if(!$this->userid && $obj['rulesIfNotConnected']) $data{'rules'}{'w'} = $obj['rulesIfNotConnected']['w'];
	if($obj['rules']['m'] == '1') $data{'rules'}{'w'} = '0';
	array_push($this->reponse{'body'}, $data);
}
