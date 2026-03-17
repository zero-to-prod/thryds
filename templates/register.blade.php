@php use ZeroToProd\Thryds\Routes\Route; @endphp
@extends('base')

@section('title', 'Register — Thryds')

@section('body')
    <x-card>
        <h1 class="text-2xl font-bold text-text mb-6">Create an account</h1>
        <form method="post" action="{{ Route::register->value }}">
            <x-form-group label="Name">
                <x-input type="text" id="name" name="name" required />
            </x-form-group>
            <x-form-group label="Email">
                <x-input type="email" id="email" name="email" required />
            </x-form-group>
            <x-form-group label="Password">
                <x-input type="password" id="password" name="password" required />
            </x-form-group>
            <x-form-group label="Confirm Password">
                <x-input type="password" id="password_confirmation" name="password_confirmation" required />
            </x-form-group>
            <x-button variant="primary" type="submit">Register</x-button>
        </form>
        <p class="mt-4"><a href="{{ Route::login->value }}" class="text-primary hover:text-primary-hover">Already have an account? Login</a></p>
    </x-card>
@endsection
