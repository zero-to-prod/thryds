@php
    use ZeroToProd\Thryds\Routes\Route;
    use ZeroToProd\Thryds\ViewModels\RegisterViewModel;
    use ZeroToProd\Thryds\Requests\RegisterRequest;
    /** @var RegisterRequest $RegisterRequest*/
    /** @var RegisterViewModel $RegisterViewModel */
@endphp
@extends('base')

@section('title', 'Register — Thryds')

@section('body')
    <x-card>
        <h1 class="text-2xl font-bold text-text mb-6">Create an account</h1>
        <form method="post" action="{{ Route::register->value }}">
            <x-form-group label="Name" :error="$RegisterViewModel->name_error">
                <x-input type="text" id="name" name="{{RegisterRequest::name}}" required :value="$RegisterViewModel->name" />
            </x-form-group>
            <x-form-group label="Handle" :error="$RegisterViewModel->handle_error">
                <x-input type="text" id="handle" name="handle" required :value="$RegisterViewModel->handle" />
            </x-form-group>
            <x-form-group label="Email" :error="$RegisterViewModel->handle_error">
                <x-input type="email" id="email" name="email" required :value="$RegisterViewModel->email" />
            </x-form-group>
            <x-form-group label="Password" :error="$RegisterViewModel->password_error">
                <x-input type="password" id="password" name="password" required />
            </x-form-group>
            <x-form-group label="Confirm Password" :error="$RegisterViewModel->password_confirmation_error">
                <x-input type="password" id="password_confirmation" name="password_confirmation" required />
            </x-form-group>
            <x-button variant="primary" type="submit">Register</x-button>
        </form>
        <p class="mt-4"><a href="{{ Route::login->value }}" class="text-primary hover:text-primary-hover">Already have an account? Login</a></p>
    </x-card>
@endsection
