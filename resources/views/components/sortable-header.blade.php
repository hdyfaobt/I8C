{{-- 3-state cycle: default -> asc -> desc -> default --}}
@props(['column', 'label', 'align' => 'left'])

@php
    $currentSort = request('sort');
    $currentDirection = request('direction');

    if ($currentSort !== $column) {
        $nextDirection = 'asc';
    } elseif ($currentDirection === 'asc') {
        $nextDirection = 'desc';
    } else {
        $nextDirection = null;
    }

    $query = request()->except(['sort', 'direction']);
    if ($nextDirection) {
        $query['sort'] = $column;
        $query['direction'] = $nextDirection;
    }

    $isActive = $currentSort === $column;
    $icon = $isActive ? ($currentDirection === 'asc' ? '▲' : '▼') : '⇅';
@endphp

<th class="px-6 py-3 text-{{ $align }} text-xs font-medium text-gray-500 uppercase tracking-wider">
    <a href="{{ request()->url() }}{{ count($query) ? '?'.http_build_query($query) : '' }}"
       class="inline-flex items-center gap-1 hover:text-gray-700 transition">
        {{ $label }}
        <span class="text-[10px] {{ $isActive ? 'text-gray-600' : 'text-gray-300' }}">{{ $icon }}</span>
    </a>
</th>
