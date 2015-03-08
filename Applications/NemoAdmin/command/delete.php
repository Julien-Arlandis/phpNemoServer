<?php
$this->setSession();

if($this->privilege == 'admin')
{
	$this->reponse{'code'} = "200";
	$this->deletePacket($this->param{'ID'});
	$this->reponse{'body'} = array("Article ".$this->param{'ID'}." deleted");
}
else
{
	$this->reponse{'code'} = "500";
	$this->reponse{'body'} = array("Not autorised to delete");
}
