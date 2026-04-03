<article class="posts-item sites-item d-flex style-sites-default" data-title="{{ $site->title }}" data-desc="{{ $site->description ?? '' }}" data-url="{{ $site->url }}" data-site-id="{{ $site->id }}">
    <a href="{{ $site->url }}" target="_blank" rel="noopener" class="sites-body" title="{{ $site->title }}">
        <div class="item-header">
            <div class="item-media">
                @if($site->favicon_url)
                <img src="{{ $site->favicon_url }}" alt="" class="fill-cover sites-icon" style="height: auto; width: auto;"
                     onerror="var img=this;var host=encodeURIComponent(new URL('{{ $site->url }}').hostname);if(!img.dataset.retried){img.dataset.retried='1';img.src='https://t2.gstatic.cn/faviconV2?client=SOCIAL&type=FAVICON&fallback_opts=TYPE,SIZE,URL&size=128&url=https://'+host;}else{var p=img.closest('.item-media');if(p){p.innerHTML='<div class=\'d-flex align-items-center justify-content-center rounded-2 bg-primary bg-gradient\' style=\'width:40px;height:40px;\'><span class=\'text-white fw-bold small\'>' + mb_substr('{{ $site->title }}', 0, 1) + '</span></div>';}}">
                @else
                <div class="d-flex align-items-center justify-content-center rounded-2 bg-primary bg-gradient" style="width: 40px; height: 40px;">
                    <span class="text-white fw-bold small">{{ mb_substr($site->title, 0, 1) }}</span>
                </div>
                @endif
            </div>
        </div>
        <div class="item-body overflow-hidden d-flex flex-column flex-fill">
            <h3 class="item-title line1"><b>{{ $site->title }}</b></h3>
            @if($site->description)
            <div class="line1 text-muted text-xs">{{ $site->description }}</div>
            @endif
        </div>
    </a>
    <div class="sites-tags">
        <a href="{{ $site->url }}" target="_blank" rel="noopener" class="togo ml-auto text-center text-muted" title="直达">
            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
        </a>
    </div>
</article>
