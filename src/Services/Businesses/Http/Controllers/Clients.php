<?php

namespace PragmaRX\Sdk\Services\Businesses\Http\Controllers;

use Gate;
use Auth;
use Flash;
use Redirect;
use PragmaRX\Sdk\Core\Controller as BaseController;
use PragmaRX\Sdk\Services\Businesses\Data\Entities\BusinessClient;
use PragmaRX\Sdk\Services\Businesses\Http\Requests\UpdateBusiness as UpdateBusinessRequest;
use PragmaRX\Sdk\Services\Businesses\Data\Repositories\Businesses as BusinessesRepository;
use PragmaRX\Sdk\Services\Businesses\Http\Requests\CreateBusiness as CreateBusinessRequest;

class Clients extends BaseController
{
	/**
	 * @var BusinessesRepository
	 */
	private $businessesRepository;

	public function __construct(BusinessesRepository $businessesRepository)
	{
		$this->businessesRepository = $businessesRepository;
	}

	public function create($businessId)
	{
		if (Gate::denies('create', $this->businessesRepository->findById($businessId)))
		{
			abort(403);
		}

		return view('businesses.enterprises.clients.create')
				->with('businessId', $businessId)
				->with('business', $this->findBusiness($businessId))
				->with('postRoute', 'businesses.clients.store')
				->with('postRouteParameters', $businessId)
				->with('cancelRoute', 'businesses.enterprises.edit')
				->with('cancelRouteParameters', $businessId)
				->with('submitButton', 'Criar cliente');
	}

	public function store($businessId, CreateBusinessRequest $createBusinessRequest)
	{
		if (Gate::denies('store', $this->businessesRepository->findById($businessId)))
		{
			abort(403);
		}

		$this->businessesRepository->createClientForBusiness($businessId, $createBusinessRequest['name']);

		Flash::message(t('paragraphs.client-created'));

		return redirect()->route('businesses.enterprises.edit', ['businessId' => $businessId]);
	}

	public function edit($businessId, $clientId)
	{
		if (Gate::denies('edit', $this->businessesRepository->findById($businessId)))
		{
			abort(403);
		}

		$business = $this->businessesRepository->findById($businessId);
		$client = $this->businessesRepository->findClientById($clientId);
        $services = $client->chatServices->toArray();

		return view('businesses.enterprises.clients.edit')
			->with('business', $business)
			->with('client', $client)
            ->with('services', $services)
			->with('postRoute', 'businesses.clients.update')
			->with('postRouteParameters', $businessId)
			->with('cancelRoute', 'businesses.enterprises.edit')
			->with('cancelRouteParameters', $businessId)
            ->with('deleteUri', '/businesses/{businessId}/clients/{clientId}/services/{serviceId}/delete/')
        ;
	}

	public function update($businessId, UpdateBusinessRequest $updateBusinessRequest)
	{
		if (Gate::denies('update', $this->businessesRepository->findById($businessId)))
		{
			abort(403);
		}

		$this->businessesRepository->updateClient($updateBusinessRequest->all());

		Flash::message(t('paragraphs.client-updated'));

		return redirect()->route('businesses.enterprises.edit', ['businessId' => $updateBusinessRequest['businessId']]);
	}

	public function delete($businessId, $clientId)
	{
		if (Gate::denies('delete', $this->businessesRepository->findById($businessId)))
		{
			abort(403);
		}

		$this->businessesRepository->deleteClient($businessId, $clientId);

		Flash::message(t('paragraphs.client-deleted'));

		return redirect()->route('businesses.enterprises.edit', compact('businessId'));
	}

	private function findBusiness($businessId)
	{
		return $this->businessesRepository->findById($businessId);
	}
}
