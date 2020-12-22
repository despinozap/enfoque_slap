@extends('layouts.master-layouts')

@section('title')
    @lang('translation.Boxed_Layout')
@endsection

@section('body')

    <body data-topbar="light" data-layout="horizontal" data-layout-size="boxed">
    @endsection

    @section('content')

        @component('common-components.breadcrumb')
            @slot('title') Horizontal Boxed Width @endslot
            @slot('li_1') Layouts @endslot
            @slot('li_2') Horizontal Boxed Width @endslot
        @endcomponent

    @endsection
