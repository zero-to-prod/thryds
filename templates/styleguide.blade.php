@php use ZeroToProd\Thryds\Routes\RouteList; @endphp
@extends('base')

@section('title', 'Styleguide — Thryds')

@section('body')
    <div class="mx-auto max-w-3xl px-6 py-12 space-y-12">
        <h1 class="text-3xl font-bold text-text">Styleguide</h1>

        {{-- x-button --}}
        <section class="space-y-4">
            <h2 class="text-xl font-semibold text-text">x-button</h2>

            <h3 class="text-sm font-medium text-text-muted">Variants</h3>
            <div class="flex flex-wrap gap-3">
                <x-button variant="primary">Primary</x-button>
                <x-button variant="danger">Danger</x-button>
                <x-button variant="secondary">Secondary</x-button>
            </div>

            <h3 class="text-sm font-medium text-text-muted">Sizes</h3>
            <div class="flex flex-wrap items-center gap-3">
                <x-button variant="primary" size="sm">Small</x-button>
                <x-button variant="primary" size="md">Medium</x-button>
                <x-button variant="primary" size="lg">Large</x-button>
            </div>

            <h3 class="text-sm font-medium text-text-muted">Sizes × Variants</h3>
            <div class="flex flex-wrap items-center gap-3">
                <x-button variant="danger" size="sm">Danger sm</x-button>
                <x-button variant="danger" size="lg">Danger lg</x-button>
                <x-button variant="secondary" size="sm">Secondary sm</x-button>
                <x-button variant="secondary" size="lg">Secondary lg</x-button>
            </div>
        </section>

        {{-- x-input --}}
        <section class="space-y-4">
            <h2 class="text-xl font-semibold text-text">x-input</h2>

            <div class="max-w-sm space-y-3">
                <x-input type="text" placeholder="Text input"/>
                <x-input type="email" placeholder="Email input"/>
                <x-input type="password" placeholder="Password input"/>
            </div>
        </section>

        {{-- x-form-group --}}
        <section class="space-y-4">
            <h2 class="text-xl font-semibold text-text">x-form-group</h2>

            <div class="max-w-sm">
                <x-form-group label="Username">
                    <x-input type="text" placeholder="Enter username"/>
                </x-form-group>

                <x-form-group label="Email" error="This email is already taken.">
                    <x-input type="email" placeholder="Enter email"/>
                </x-form-group>
            </div>
        </section>

        {{-- x-card --}}
        <section class="space-y-4">
            <h2 class="text-xl font-semibold text-text">x-card</h2>

            <x-card>
                <h3 class="text-lg font-semibold text-text">Card Title</h3>
                <p class="mt-2 text-text-muted">Card content with supporting text.</p>
            </x-card>
        </section>

        {{-- x-alert --}}
        <section class="space-y-4">
            <h2 class="text-xl font-semibold text-text">x-alert</h2>

            <div class="space-y-3">
                <x-alert variant="info">This is an informational alert.</x-alert>
                <x-alert variant="danger">This is a danger alert.</x-alert>
                <x-alert variant="success">This is a success alert.</x-alert>
            </div>
        </section>

        <p class="text-sm text-text-muted"><a href="{{ RouteList::home->value }}" class="text-primary hover:text-primary-hover">Home</a></p>
    </div>
@endsection
