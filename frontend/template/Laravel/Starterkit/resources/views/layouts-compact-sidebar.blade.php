@extends('layouts.master')

@section('title')
    @lang('translation.Compact_Sidebar')
@endsection
@section('body')

    <body data-sidebar="dark" data-sidebar-size="small">
    @endsection
    @section('content')

        @component('common-components.breadcrumb')
            @slot('title') Compact Sidebar @endslot
            @slot('li_1') Layouts @endslot
            @slot('li_2') Compact Sidebar @endslot
        @endcomponent

    @endsection
