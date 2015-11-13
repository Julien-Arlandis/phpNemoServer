<?php

$this->setSession();

if($this->privilege == 'admin')
{
	$this->createIndex();
	$this->reponse{'code'} = "200";
	$this->reponse{'info'} = "Reload index, done";
}
else
{
	$this->reponse{'code'} = "400";
	$this->reponse{'info'} = "Not autorised to reload index";
}
