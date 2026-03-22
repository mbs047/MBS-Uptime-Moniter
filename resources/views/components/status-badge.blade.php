@props(['status'])

@php
    $label = str($status)->replace('_', ' ')->title();
@endphp

<span {{ $attributes->class(['status-badge', 'status-badge--'.$status]) }}>
    {{ $label }}
</span>
