@props(['title'])
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <style>
        body {
            margin: 0 auto;
            max-width: 40rem;
            padding: 2rem 1rem 3rem;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            line-height: 1.6;
            color: #2b2330;
            background: #fffafc;
        }
        h1 { font-size: 1.6rem; line-height: 1.3; }
        h2 { font-size: 1.15rem; margin-top: 2rem; }
        a { color: #a8326e; }
        .table-wrap { overflow-x: auto; margin: 1rem 0; }
        table { width: 100%; border-collapse: collapse; font-size: 0.95rem; }
        th, td { padding: 0.5rem 0.6rem; border: 1px solid #ead7e1; text-align: left; vertical-align: top; }
        th { background: #fbeef4; }
        li { margin-bottom: 0.35rem; }
        footer { margin-top: 3rem; padding-top: 1rem; border-top: 1px solid #ead7e1; font-size: 0.9rem; }
        @media (prefers-color-scheme: dark) {
            body { color: #f3e9ef; background: #1d171b; }
            a { color: #f08cbf; }
            th, td { border-color: #3a2c34; }
            th { background: #2a2026; }
            footer { border-top-color: #3a2c34; }
        }
    </style>
</head>
<body>
    {{ $slot }}

    <footer>
        <a href="{{ route('privacy-policy') }}">Kebijakan privasi</a>
        &middot;
        <a href="{{ route('terms') }}">Syarat layanan</a>
        &middot;
        <a href="{{ route('account-deletion') }}">Hapus akun</a>
    </footer>
</body>
</html>
