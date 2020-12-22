@extends('layouts.master-layouts')

@section('title')
    @lang('translation.Scrollable')
@endsection

@section('body')
    <body data-topbar="dark" data-layout-scrollable="true" data-layout="horizontal">
@endsection

@section('content')

    @component('common-components.breadcrumb')
        @slot('title') Scrollable Layout @endslot
        @slot('li_1') Layouts @endslot
        @slot('li_2') Scrollable Layout @endslot
    @endcomponent

@endsection
