<?php

namespace PragmaRX\Sdk\Services\Groups\Http\Requests;

use PragmaRX\Sdk\Core\Validation\FormRequest;

class AddGroup extends FormRequest {

	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array
	 */
	public function rules()
	{
		return [
			'name' => 'required',
			'members' => 'required',
		];
	}

	public function messages()
	{
		return [
			'members.required' => t('paragraphs.you-need-to-select-members')
		];
	}

}
