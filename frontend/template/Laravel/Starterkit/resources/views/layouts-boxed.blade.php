@extends('layouts.master')

@section('title')
    @lang('translation.Boxed_Layout')
@endsection
@section('body')

    <body data-sidebar="dark" data-keep-enlarged="true" class="vertical-collpsed" data-layout-size="boxed">
    @endsection
    @section('content')

        @component('common-components.breadcrumb')
            @slot('title') Boxed Width @endslot
            @slot('li_1') Layouts @endslot
            @slot('li_2') Boxed Width @endslot
        @endcomponent

    @endsection
