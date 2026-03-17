@props([])
<div {{ $attributes->merge(['class' => 'rounded-lg border border-border bg-surface p-6 shadow-sm']) }}>
    {{ $slot }}
</div>
