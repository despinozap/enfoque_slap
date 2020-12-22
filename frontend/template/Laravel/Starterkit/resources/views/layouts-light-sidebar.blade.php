@extends('layouts.master')

@section('title')
    @lang('translation.Light_Sidebar')
@endsection
@section('body')

    <body data-topbar="dark" data-sidebar="light">
    @endsection
    @section('content')
        @component('common-components.breadcrumb')
            @slot('title') Light Sidebar @endslot
            @slot('li_1') Layouts @endslot
            @slot('li_2') Light Sidebar @endslot
        @endcomponent

    @endsection
