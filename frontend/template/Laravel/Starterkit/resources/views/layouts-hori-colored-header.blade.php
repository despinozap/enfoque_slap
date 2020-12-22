@extends('layouts.master-layouts')

@section('title')
    @lang('translation.Colored_Topbar')
@endsection

@section('body')
    <body data-topbar="colored" data-layout="horizontal">
@endsection

@section('content')

    @component('common-components.breadcrumb')
        @slot('title') Colored Header @endslot
        @slot('li_1') Layouts @endslot
        @slot('li_2') Colored Header @endslot
    @endcomponent

@endsection
