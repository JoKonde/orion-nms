<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $report['title'] ?? 'Rapport ORION' }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111; margin: 2rem; line-height: 1.45; }
        h1 { margin: 0 0 0.25rem; font-size: 1.5rem; }
        .meta { color: #555; font-size: 0.9rem; margin-bottom: 1.5rem; }
        table { width: 100%; border-collapse: collapse; margin: 1rem 0 2rem; font-size: 0.85rem; }
        th, td { border: 1px solid #ccc; padding: 0.45rem 0.6rem; text-align: left; }
        th { background: #f3f4f6; }
        .summary { margin-bottom: 1.5rem; }
        .summary dt { font-weight: 600; }
        .summary dd { margin: 0 0 0.5rem; }
        @media print {
            body { margin: 1rem; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">Imprimer / PDF</button>

    <h1>ORION NMS — {{ $report['title'] ?? 'Rapport' }}</h1>
    <p class="meta">
        Genere le {{ \Carbon\Carbon::parse($report['generated_at'])->format('d/m/Y H:i') }}
        @if (!empty($report['period']))
            — Periode du {{ \Carbon\Carbon::parse($report['period']['from'])->format('d/m/Y') }}
            au {{ \Carbon\Carbon::parse($report['period']['to'])->format('d/m/Y') }}
        @endif
    </p>

    @if (!empty($report['summary']))
        <section class="summary">
            <h2>Synthese</h2>
            <dl>
                @foreach ($report['summary'] as $row)
                    <dt>{{ $row['label'] }}</dt>
                    <dd>{{ $row['value'] }}</dd>
                @endforeach
            </dl>
        </section>
    @endif

    @if (!empty($report['columns']) && !empty($report['rows']))
        <section>
            <h2>Detail</h2>
            <table>
                <thead>
                    <tr>
                        @foreach ($report['columns'] as $col)
                            <th>{{ $col['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($report['rows'] as $row)
                        <tr>
                            @foreach ($report['columns'] as $col)
                                <td>{{ $row[$col['key']] ?? '—' }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @elseif (!empty($report['sections'][0]['empty']))
        <p>{{ $report['sections'][0]['empty'] }}</p>
    @endif
</body>
</html>
