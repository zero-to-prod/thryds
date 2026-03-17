@props([
    'variant' => 'info',
])
<div role="alert" {{ $attributes->class([
    'rounded-md p-4 text-sm',
    'bg-primary/10 text-primary border border-primary/20' => $variant === 'info',
    'bg-danger/10 text-danger border border-danger/20' => $variant === 'danger',
    'bg-success/10 text-success border border-success/20' => $variant === 'success',
]) }}>
    {{ $slot }}
</div>
