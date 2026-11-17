@props(['map', 'value'])
<x-badge :color="\App\Support\Labels::color($map, $value)" {{ $attributes }}>{{ \App\Support\Labels::label($map, $value) }}{{ $slot }}</x-badge>
