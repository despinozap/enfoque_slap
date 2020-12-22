@extends('layouts.master')

@section('title')
    @lang('translation.Preloader')
@endsection
@section('body')

    <body>
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
            @slot('title') Preloader @endslot
            @slot('li_1') Layouts @endslot
            @slot('li_2') Preloader @endslot
        @endcomponent

    @endsection
