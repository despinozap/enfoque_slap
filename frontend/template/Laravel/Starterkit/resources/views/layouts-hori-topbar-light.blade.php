@extends('layouts.master-layouts')

@section('title')
    @lang('translation.Topbar_Light')
@endsection

@section('body')

    <body data-topbar="light" data-layout="horizontal">
    @endsection

    @section('content')

        @component('common-components.breadcrumb')
            @slot('title') Horizontal Topbar Light @endslot
            @slot('li_1') Layouts @endslot
            @slot('li_2') Horizontal Topbar Light @endslot
        @endcomponent

    @endsection
