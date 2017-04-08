{
	"description": "Manage a server of newsgroups for Nemo network",
	"commands": [
		"getNewsgroup",
		"set",
		"inscription",
		"help",
		"changePassword"
	],
	"maxCrosspost": 5,
	"maxFU2": 5,
	"maxCrosspostWithoutFU2": 3,
	"outFeeds": {
		"news2.nemoweb.net": {
			"actif": 1,
			"type": [
				"JNTP",
				"J2J.php"
			],
			"match": [{
				"Data.DataType": [
					"Article"
				],
				"Data.Newsgroups": [
					"*"
				]
			}, {
				"Data.DataType": [
					"*"
				]
			}],
			"mail": "newsmaster@exemple2.net"
		},
		"nntpserver.net": {
			"actif": 0,
			"type": [
				"NNTP",
				"J2N.php"
			],
			"match": [{
				"Data.DataType": [
					"Article"
				],
				"Data.Newsgroups": [
					"*"
				]
			}, {
				"Data.DataType": [
					"*"
				]
			}],
			"mail": "newsmaster@nntpserver.net"
		}
	}
}
