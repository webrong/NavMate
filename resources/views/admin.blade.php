<!DOCTYPE html>
<html lang="zh-Hans">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后台管理 - {{ config('app.name', '导航') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="/static/css/font_4814538_uewl30t0cv.css">
    @vite(['resources/js/admin/admin.js'])
</head>
<body>
    <div id="admin-app"></div>
</body>
</html>
