<?php
class DataType
{
	function __construct() 
	{
	}

	function isValidData()
	{
		global $jntp;
		return true;
	}

	function forgeData()
	{
		global $jntp;
		return true;
	}

	function beforeInsertion()
	{
		global $jntp;
		return true;
	}

	function afterInsertion()
	{
		global $jntp;
		return true;
	}
}
