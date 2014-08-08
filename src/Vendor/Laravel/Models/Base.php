<?php

/**
 * Part of the SDK package.
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the 3-clause BSD License.
 *
 * This source file is subject to the 3-clause BSD License that is
 * bundled with this package in the LICENSE file.  It is also available at
 * the following URL: http://www.opensource.org/licenses/BSD-3-Clause
 *
 * @package    SDK
 * @author     Antonio Carlos Ribeiro @ PragmaRX
 * @license    BSD License (3-clause)
 * @copyright  (c) 2013, PragmaRX
 * @link       http://pragmarx.com
 */

namespace PragmaRX\SDK\Vendor\Laravel\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use PragmaRX\SDK\Support\Config;

class Base extends Eloquent {

	public function __construct(array $attributes = array())
	{
		parent::__construct($attributes);

		$this->setConnection($this->getConfig()->get('connection'));
	}

	public function getConfig()
	{
		return $GLOBALS["app"]["sdk.config"];
	}

	public function scopePeriod($query, $minutes, $alias = '')
	{
		$alias = $alias ? "$alias." : '';

		return $query
				->where($alias.'updated_at', '>=', $minutes->getStart())
				->where($alias.'updated_at', '<=', $minutes->getEnd());
	}

}
