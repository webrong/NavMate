<!DOCTYPE html>
<html lang="zh-Hans">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimum-scale=1.0, maximum-scale=0.0">
    <title>{{ config('app.name', '导航') }}</title>
    <meta name="theme-color" content="#f9f9f9" />
    <meta name="keywords" content="{{ config('app.name', '导航') }},办公导航,职场办公,办公网站,网址导航" />
    <meta name="description" content="{{ config('app.name', '导航') }} - 为职场人士提供全面的办公网址导航服务" />
    <link rel="shortcut icon" href="static/image/logo.png">
    <link rel="apple-touch-icon" href="static/image/logo.png">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="/static/css/iconfont.css">
    <link rel="stylesheet" href="/static/css/font_4814538_uewl30t0cv.css">
    @vite(['resources/css/tailwind.css', 'resources/js/app.js'])
</head>
<body>
    <div id="app"></div>
</body>
</html>
