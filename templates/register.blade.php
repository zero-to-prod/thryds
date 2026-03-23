@php
    use ZeroToProd\Thryds\Blade\View;
    use ZeroToProd\Framework\Requests\InputField;
    use ZeroToProd\Framework\Routes\HttpMethod;
    use ZeroToProd\Thryds\Routes\RouteList;
    use ZeroToProd\Framework\UI\ButtonVariant;
    use ZeroToProd\Thryds\ViewModels\RegisterViewModel;
    /** @var RegisterViewModel $RegisterViewModel */
    /** @var list<InputField> $fields */
@endphp
@extends('base')

@section('title', View::register->pageTitle())

@section('body')
    <x-card>
        <h1 class="text-2xl font-bold text-text mb-6">Create an account</h1>
        <form method="{{ HttpMethod::POST->value }}" action="@route(RouteList::register)">
            @foreach ($fields as $field)
                <x-form-group :label="$field->label" :error="$field->error($RegisterViewModel)">
                    <x-input
                            :type="$field->InputType->value"
                            :id="$field->name"
                            :name="$field->name"
                            :required="$field->required"
                            :value="$field->value($RegisterViewModel)"
                    />
                </x-form-group>
            @endforeach
            <x-button :variant="ButtonVariant::primary->value" type="submit">Register</x-button>
        </form>
        <p class="mt-4"><a href="@route(RouteList::login)" class="text-primary hover:text-primary-hover">Already have an account? Login</a></p>
    </x-card>
@endsection
