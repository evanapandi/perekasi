<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Penggunaan Reagent</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            margin: 24px;
            color: #333;
        }

        h1 {
            text-align: center;
            font-size: 18px;
            margin-bottom: 8px;
        }

        .period {
            text-align: center;
            margin-bottom: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #666;
            padding: 6px;
            text-align: left;
        }

        th {
            background: #f0f0f0;
        }

        tfoot td {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Laporan Penggunaan Reagent</h1>
    <div class="period">
        <strong>Periode:</strong>
        {{ \Carbon\Carbon::parse($start_date)->translatedFormat('d F Y') }}
        &ndash;
        {{ \Carbon\Carbon::parse($end_date)->translatedFormat('d F Y') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Reagent</th>
                <th>Total Penggunaan</th>
                <th>Satuan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($summaries as $index => $summary)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $summary->nama_reagent }}</td>
                    <td>{{ number_format($summary->total_penggunaan, 2) }}</td>
                    <td>{{ $summary->satuan }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center;">Tidak ada data</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">Jumlah Data</td>
                <td colspan="2">{{ $summaries->count() }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>



