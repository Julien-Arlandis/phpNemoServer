<?php
$this->reponse{'code'} = "200";

switch($this->param)
{
	case 'get' : $this->reponse{'info'} = <<<EOF
["get",{"Jid":[],"ID":[],"select":[],filter:[]}]

Pour lire un article :
["get",{"Jid":["jid"]}]

Pour lire les entêtes "FromName, FromaMail, Subject, Date, Newsgroups" de mid1 et mid2:
["get",{"Jid":["jid1","jid2"],"select":["FromName", "FromMail", "Subject", "Date", "Newsgroups"]}]

Pour lire les articles à partir de leur id:
["get",{"ID":["id1","id2"]}]

Pour lire le sujet des 10 derniers articles de la hiérarchie fr.sci.* :
["get",{"filter":[["Newsgroups","fr.sci.*"],["total","10"]], "select":["Subject"]}]
EOF;
	break;

	case 'diffuse' : $this->reponse{'info'} = <<<EOF
Pour envoyer un article sur fr.test
{"diffuse":{"Data":{"FromName":"Julien","FromMail":"julien@test","Subject":"test","Newsgroups":["fr.test"],"Body":"Message de test"}}}

Pour transférer un article entre deux serveurs
{"diffuse":{"Packet":{"Jid":"X","Route":[X],"Data":{X}}}
EOF;
	break;

	case 'auth' : $this->reponse{'info'} = <<<EOF
Permet de s'authentifier sur le serveur de newsgroup
["auth",{"email":"un_email_valide","password":"un_password_valide"}]
EOF;
	break;

	case 'whoami' :	$this->reponse{'info'} = <<<EOF
Informe le client de son identité et de ses droits sur le serveur, utile pour savoir si on est encore connecté
["whoami"]
EOF;
	break;

	case 'quit' : $this->reponse{'info'} = <<<EOF
Ferme la connexion avec le serveur
["quit"]
EOF;
	break;

	default : $this->reponse{'info'} = <<<EOF
Pour obtenir de l'aide sur la commande get, taper ["help","get"]
   get            : Récupère un ou plusieurs articles
   diffuse        : Diffuse un article sur le réseau
   auth           : Permet de s'authentifier sur le serveur de newsgroup
   whoami         : Informe le client de son identité, utilie pour savoir si on est encore connecté
   quit           : Ferme la connexion avec le serveur
   help           : Fournit de l'aide sur la liste des instructions disponibles
EOF;
	break;
}
