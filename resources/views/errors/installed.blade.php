<!DOCTYPE html>
<html lang="zh-Hans">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>已安装 - {{ config('app.name', 'NavMate') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f0f2f5;
            color: #333;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .container {
            text-align: center;
            max-width: 480px;
        }
        .icon {
            width: 72px;
            height: 72px;
            background: #1677ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 36px;
            color: #fff;
        }
        h1 { font-size: 22px; font-weight: 600; margin-bottom: 8px; color: #1a1a1a; }
        p { font-size: 14px; color: #666; margin-bottom: 24px; line-height: 1.6; }
        code {
            background: #f5f5f5;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 13px;
            color: #d48806;
        }
        a {
            display: inline-block;
            background: #1677ff;
            color: #fff;
            padding: 10px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.2s;
        }
        a:hover { background: #4096ff; }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">&#10003;</div>
        <h1>应用已安装</h1>
        <p>站点已完成初始安装，无需重复操作。<br>如需重新安装，请先删除 <code>storage/app/installed</code> 文件。</p>
        <a href="/">返回首页</a>
    </div>
</body>
</html>
