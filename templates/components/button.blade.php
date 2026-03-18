@php use ZeroToProd\Thryds\UI\ButtonVariant; use ZeroToProd\Thryds\UI\ButtonSize; @endphp
@props([
    'variant' => ButtonVariant::primary->value,
    'size' => ButtonSize::md->value,
    'type' => 'button',
])
<button type="{{ $type }}" {{ $attributes->class([
    'inline-flex items-center justify-center font-semibold rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors',
    'bg-primary text-white hover:bg-primary-hover focus:ring-primary' => $variant === ButtonVariant::primary->value,
    'bg-danger text-white hover:bg-danger-hover focus:ring-danger' => $variant === ButtonVariant::danger->value,
    'bg-surface-alt text-text border border-border hover:bg-border focus:ring-primary' => $variant === ButtonVariant::secondary->value,
    'py-1 px-3 text-sm' => $size === ButtonSize::sm->value,
    'py-2 px-4 text-base' => $size === ButtonSize::md->value,
    'py-3 px-6 text-lg' => $size === ButtonSize::lg->value,
]) }}>
    {{ $slot }}
</button>
