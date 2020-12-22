@extends('layouts.master-layouts')

@section('title')
    @lang('translation.Horizontal')
@endsection

@section('content')

    @component('common-components.breadcrumb')
        @slot('title') Horizontal Layout @endslot
        @slot('li_1') Layouts @endslot
        @slot('li_2') Horizontal Layout @endslot
    @endcomponent

@endsection
