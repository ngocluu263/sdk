@extends(Config::get('pragmarx/sdk::stats_layout'))

@section('page-contents')
	@include('pragmarx/sdk::_dataTable', array('route' => route('sdk.stats.api.log', array('uuid' => $uuid))))

	<div id='table_div'></div>
@stop
