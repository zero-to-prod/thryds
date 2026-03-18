@php use ZeroToProd\Thryds\UI\AlertVariant; @endphp
@props([
    'variant' => AlertVariant::info->value,
])
<div role="alert" {{ $attributes->class([
    'rounded-md p-4 text-sm',
    'bg-primary/10 text-primary border border-primary/20' => $variant === AlertVariant::info->value,
    'bg-danger/10 text-danger border border-danger/20' => $variant === AlertVariant::danger->value,
    'bg-success/10 text-success border border-success/20' => $variant === AlertVariant::success->value,
]) }}>
    {{ $slot }}
</div>
