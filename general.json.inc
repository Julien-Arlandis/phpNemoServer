{
	"domain": "news.exemple.net",
	"organization": "Nemoweb",
	"publicServer": ["news2.nemoweb.net"],
	"serverVersion": "0.90a",
	"protocolVersion": "0.19.2",
	"administrator": "newsmaster@exemple.net",
	"maxDataLength": 1000,
	"crossDomainAccept": true,
	"activeInscription": true,
	"application": {
		"core": {
			"description": "JNTP Server core",
			"commands": ["diffuse","get","auth","whoami","quit"]
		},
		"NemoNetwork": {
			"description": "Manage a server of newsgroups for Nemo network",
			"commands": ["getNewsgroup","set","inscription","help","changePassword"],
			"maxCrosspost": 5,
			"maxFU2": 5,
			"maxCrosspostWithoutFU2": 3
		},
		"NemoAdmin": {
			"description": "Administration of Nemo network",
			"commands": ["delete","ban","synchronizeNewsgroup"]
		}
	},
	"DataType": {
		"ProtoData": {
			"filter": ["Jid", "ID", "Data.DataType", "Data.DataID", "Data.InjectionDate", "Data.Protocol"]
		},
		"Article": {
			"filter": ["Jid", "ID", "Data.Newsgroups", "Data.UserAgent", "Data.FromName", "Data.FromMail", "Data.Subject", "Data.ThreadID", "Data.UserID", "Data.ReferenceUserID"]
		},
		"ListGroup": {
			"filter": ["Jid", "ID", "Data.Newsgroups", "Data.Hierarchy"]
		},
		"Like": {
			"filter": ["Jid", "ID", "Data.Newsgroups", "Data.JidLike"]
		}
	},
	"feed": {
		"news.exemple2.net": {
			"actif": 1,
			"type": ["JNTP","J2J.php"],
			"match": [
				{
					"Data.DataType": ["Article"],
					"Data.Newsgroups": ["*"]
				},
				{
					"Data.DataType": ["*"]
				}
			],
			"mail": "mail":"newsmaster@exemple2.net"
		},
		"nntpserver.net": {
			"actif":0,
			"type": ["NNTP", "J2N.php"],
			"match": [
					{
						"Data.DataType": ["Article"],
						"Data.Newsgroups": ["*"]
					},
					{
						"Data.DataType": ["*"]
					}
			],
			"mail":"newsmaster@nntpserver.net"
		}
	}
}
