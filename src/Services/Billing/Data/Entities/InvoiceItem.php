<?php

namespace PragmaRX\Sdk\Services\Billing\Data\Entities;

use PragmaRX\Sdk\Core\Model;

class InvoiceItem extends Model {

	protected $fillable = [
		'invoice_id',
		'item_id',
		'amount',
	];

}
