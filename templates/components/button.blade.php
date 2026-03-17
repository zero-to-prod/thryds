@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
])
<button type="{{ $type }}" {{ $attributes->class([
    'inline-flex items-center justify-center font-semibold rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors',
    'bg-primary text-white hover:bg-primary-hover focus:ring-primary' => $variant === 'primary',
    'bg-danger text-white hover:bg-danger-hover focus:ring-danger' => $variant === 'danger',
    'bg-surface-alt text-text border border-border hover:bg-border focus:ring-primary' => $variant === 'secondary',
    'py-1 px-3 text-sm' => $size === 'sm',
    'py-2 px-4 text-base' => $size === 'md',
    'py-3 px-6 text-lg' => $size === 'lg',
]) }}>
    {{ $slot }}
</button>
