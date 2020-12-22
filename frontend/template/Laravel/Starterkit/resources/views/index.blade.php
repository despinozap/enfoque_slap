@extends('layouts.master')

@section('title') @lang('translation.Dashboard') @endsection

@section('content')

    @component('common-components.breadcrumb')
        @slot('title') Dashboard @endslot
        @slot('li_1') Welcome to Skote Dashboard @endslot
    @endcomponent

@endsection

@section('script')
@endsection
