@props([
    'label' => '',
    'error' => '',
])
<div {{ $attributes->merge(['class' => 'mb-4']) }}>
    @if($label)
        <label class="block mb-1 text-sm font-medium text-text">
            {{ $label }}
        </label>
    @endif
    {{ $slot }}
    @if($error)
        <p class="mt-1 text-sm text-danger">{{ $error }}</p>
    @endif
</div>
