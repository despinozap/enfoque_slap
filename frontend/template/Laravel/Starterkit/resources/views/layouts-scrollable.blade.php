@extends('layouts.master')

@section('title') @lang('translation.Scrollable') @endsection
@section('body')

    <body data-sidebar="dark" data-layout-scrollable="true">
    @endsection
    @section('content')

        @component('common-components.breadcrumb')
            @slot('title') Scrollable @endslot
            @slot('li_1') Layouts @endslot
            @slot('li_2') Scrollable @endslot
        @endcomponent

    @endsection
