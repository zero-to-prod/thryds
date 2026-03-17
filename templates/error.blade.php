@php
    use ZeroToProd\Thryds\ViewModels\ErrorViewModel;
    /** @var ErrorViewModel $ErrorViewModel */
@endphp
@extends('base')

@section('title', $ErrorViewModel->status_code . ' - Thryds')

@section('body')
    <h1>{{ $ErrorViewModel->status_code }}</h1>
    <p>{{ $ErrorViewModel->message }}</p>
@endsection