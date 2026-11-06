@props([
    'name' => 'circle',
    'class' => 'w-4 h-4',
])

<i data-lucide="{{ $name }}" {{ $attributes->merge(['class' => $class . ' inline-block align-middle shrink-0']) }}></i>

