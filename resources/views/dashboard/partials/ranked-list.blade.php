{{-- rows: list of ['label' => string, 'value' => string, 'meta' => ?string, 'percent' => ?float, 'tone' => ?string] --}}
@php
    $rows = $rows ?? [];
    $emptyText = $emptyText ?? 'Nothing to show for this period.';
@endphp

@if (count($rows) === 0)
    <div class="dashboard-empty">{{ $emptyText }}</div>
@else
    <ol class="ranked-list">
        @foreach ($rows as $row)
            @php
                $rowTone = $row['tone'] ?? 'primary';
            @endphp
            <li class="ranked-list__item">
                <div class="ranked-list__row">
                    <span class="ranked-list__label text-truncate" title="{{ $row['label'] }}">{{ $row['label'] }}</span>
                    <span class="ranked-list__value ranked-list__value--{{ $rowTone }} text-nowrap">{{ $row['value'] }}</span>
                </div>
                @isset($row['percent'])
                    <div class="ranked-list__bar">
                        <span class="ranked-list__fill ranked-list__fill--{{ $rowTone }}" style="width: {{ max(2, min(100, $row['percent'])) }}%"></span>
                    </div>
                @endisset
                @if (! empty($row['meta']))
                    <div class="ranked-list__meta">{{ $row['meta'] }}</div>
                @endif
            </li>
        @endforeach
    </ol>
@endif
