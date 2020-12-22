@extends('layouts.master')

@section('title')
    @lang('translation.Icon_Sidebar')
@endsection
@section('body')

    <body data-sidebar="dark" data-keep-enlarged="true" class="vertical-collpsed">
    @endsection
    @section('content')

        @component('common-components.breadcrumb')
            @slot('title') Icon Sidebar @endslot
            @slot('li_1') Layouts @endslot
            @slot('li_2') Icon Sidebar @endslot
        @endcomponent

    @endsection
