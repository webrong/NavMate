<!DOCTYPE html>
<html lang="zh-Hans">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后台管理 - {{ config('app.name', 'NavMate') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        // Ant Design icon font CSS (189KB). Preload it so the browser starts
        // fetching the woff2 in parallel with admin.js, then immediately apply
        // it via a normal <link rel="stylesheet">. The preload gives a head
        // start; the stylesheet tag is what the browser expects to "consume"
        // the preload (avoids the "preloaded but not used" warning that the
        // pure JS-swap approach triggers).
        $fontCss = '/static/css/font_4814538_uewl30t0cv.css';
    @endphp
    <link rel="preload" href="{{ $fontCss }}" as="style">
    <link rel="stylesheet" href="{{ $fontCss }}">
    @vite(['resources/js/admin/admin.js'])
</head>
<body>
    <div id="admin-app"></div>
</body>
</html>
