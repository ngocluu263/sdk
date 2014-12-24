<?php

namespace PragmaRX\Sdk\Core\Data;

use Config;

class Repository {

	public function getClassName($className)
	{
		$aliases = Config::get('pragmarx/sdk::aliases');

		if (isset($aliases[$className]))
		{
			return $aliases[$className];
		}

		return $className;
	}

}