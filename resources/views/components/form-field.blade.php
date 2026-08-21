{{-- Shared labeled input field --}}
@props([
    'label',
    'name',
    'type' => 'text',
    'value' => null,
    'required' => false,
    'placeholder' => null,
    'step' => null,
    'min' => null,
    'rows' => 3,
    'last' => false,
])

<div class="{{ $last ? 'mb-6' : 'mb-4' }}">
    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}{{ $required ? ' *' : '' }}</label>

    @if ($type === 'textarea')
        <textarea name="{{ $name }}" rows="{{ $rows }}"
                  class="w-full rounded-md border-2 border-gray-300 px-4 py-2.5 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                  @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        >{{ $value }}</textarea>
    @else
        <input type="{{ $type }}" name="{{ $name }}" value="{{ $value }}"
               @if (! is_null($step)) step="{{ $step }}" @endif
               @if (! is_null($min)) min="{{ $min }}" @endif
               @if ($placeholder) placeholder="{{ $placeholder }}" @endif
               class="w-full rounded-md border-2 border-gray-300 px-4 py-2.5 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
    @endif

    @error($name)
        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
    @enderror
</div>
