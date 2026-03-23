@php use ZeroToProd\Thryds\Routes\RouteList; @endphp
@extends('base')

@section('title', 'Login — Thryds')

@section('body')
    <x-card>
        <h1 class="text-2xl font-bold text-text mb-6">Login</h1>
        <form method="post" action="@route(RouteList::login)">
            <x-form-group label="Email">
                <x-input type="email" id="email" name="email" required/>
            </x-form-group>
            <x-form-group label="Password">
                <x-input type="password" id="password" name="password" required/>
            </x-form-group>
            <x-button variant="primary" type="submit">Login</x-button>
        </form>
        <p class="mt-4"><a href="@route(RouteList::home)" class="text-primary hover:text-primary-hover">Home</a></p>
    </x-card>
@endsection
