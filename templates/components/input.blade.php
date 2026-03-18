@php use ZeroToProd\Thryds\UI\InputType; @endphp
@props([
    'type' => InputType::text->value,
])
<input type="{{ $type }}" {{ $attributes->class([
    'block w-full rounded-md border border-border bg-surface px-3 py-2 text-text placeholder:text-text-muted focus:border-primary focus:ring-1 focus:ring-primary',
]) }} />
