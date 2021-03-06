<?php

namespace PragmaRX\Sdk\Services\Messages\Http\Requests;

use PragmaRX\Sdk\Core\Validation\FormRequest;

class SendMessage extends FormRequest {

	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array
	 */
	public function rules()
	{
		$rules = [
			'body' => 'required',
		];

		if ( ! $this->get('answering_message_id'))
		{
			$rules = array_merge(
				$rules,
				[
					'recipients' => 'required',
					'subject' => 'required',
				]
			);
		}

		return $rules;
	}

}
