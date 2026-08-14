{{--
    Shared labeled input for the create/edit forms (customers, products,
    admin users) — same label + input + error markup was copy-pasted in
    every one of those forms. Renders a <textarea> instead of <input>
    when type="textarea".

    Usage:
        <x-form-field label="Naam" name="name" :value="old('name')" required />
        <x-form-field label="Adres" name="address" type="textarea" :value="old('address')" last />
--}}
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
                  class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                  @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        >{{ $value }}</textarea>
    @else
        <input type="{{ $type }}" name="{{ $name }}" value="{{ $value }}"
               @if (! is_null($step)) step="{{ $step }}" @endif
               @if (! is_null($min)) min="{{ $min }}" @endif
               @if ($placeholder) placeholder="{{ $placeholder }}" @endif
               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
    @endif

    @error($name)
        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
    @enderror
</div>
