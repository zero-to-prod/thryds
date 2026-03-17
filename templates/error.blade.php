@php
    use ZeroToProd\Thryds\ViewModels\ErrorViewModel;
    /** @var ErrorViewModel $ErrorViewModel */
@endphp
@extends('base')

@section('title', $ErrorViewModel->status_code . ' - Thryds')

@section('body')
    <x-card>
        <h1 class="text-2xl font-bold text-text mb-2">{{ $ErrorViewModel->status_code }}</h1>
        <p class="text-text-muted">{{ $ErrorViewModel->message }}</p>
    </x-card>
@endsection
