<?php

$this->setSession();

if($this->privilege == 'admin')
{
	$this->createIndex();
	$this->reponse{'code'} = "200";
	$this->reponse{'body'} = array("reload index, done");
}
else
{
	$this->reponse{'code'} = "500";
	$this->reponse{'body'} = array("Not autorised to reload index");
}
