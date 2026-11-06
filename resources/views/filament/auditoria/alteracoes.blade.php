@php
    $alteracoes = $getState() ?? [];
    $fmt = fn ($v) => match (true) {
        $v === null || $v === '' => '—',
        $v === true              => 'sim',
        $v === false             => 'não',
        is_scalar($v)            => (string) $v,
        default                  => json_encode($v, JSON_UNESCAPED_UNICODE),
    };
@endphp

@if (empty($alteracoes))
    <p class="text-sm text-gray-500">Sem detalhes.</p>
@else
    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-white/10">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left dark:bg-white/5">
                <tr>
                    <th class="px-3 py-2 font-medium">Campo</th>
                    <th class="px-3 py-2 font-medium">De</th>
                    <th class="px-3 py-2 font-medium">Para</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                @foreach ($alteracoes as $campo => $valores)
                    <tr>
                        <td class="px-3 py-2 font-mono text-xs">{{ $campo }}</td>
                        <td class="px-3 py-2 text-gray-500 break-all">{{ $fmt($valores['de'] ?? null) }}</td>
                        <td class="px-3 py-2 break-all">{{ $fmt($valores['para'] ?? null) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
