@props(['status'])
@php($meta = \App\Models\Order::STATUSES[$status] ?? ['label' => $status])
<span {{ $attributes->class(['ui-status', 'ui-status-'.$status]) }}><span class="ui-status-dot" aria-hidden="true"></span>{{ $meta['label'] }}</span>
