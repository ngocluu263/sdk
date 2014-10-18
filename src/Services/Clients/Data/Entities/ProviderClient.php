<?php

namespace PragmaRX\Sdk\Services\Clients\Data\Entities;

use PragmaRX\Sdk\Core\Model;

class ProviderClient extends Model {

	protected $table = 'providers_clients';

	protected $fillable = [
		'provider_id',
		'client_id',
	];

	public function provider()
	{
		return $this
				->belongsTo('PragmaRX\Sdk\Services\Users\Data\Entities\User', 'provider_id');
	}

	public function client()
	{
		return $this
			->belongsTo('PragmaRX\Sdk\Services\Users\Data\Entities\User', 'client_id');
	}

}
