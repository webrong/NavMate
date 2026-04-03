<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', '导航') }}</title>
    @vite(['resources/css/app.scss', 'resources/js/frontend.js'])
</head>
<body class="home blog wp-theme-onenav container-body aside-show have-banner">

{{-- ===== Header - sbrdh.com 完全一致 ===== --}}
<header class="main-header header-fixed">
    <div class="header-nav blur-bg">
        <nav class="switch-container container-header nav-top header-center d-flex align-items-center h-100 container">
            {{-- Logo --}}
            <div class="navbar-logo d-flex mr-4">
                <h1 class="text-hide position-absolute">{{ config('app.name', '导航') }}</h1>
                <a href="{{ url('/') }}" class="logo-expanded">
                    <img src="https://www.sbrdh.com/wp-content/uploads/2025/01/1737429669-水印-09.png" height="36" alt="{{ config('app.name', '导航') }}">
                </a>
            </div>

            {{-- Desktop Nav --}}
            <div class="navbar-header-menu">
                <ul class="nav navbar-header d-none d-md-flex mr-3">
                    <li class="menu-item">
                        <a href="{{ url('/') }}">
                            <i class="io io-shenghuofuwu11 icon-fw icon-lg mr-2"></i>
                            <span>首页</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="flex-fill"></div>

            {{-- Right Tools --}}
            <ul class="nav header-tools position-relative">
                @auth
                <li class="header-icon-btn nav-login d-none d-md-block">
                    <a href="{{ route('admin.dashboard') }}">
                        <i class="iconfont icon-user icon-lg"></i>
                    </a>
                </li>
                @endauth

                <li class="header-icon-btn nav-search">
                    <a href="javascript:" class="search-ico-btn nav-search-icon">
                        <i class="search-bar"></i>
                    </a>
                </li>
            </ul>

            {{-- Mobile Menu Button --}}
            <div class="d-block d-md-none menu-btn">
                <span class="menu-bar"></span>
                <span class="menu-bar"></span>
                <span class="menu-bar"></span>
            </div>
        </nav>
    </div>
</header>

{{-- ===== Search Banner - sbrdh.com 完全一致 ===== --}}
<div class="header-banner mb-4 module-id-0 header-calculate header-big css-color post-top bg-gradual" style="background-image: linear-gradient(45deg, #a524d8 0%, #327ec1 50%, #e27826 100%);">
    <div class="switch-container search-container content container">
        <div id="search" class="big-search mx-auto" style="--big-search-height:270px;--big-mobile-height:200px">
            <div class="search-box-big">
                {{-- Category Tabs --}}
                <div class="search-list-menu no-scrollbar overflow-x-auto slider-ul">
                    <div class="search-menu slider-li active" data-target="#group-b">搜索</div>
                    <div class="search-menu slider-li" data-target="#group-d">社区</div>
                    <div class="search-menu slider-li" data-target="#group-e">生活</div>
                    <div class="search-menu slider-li" data-target="#group-H">视频</div>
                </div>

                {{-- Search Input --}}
                <form class="search-form">
                    <input type="text" id="site-search" class="form-control search-key" placeholder="百度一下" style="outline:0" autocomplete="off">
                    <div class="search-tools">
                        <span type="submit" class="btn vc-theme search-submit-btn">
                            <i class="iconfont icon-search"></i>
                        </span>
                    </div>
                </form>

                {{-- Search Groups --}}
                <div class="search-list-group">
                    {{-- Search Group --}}
                    <ul id="group-b" class="search-group group-b no-scrollbar overflow-x-auto active">
                        <li class="search-term active" data-placeholder="站内搜索">本站</li>
                        <li class="search-term" data-placeholder="百度一下">百度</li>
                        <li class="search-term" data-placeholder="微软Bing搜索">Bing</li>
                        <li class="search-term" data-placeholder="头条搜索">头条</li>
                        <li class="search-term" data-placeholder="360好搜">360</li>
                        <li class="search-term" data-placeholder="搜狗搜索">搜狗</li>
                        <li class="search-term" data-placeholder="谷歌搜索">Google</li>
                    </ul>
                    {{-- Community Group --}}
                    <ul id="group-d" class="search-group group-d no-scrollbar overflow-x-auto">
                        <li class="search-term" data-placeholder="知乎">知乎</li>
                        <li class="search-term" data-placeholder="微信">微信</li>
                        <li class="search-term" data-placeholder="微博">微博</li>
                        <li class="search-term" data-placeholder="豆瓣">豆瓣</li>
                    </ul>
                    {{-- Life Group --}}
                    <ul id="group-e" class="search-group group-e no-scrollbar overflow-x-auto">
                        <li class="search-term" data-placeholder="淘宝">淘宝</li>
                        <li class="search-term" data-placeholder="京东">京东</li>
                        <li class="search-term" data-placeholder="12306">12306</li>
                        <li class="search-term" data-placeholder="快递100">快递100</li>
                    </ul>
                    {{-- Video Group --}}
                    <ul id="group-H" class="search-group group-H no-scrollbar overflow-x-auto">
                        <li class="search-term" data-placeholder="抖音">抖音</li>
                        <li class="search-term" data-placeholder="哔哩哔哩">哔哩哔哩</li>
                        <li class="search-term" data-placeholder="快手">快手</li>
                        <li class="search-term" data-placeholder="腾讯视频">腾讯视频</li>
                        <li class="search-term" data-placeholder="优酷">优酷</li>
                        <li class="search-term" data-placeholder="爱奇艺">爱奇艺</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@yield('content')

{{-- ===== Footer ===== --}}
<footer class="site-footer mt-4">
    <div class="container">
        <div class="footer-meta text-center text-xs text-muted py-3">
            <p class="mb-1">&copy; {{ date('Y') }} {{ config('app.name', '导航') }}</p>
            <p class="mb-0">ICP备案号</p>
        </div>
    </div>
</footer>

</body>
</html>
