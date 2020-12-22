@extends('layouts.master-layouts')

@section('title')
    @lang('translation.Preloader')
@endsection
@section('body')
    <body data-topbar="dark" data-layout="horizontal">
        <div id="preloader">
            <div id="status">
                <div class="spinner-chase">
                    <div class="chase-dot"></div>
                    <div class="chase-dot"></div>
                    <div class="chase-dot"></div>
                    <div class="chase-dot"></div>
                    <div class="chase-dot"></div>
                    <div class="chase-dot"></div>
                </div>
            </div>
        </div>
@endsection
@section('content')

    @component('common-components.breadcrumb')
        @slot('title') Preloader Layout @endslot
        @slot('li_1') Horizontal @endslot
        @slot('li_2') Preloader Layout @endslot
    @endcomponent

@endsection
