{{-- Colored order status badge --}}
@props(['status'])

@if ($status === 'awaiting_review')
    <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs">Wacht op validatie</span>
@elseif ($status === 'pending')
    <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs">In afwachting</span>
@elseif ($status === 'sent')
    <span class="px-2 py-1 bg-orange-100 text-orange-700 rounded text-xs">Naar de orderpicker</span>
@elseif ($status === 'failed')
    <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs">Mislukt</span>
@elseif ($status === 'refused')
    <span class="px-2 py-1 bg-red-200 text-red-800 rounded text-xs">Geweigerd</span>
@elseif ($status === 'cancelled')
    <span class="px-2 py-1 bg-gray-200 text-gray-700 rounded text-xs">Geannuleerd</span>
@elseif ($status === 'ready_for_pickup')
    <span class="px-2 py-1 bg-purple-100 text-purple-700 rounded text-xs">Klaar om op te halen</span>
@elseif ($status === 'received')
    <span class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded text-xs">Ontvangen door klant</span>
@endif
