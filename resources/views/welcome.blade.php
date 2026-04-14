@extends('layouts.app')

@section('title', 'OpenKap — Record. Share. Ship faster.')
@section('meta_description', 'The modern screen recording platform for product teams. Capture, share instantly, and collaborate with context.')
@section('og_title', 'OpenKap — Record. Share. Ship faster.')
@section('og_description', 'The modern screen recording platform for product teams.')

{{-- Activates light mode in navbar + footer partials --}}
@section('light_mode', true)

{{-- Override body to light background --}}
@section('body_class', 'bg-[#fafaf9] text-stone-900 selection:bg-orange-200 selection:text-orange-900')

@push('styles')
<style>
    *,*::before,*::after{box-sizing:border-box}
    ::-webkit-scrollbar{width:6px}
    ::-webkit-scrollbar-track{background:#f5f5f4}
    ::-webkit-scrollbar-thumb{background:#d6d3d1;border-radius:3px}

    /* Orbs — two subtle warm tones only */
    .orb{position:fixed;border-radius:50%;filter:blur(100px);opacity:.28;pointer-events:none;z-index:0;animation:orbFloat 20s ease-in-out infinite}
    .orb-1{width:500px;height:500px;background:radial-gradient(circle,#fdba74 0%,transparent 70%);top:-160px;left:-120px;animation-delay:0s}
    .orb-2{width:400px;height:400px;background:radial-gradient(circle,#fed7aa 0%,transparent 70%);top:40px;right:-80px;animation-delay:-8s}
    @keyframes orbFloat{0%,100%{transform:translate(0,0)}50%{transform:translate(20px,-30px)}}

    /* Glass */
    .glass{background:rgba(255,255,255,.75);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.9)}

    /* Gradient text — subtle, single hue */
    .grad{background:linear-gradient(135deg,#ea580c 0%,#f97316 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}

    /* Entrance animations */
    .reveal{opacity:0;transform:translateY(20px);transition:opacity .7s cubic-bezier(.16,1,.3,1),transform .7s cubic-bezier(.16,1,.3,1)}
    .reveal.in{opacity:1;transform:translateY(0)}
    .reveal-r{opacity:0;transform:translateX(40px) perspective(1200px) rotateY(-6deg);transition:opacity .8s cubic-bezier(.16,1,.3,1),transform .8s cubic-bezier(.16,1,.3,1)}
    .reveal-r.in{opacity:1;transform:translateX(0) perspective(1200px) rotateY(0)}
    .d1{transition-delay:.08s}.d2{transition-delay:.16s}.d3{transition-delay:.24s}.d4{transition-delay:.32s}
    .d80{transition-delay:80ms}.d160{transition-delay:160ms}.d240{transition-delay:240ms}.d320{transition-delay:320ms}.d400{transition-delay:400ms}

    /* Badge pulse */
    .dot-pulse{width:7px;height:7px;border-radius:50%;background:#22c55e;animation:dpulse 2s ease-in-out infinite;flex-shrink:0;display:inline-block}
    @keyframes dpulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.5;transform:scale(.75)}}

    /* Preview tilt */
    #preview-wrap{transition:transform .15s ease-out;will-change:transform}

    /* Feature cards */
    .feat-card{transition:transform .3s cubic-bezier(.16,1,.3,1),box-shadow .3s}
    .feat-card:hover{transform:translateY(-5px);box-shadow:0 12px 40px rgba(0,0,0,.08)!important}
    .feat-card:hover .feat-arrow{color:#f97316;transform:translateX(4px)}
    .feat-arrow{transition:color .25s,transform .25s;display:block}

    /* Btn */
    .btn-p{display:inline-flex;align-items:center;gap:.5rem;background:#f97316;color:#fff;font-weight:700;text-decoration:none;border-radius:10px;box-shadow:0 2px 12px rgba(249,115,22,.3);transition:transform .2s,box-shadow .2s,background .2s}
    .btn-p:hover{background:#ea580c;transform:translateY(-1px);box-shadow:0 6px 20px rgba(249,115,22,.4)}

    /* Tag pill */
    .tag{display:inline-block;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:#f97316;background:rgba(249,115,22,.08);border:1px solid rgba(249,115,22,.15);border-radius:100px;padding:.3rem 1rem;margin-bottom:1.25rem}

    /* ── Hero section ─────────────────────────────────── */
    .hero-content {
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1.75rem;
        padding: 4rem 1rem 3rem;
    }

    /* Top REC indicator */
    .rec-indicator {
        display: inline-flex; align-items: center; gap: .45rem;
        font-size: .72rem; font-weight: 700;
        color: #ef4444; letter-spacing: .06em;
        background: rgba(239,68,68,.06);
        border: 1px solid rgba(239,68,68,.15);
        border-radius: 100px;
        padding: .3rem .9rem;
    }
    .rec-circle {
        width: 8px; height: 8px;
        border-radius: 50%; background: #ef4444;
        animation: recBlink 1.2s ease-in-out infinite;
        flex-shrink: 0;
    }
    @keyframes recBlink {
        0%,100% { opacity:1; box-shadow:0 0 0 0 rgba(239,68,68,.5); }
        50%      { opacity:.4; box-shadow:0 0 0 5px rgba(239,68,68,0); }
    }

    /* Feature cards */
    .feat-card{transition:transform .3s cubic-bezier(.16,1,.3,1),box-shadow .3s}
    .feat-card:hover{transform:translateY(-5px);box-shadow:0 12px 40px rgba(0,0,0,.08)!important}
    .feat-card:hover .feat-arrow{color:#f97316;transform:translateX(4px)}
    .feat-arrow{transition:color .25s,transform .25s;display:block}

    /* ── Demo carousel ───────────────────────────────────── */
    .demo-carousel-wrap { margin-top: 0; }

    .demo-carousel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.25rem;
        max-width: 1400px;
        margin-left: auto;
        margin-right: auto;
    }
    .demo-carousel-title {
        font-size: 1.15rem;
        font-weight: 800;
        color: #1c1917;
        letter-spacing: -.02em;
    }
    .demo-carousel-title span { color: #f97316; }
    .demo-carousel-nav {
        display: flex;
        gap: .5rem;
    }
    .demo-nav-btn {
        width: 36px; height: 36px;
        border-radius: 50%;
        border: 1.5px solid rgba(0,0,0,.12);
        background: #fff;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
        color: #44403c;
        transition: border-color .2s, background .2s, color .2s;
        box-shadow: 0 1px 4px rgba(0,0,0,.06);
    }
    .demo-nav-btn:hover { border-color: #f97316; color: #f97316; background: rgba(249,115,22,.04); }
    .demo-nav-btn:disabled { opacity: .35; cursor: default; }
    .demo-nav-btn:disabled:hover { border-color: rgba(0,0,0,.12); color: #44403c; background: #fff; }

    /* Default: fluid grid */
    .demo-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 1.25rem;
        max-width: 1400px;
        margin: 0 auto;
    }
    /* Carousel mode (>5 cards, toggled by JS) */
    .demo-grid.is-carousel {
        display: flex;
        overflow-x: auto;
        scroll-snap-type: x mandatory;
        -webkit-overflow-scrolling: touch;
        padding-bottom: .5rem;
        scrollbar-width: none;
        max-width: none;
        margin: 0;
    }
    .demo-grid.is-carousel::-webkit-scrollbar { display: none; }
    .demo-grid.is-carousel .demo-card { flex: 0 0 365px; scroll-snap-align: start; }

    /* Nav buttons only visible in carousel mode */
    .demo-carousel-nav { display: none; }
    .demo-carousel-wrap.is-carousel-mode .demo-carousel-nav { display: flex; }

    .demo-card {
        border-radius: 16px;
        overflow: hidden;
        border: 1.5px solid rgba(0,0,0,.08);
        background: #fff;
        display: flex;
        flex-direction: column;
        transition: transform .3s cubic-bezier(.16,1,.3,1), box-shadow .3s, border-color .2s;
        box-shadow: 0 2px 12px rgba(0,0,0,.06);
    }
    .demo-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 40px rgba(0,0,0,.1);
        border-color: rgba(249,115,22,.3);
    }
    .demo-media-wrap {
        position: relative;
        width: 100%;
        height: 220px;
        overflow: hidden;
        background: #f5f5f4;
        flex-shrink: 0;
    }
    .demo-media-wrap img,
    .demo-media-wrap video {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transition: transform .4s cubic-bezier(.16,1,.3,1);
    }
    .demo-card:hover .demo-media-wrap img,
    .demo-card:hover .demo-media-wrap video { transform: scale(1.03); }
    .demo-overlay {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(0,0,0,0);
        transition: background .25s;
        cursor: pointer;
    }
    .demo-card:hover .demo-overlay { background: rgba(0,0,0,.32); }
    .demo-overlay-icon {
        width: 52px; height: 52px;
        border-radius: 50%;
        background: rgba(255,255,255,.96);
        display: flex; align-items: center; justify-content: center;
        opacity: 0;
        transform: scale(.6);
        transition: opacity .22s, transform .28s cubic-bezier(.16,1,.3,1);
        box-shadow: 0 6px 24px rgba(0,0,0,.2);
        pointer-events: none;
    }
    .demo-card:hover .demo-overlay-icon { opacity: 1; transform: scale(1); }
    .demo-card-body { padding: 1rem 1.1rem 1.1rem; }
    .demo-card-title {
        font-size: .92rem;
        font-weight: 700;
        color: #1c1917;
        margin: 0 0 .3rem;
        line-height: 1.35;
    }
    .demo-card-desc {
        font-size: .78rem;
        color: #78716c;
        line-height: 1.55;
        margin: 0 0 .65rem;
    }
    .demo-card-meta {
        display: flex;
        align-items: center;
        gap: .35rem;
        font-size: .72rem;
        color: #a8a29e;
        font-weight: 500;
    }

    /* ── Demo lightbox ───────────────────────────────────── */
    #demo-lightbox {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 9999;
        background: rgba(0,0,0,.88);
        backdrop-filter: blur(8px);
        align-items: center;
        justify-content: center;
    }
    #demo-lightbox.open { display: flex; }
    #demo-lightbox-inner {
        position: relative;
        width: min(92vw, 960px);
        border-radius: 18px;
        overflow: hidden;
        background: #000;
        box-shadow: 0 40px 100px rgba(0,0,0,.7);
    }
    #demo-lightbox-inner img,
    #demo-lightbox-inner video {
        width: 100%;
        display: block;
        max-height: 82vh;
        object-fit: contain;
    }
    #demo-lightbox-close {
        position: absolute;
        top: 12px; right: 12px;
        width: 34px; height: 34px;
        border-radius: 50%;
        background: rgba(255,255,255,.12);
        border: none;
        cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        transition: background .2s;
        color: #fff;
        z-index: 2;
    }
    #demo-lightbox-close:hover { background: rgba(255,255,255,.25); }
    .demo-lb-nav {
        position: absolute;
        top: 50%; transform: translateY(-50%);
        width: 42px; height: 42px;
        border-radius: 50%;
        background: rgba(255,255,255,.12);
        border: 1.5px solid rgba(255,255,255,.18);
        cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        transition: background .2s;
        color: #fff;
    }
    .demo-lb-nav:hover { background: rgba(255,255,255,.24); }
    #demo-lb-prev { left: -60px; }
    #demo-lb-next { right: -60px; }
    @media(max-width:1100px){
        #demo-lb-prev { left: 10px; }
        #demo-lb-next { right: 10px; }
    }

    @media(max-width:900px){
        .feat-grid{grid-template-columns:1fr!important}
        .stats-grid{grid-template-columns:repeat(2,1fr)!important}
        .steps-row{flex-direction:column!important;gap:2rem!important}
        .step-line{display:none!important}
        .demo-grid:not(.is-carousel){grid-template-columns:1fr!important}
        .demo-grid.is-carousel .demo-card{flex:0 0 280px!important}
        .mcp-cards{grid-template-columns:1fr!important}
    }
    @media(max-width:640px){
        .stats-grid{grid-template-columns:1fr!important}
    }
</style>
@endpush

@section('content')

{{-- Orbs --}}
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

{{-- ── HERO ──────────────────────────────────────────────────── --}}
<section style="position:relative;z-index:1;padding:0 2rem 4rem">
<div style="max-width:780px;margin:0 auto">
    <div class="hero-content reveal">

        {{-- Badge row --}}
        <div style="display:flex;align-items:center;justify-content:center;gap:.5rem;flex-wrap:wrap">
            <div class="rec-indicator">
                <span class="rec-circle"></span>
                Screen Record
            </div>
            <div style="display:inline-flex;align-items:center;gap:.4rem;font-size:.72rem;font-weight:700;color:#57534e;letter-spacing:.04em;background:rgba(0,0,0,.04);border:1px solid rgba(0,0,0,.08);border-radius:100px;padding:.3rem .9rem">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                Link Sharing
            </div>
            <div style="display:inline-flex;align-items:center;gap:.4rem;font-size:.72rem;font-weight:700;color:#57534e;letter-spacing:.04em;background:rgba(0,0,0,.04);border:1px solid rgba(0,0,0,.08);border-radius:100px;padding:.3rem .9rem">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="10" y1="14" x2="10" y2="14" stroke-linecap="round"/><line x1="14" y1="14" x2="14" y2="14" stroke-linecap="round"/></svg>
                No Meeting Needed
            </div>
        </div>

        <h1 style="font-size:clamp(2.6rem,5vw,4.2rem);font-weight:800;letter-spacing:-.04em;line-height:1.1;color:#1c1917;margin:0">
            Record it, Share it.<br>
            <em style="font-style:italic;color:#f97316">Move on.</em>
        </h1>

        <p style="font-size:1.05rem;line-height:1.75;color:#78716c;max-width:520px;margin:0">
            Skip the scheduling. Record your screen, get a shareable link instantly, and let your team watch on their own time.
        </p>

        <div style="display:flex;align-items:center;justify-content:center;gap:.875rem;flex-wrap:wrap">
            <a href="{{ config('app.frontend_url', config('app.url')) }}/login" class="btn-p" style="font-size:.95rem;padding:.9rem 1.75rem">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3" fill="currentColor" stroke="none"/></svg>
                Record Now
            </a>
            <a href="https://chromewebstore.google.com/detail/openkap/nnchnlkilgfemhpcohmgdpcmkjedjkfm" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;gap:.5rem;text-decoration:none;color:#44403c;font-size:.9rem;font-weight:500;padding:.85rem 1.4rem;border-radius:10px;border:1px solid rgba(0,0,0,.1);background:white;transition:border-color .2s" onmouseover="this.style.borderColor='rgba(0,0,0,.2)'" onmouseout="this.style.borderColor='rgba(0,0,0,.1)'">
                <img src="/logo.png" alt="" style="width:16px;height:16px;border-radius:4px">
                Add Chrome Extension
            </a>
        </div>

        <div style="display:flex;align-items:center;gap:.75rem">
            <div style="display:flex">
                @foreach([['AK','#E05C2A'],['MR','#2A7EE0'],['SL','#29A06A'],['JP','#8B5CF6']] as $i => $av)
                <div style="width:26px;height:26px;border-radius:50%;border:2px solid white;background:{{ $av[1] }};display:flex;align-items:center;justify-content:center;font-size:.55rem;font-weight:700;color:white;{{ $i > 0 ? 'margin-left:-7px' : '' }}">{{ $av[0] }}</div>
                @endforeach
            </div>
            <span style="font-size:.8rem;color:#78716c">
                Joined by <strong style="color:#1c1917;font-weight:600">{{ number_format($userCount) }}</strong> {{ Str::plural('user', $userCount) }}
            </span>
        </div>

    </div>

</div>
</section>

{{-- ── DEMO CARDS ─────────────────────────────────────────────── --}}
<section style="position:relative;z-index:1;padding:0 2rem 4rem">
<div class="demo-carousel-wrap reveal d2">
        <div class="demo-carousel-header">
            <div class="demo-carousel-title">See it in <span>action</span></div>
            <div class="demo-carousel-nav">
                <button id="demo-scroll-prev" class="demo-nav-btn" aria-label="Previous" disabled>
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
                </button>
                <button id="demo-scroll-next" class="demo-nav-btn" aria-label="Next">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                </button>
            </div>
        </div>

        <div class="demo-grid" id="demo-grid">

            <div class="demo-card" data-demo-index="0">
                <div class="demo-media-wrap">
                    <img src="/demo/how-to-record-screen.gif" alt="How to record your screen">
                    <div class="demo-overlay" onclick="openDemoLightbox(0)">
                        <div class="demo-overlay-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1c1917" stroke-width="2.2"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
                        </div>
                    </div>
                </div>
                <div class="demo-card-body">
                    <p class="demo-card-title">Record your screen</p>
                    <p class="demo-card-desc">Click the extension, choose your area, start in seconds.</p>
                    <div class="demo-card-meta">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        OpenKap Demo
                    </div>
                </div>
            </div>

            <div class="demo-card" data-demo-index="1">
                <div class="demo-media-wrap">
                    <img src="/demo/copy-share-link.gif" alt="Copy and share link">
                    <div class="demo-overlay" onclick="openDemoLightbox(1)">
                        <div class="demo-overlay-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1c1917" stroke-width="2.2"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
                        </div>
                    </div>
                </div>
                <div class="demo-card-body">
                    <p class="demo-card-title">Copy &amp; share instantly</p>
                    <p class="demo-card-desc">Link ready the moment you stop. Paste it in Slack, Notion, anywhere.</p>
                    <div class="demo-card-meta">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        OpenKap Demo
                    </div>
                </div>
            </div>

            <div class="demo-card" data-demo-index="2">
                <div class="demo-media-wrap">
                    <video src="/demo/screenshot-editor-demo.mp4" autoplay loop muted playsinline></video>
                    <div class="demo-overlay" onclick="openDemoLightbox(2)">
                        <div class="demo-overlay-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1c1917" stroke-width="2.2"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
                        </div>
                    </div>
                </div>
                <div class="demo-card-body">
                    <p class="demo-card-title">Edit screenshots instantly</p>
                    <p class="demo-card-desc">Annotate, highlight, and crop screenshots before sharing.</p>
                    <div class="demo-card-meta">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        OpenKap Demo
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
</section>

{{-- ── Demo Lightbox ─────────────────────────────────────────── --}}
<div id="demo-lightbox" role="dialog" aria-modal="true">
    <button id="demo-lb-prev" class="demo-lb-nav" aria-label="Previous" onclick="moveDemoLightbox(-1)">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
    </button>
    <div id="demo-lightbox-inner">
        <button id="demo-lightbox-close" aria-label="Close" onclick="closeDemoLightbox()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <div id="demo-lb-media"></div>
    </div>
    <button id="demo-lb-next" class="demo-lb-nav" aria-label="Next" onclick="moveDemoLightbox(1)">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
    </button>
</div>

<script>
(function () {
    // ── Carousel mode: only when >5 cards ───────────────
    const grid     = document.getElementById('demo-grid');
    const wrap     = grid ? grid.closest('.demo-carousel-wrap') : null;
    const btnPrevS = document.getElementById('demo-scroll-prev');
    const btnNextS = document.getElementById('demo-scroll-next');

    function updateScrollBtns() {
        if (!grid) return;
        btnPrevS.disabled = grid.scrollLeft <= 4;
        btnNextS.disabled = grid.scrollLeft + grid.clientWidth >= grid.scrollWidth - 4;
    }

    if (grid) {
        const count = grid.querySelectorAll('.demo-card').length;
        if (count > 5) {
            grid.classList.add('is-carousel');
            wrap && wrap.classList.add('is-carousel-mode');
            const cardWidth = () => {
                const c = grid.querySelector('.demo-card');
                return c ? c.offsetWidth + 20 : 385;
            };
            btnPrevS.addEventListener('click', () => { grid.scrollBy({ left: -cardWidth(), behavior: 'smooth' }); });
            btnNextS.addEventListener('click', () => { grid.scrollBy({ left:  cardWidth(), behavior: 'smooth' }); });
            grid.addEventListener('scroll', updateScrollBtns, { passive: true });
            updateScrollBtns();
        }
    }

    // Build media list from cards — order matters, add more cards and they just work
    const cards = Array.from(document.querySelectorAll('.demo-card'));
    const items = cards.map(card => {
        const img   = card.querySelector('.demo-media-wrap img');
        const video = card.querySelector('.demo-media-wrap video');
        if (video) return { type: 'video', src: video.getAttribute('src') };
        return { type: 'img', src: img.getAttribute('src'), alt: img.getAttribute('alt') || '' };
    });

    let current = 0;
    const lb      = document.getElementById('demo-lightbox');
    const media   = document.getElementById('demo-lb-media');
    const btnPrev = document.getElementById('demo-lb-prev');
    const btnNext = document.getElementById('demo-lb-next');

    function render(idx) {
        const item = items[idx];
        if (item.type === 'video') {
            media.innerHTML = `<video src="${item.src}" autoplay loop muted playsinline style="width:100%;display:block;max-height:80vh;object-fit:contain;background:#000"></video>`;
        } else {
            media.innerHTML = `<img src="${item.src}" alt="${item.alt}" style="width:100%;display:block;max-height:80vh;object-fit:contain;background:#000">`;
        }
        btnPrev.style.visibility = idx === 0 ? 'hidden' : 'visible';
        btnNext.style.visibility = idx === items.length - 1 ? 'hidden' : 'visible';
    }

    window.openDemoLightbox = function (idx) {
        current = idx;
        render(current);
        lb.classList.add('open');
        document.body.style.overflow = 'hidden';
    };

    window.closeDemoLightbox = function () {
        lb.classList.remove('open');
        media.innerHTML = '';
        document.body.style.overflow = '';
    };

    window.moveDemoLightbox = function (dir) {
        const next = current + dir;
        if (next < 0 || next >= items.length) return;
        current = next;
        render(current);
    };

    // Close on backdrop click
    lb.addEventListener('click', function (e) {
        if (e.target === lb) closeDemoLightbox();
    });

    // Keyboard nav
    document.addEventListener('keydown', function (e) {
        if (!lb.classList.contains('open')) return;
        if (e.key === 'Escape') closeDemoLightbox();
        if (e.key === 'ArrowLeft')  moveDemoLightbox(-1);
        if (e.key === 'ArrowRight') moveDemoLightbox(1);
    });
}());
</script>

{{-- ── LOGOS BAR ──────────────────────────────────────────────── --}}
<div style="position:relative;z-index:1;text-align:center;padding:2.5rem 1.5rem;border-top:1px solid rgba(0,0,0,.06);border-bottom:1px solid rgba(0,0,0,.06);background:rgba(255,255,255,.5);backdrop-filter:blur(8px)">
    <p style="font-size:.72rem;color:#a8a29e;font-weight:600;text-transform:uppercase;letter-spacing:.1em;margin-bottom:1.1rem">Trusted by developers, designers &amp; PMs at</p>
    <div style="display:flex;justify-content:center;flex-wrap:wrap;gap:.65rem">
        @foreach(['Notion','Linear','Vercel','Stripe','Figma','Railway','Supabase'] as $co)
        <span style="background:rgba(255,255,255,.8);border:1px solid rgba(0,0,0,.08);border-radius:100px;padding:.3rem 1rem;font-size:.8rem;font-weight:600;color:#57534e">{{ $co }}</span>
        @endforeach
    </div>
</div>


{{-- ── HOW IT WORKS ───────────────────────────────────────────── --}}
<section id="how-it-works" style="position:relative;z-index:1;background:rgba(255,255,255,.5);backdrop-filter:blur(12px);border-top:1px solid rgba(0,0,0,.06);border-bottom:1px solid rgba(0,0,0,.06);padding:6rem 2rem">
    <div style="max-width:1000px;margin:0 auto">
        <div style="text-align:center;max-width:600px;margin:0 auto 3.5rem">
            <div class="tag">How it works</div>
            <h2 style="font-size:clamp(1.9rem,3.5vw,2.75rem);font-weight:800;letter-spacing:-.03em;line-height:1.15;color:#1c1917">From idea to shared video in <span class="grad">60 seconds</span></h2>
        </div>
        <div class="steps-row" style="display:flex;justify-content:center;position:relative">
            @php
            $steps=[
                ['🧩','01','Install the Extension','Add OpenKap to Chrome in seconds. It lives quietly in your toolbar, ready when you need it.'],
                ['⏺️','02','Hit Record','Click record, choose your capture area, and start talking. Recording starts instantly — no waiting.'],
                ['📤','03','Share Your Link','When you stop recording, your video is ready. Copy the link and paste it anywhere — Slack, Notion, Linear, email.'],
            ];
            @endphp
            @foreach($steps as $s)
            <div style="flex:1;display:flex;flex-direction:column;align-items:center;text-align:center;position:relative">
                <div style="font-size:.68rem;font-weight:800;letter-spacing:.1em;color:#f97316;margin-bottom:.9rem">{{ $s[1] }}</div>
                @if(!$loop->last)
                <div class="step-line" style="position:absolute;top:2.7rem;left:55%;right:-45%;height:1px;background:linear-gradient(to right,rgba(249,115,22,.3),rgba(249,115,22,.1))"></div>
                @endif
                <div style="padding:0 1.8rem;display:flex;flex-direction:column;align-items:center">
                    <div style="width:56px;height:56px;border-radius:16px;background:rgba(255,255,255,.92);backdrop-filter:blur(12px);border:1px solid rgba(249,115,22,.15);box-shadow:0 4px 16px rgba(249,115,22,.1);display:flex;align-items:center;justify-content:center;font-size:1.5rem;margin-bottom:1.2rem">{{ $s[0] }}</div>
                    <h3 style="font-size:.97rem;font-weight:700;color:#1c1917;margin-bottom:.45rem">{{ $s[2] }}</h3>
                    <p style="font-size:.845rem;color:#78716c;line-height:1.65">{{ $s[3] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── AI INTEGRATION / MCP ─────────────────────────────────── --}}
<section id="ai-integration" style="position:relative;z-index:1;max-width:960px;margin:0 auto;padding:5rem 2rem">

    {{-- Heading --}}
    <div class="reveal" style="text-align:center;margin-bottom:3rem">
        <div class="tag">AI Integration</div>
        <h2 style="font-size:clamp(1.9rem,3.5vw,2.75rem);font-weight:800;letter-spacing:-.03em;line-height:1.15;color:#1c1917;margin-bottom:.6rem">
            Talk to your videos with <span class="grad">Claude AI</span>
        </h2>
        <p style="font-size:.95rem;color:#78716c;line-height:1.7;max-width:560px;margin:0 auto">
            Connect OpenKap to Claude Code or any MCP-compatible tool. Ask questions about any shared video — transcriptions, summaries, and comments.
        </p>
    </div>

    {{-- Terminal demo --}}
    <div class="reveal d1" style="background:#1c1917;border-radius:16px;overflow:hidden;box-shadow:0 8px 40px rgba(0,0,0,.15);margin-bottom:2rem">
        {{-- Title bar --}}
        <div style="display:flex;align-items:center;gap:6px;padding:.75rem 1rem;background:rgba(255,255,255,.06);border-bottom:1px solid rgba(255,255,255,.06)">
            <span style="width:10px;height:10px;border-radius:50%;background:#ef4444"></span>
            <span style="width:10px;height:10px;border-radius:50%;background:#eab308"></span>
            <span style="width:10px;height:10px;border-radius:50%;background:#22c55e"></span>
            <span style="margin-left:.75rem;font-size:.75rem;color:rgba(255,255,255,.4);font-weight:500">Claude Code</span>
        </div>
        {{-- Lines --}}
        <div style="padding:1.5rem;display:flex;flex-direction:column;gap:1rem">
            {{-- User --}}
            <div style="display:flex;gap:.75rem;align-items:flex-start;font-size:.85rem;line-height:1.6">
                <span style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#f97316;background:rgba(249,115,22,.12);padding:.15rem .5rem;border-radius:4px;flex-shrink:0;margin-top:.1rem">you</span>
                <span style="color:rgba(255,255,255,.85)">What bugs were reported in this video? openkap.com/share/video/a8xK2m</span>
            </div>
            {{-- MCP tool call --}}
            <div style="display:flex;gap:.75rem;align-items:flex-start;font-size:.85rem;line-height:1.6">
                <span style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#a78bfa;background:rgba(167,139,250,.12);padding:.15rem .5rem;border-radius:4px;flex-shrink:0;margin-top:.1rem">mcp</span>
                <span style="color:rgba(255,255,255,.4);font-family:ui-monospace,'SF Mono',monospace;font-size:.8rem">get_video_transcription("a8xK2m")</span>
            </div>
            {{-- AI response --}}
            <div style="display:flex;gap:.75rem;align-items:flex-start;font-size:.85rem;line-height:1.6">
                <span style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#34d399;background:rgba(52,211,153,.12);padding:.15rem .5rem;border-radius:4px;flex-shrink:0;margin-top:.1rem">claude</span>
                <span style="color:rgba(255,255,255,.85)">The video reports <strong style="color:#fff;font-weight:600">2 bugs</strong>: (1) Login button unresponsive on staging at <span style="color:#fbbf24;font-weight:600">0:45</span>, and (2) session token expires too early at <span style="color:#fbbf24;font-weight:600">2:12</span>. A teammate commented confirming both issues.</span>
            </div>
        </div>
    </div>

    {{-- Use-case cards --}}
    <div class="mcp-cards" style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:2.5rem">
        <div class="glass reveal d1 feat-card" style="border-radius:16px;padding:1.5rem;box-shadow:0 2px 8px rgba(0,0,0,.04);cursor:default">
            <div style="width:40px;height:40px;border-radius:10px;background:rgba(249,115,22,.08);color:#f97316;display:flex;align-items:center;justify-content:center;margin-bottom:1rem">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
            </div>
            <div style="font-size:.9rem;font-weight:700;color:#1c1917;margin-bottom:.35rem">Ask about transcriptions</div>
            <div style="font-size:.8rem;color:#78716c;font-style:italic;line-height:1.5">"What was discussed at the 3-minute mark?"</div>
        </div>
        <div class="glass reveal d2 feat-card" style="border-radius:16px;padding:1.5rem;box-shadow:0 2px 8px rgba(0,0,0,.04);cursor:default">
            <div style="width:40px;height:40px;border-radius:10px;background:rgba(249,115,22,.08);color:#f97316;display:flex;align-items:center;justify-content:center;margin-bottom:1rem">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            </div>
            <div style="font-size:.9rem;font-weight:700;color:#1c1917;margin-bottom:.35rem">Get summaries</div>
            <div style="font-size:.8rem;color:#78716c;font-style:italic;line-height:1.5">"Summarize this product demo for me."</div>
        </div>
        <div class="glass reveal d3 feat-card" style="border-radius:16px;padding:1.5rem;box-shadow:0 2px 8px rgba(0,0,0,.04);cursor:default">
            <div style="width:40px;height:40px;border-radius:10px;background:rgba(249,115,22,.08);color:#f97316;display:flex;align-items:center;justify-content:center;margin-bottom:1rem">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
            </div>
            <div style="font-size:.9rem;font-weight:700;color:#1c1917;margin-bottom:.35rem">Read team comments</div>
            <div style="font-size:.8rem;color:#78716c;font-style:italic;line-height:1.5">"What feedback did the team leave?"</div>
        </div>
    </div>

    {{-- Install command --}}
    <div class="reveal d3" style="text-align:center">
        <p style="font-size:.8rem;color:#78716c;margin-bottom:.5rem;font-weight:500">Add to Claude Code in one command:</p>
        <div style="display:inline-block;background:#1c1917;border-radius:10px;padding:.65rem 1.25rem">
            <code style="font-family:ui-monospace,'SF Mono',monospace;font-size:.8rem;color:rgba(255,255,255,.8);letter-spacing:-.01em">claude mcp add openkap -- npx tsx mcp-server/server.ts</code>
        </div>
    </div>
</section>

{{-- ── STATS ──────────────────────────────────────────────────── --}}
<section style="position:relative;z-index:1;max-width:960px;margin:0 auto;padding:5rem 2rem">
    <div class="stats-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:1.2rem">
        @foreach([['< 3s','Time to first recording'],['100%','Uptime guaranteed'],['∞','Video storage'],['0','Setup required']] as $st)
        <div class="glass reveal" style="border-radius:20px;padding:1.9rem;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,.04)">
            <div class="grad" style="font-size:2.4rem;font-weight:800;letter-spacing:-.04em;margin-bottom:.35rem">{{ $st[0] }}</div>
            <div style="font-size:.8rem;color:#78716c;font-weight:500">{{ $st[1] }}</div>
        </div>
        @endforeach
    </div>
</section>

{{-- ── PRICING ─────────────────────────────────────────────────── --}}
<section id="pricing" style="position:relative;z-index:1;padding:5rem 2rem 6rem">
    <div style="max-width:860px;margin:0 auto">

        {{-- Heading --}}
        <div class="reveal" style="text-align:center;margin-bottom:3rem">
            <div class="tag">Pricing</div>
            <h2 style="font-size:clamp(1.9rem,3.5vw,2.75rem);font-weight:800;letter-spacing:-.03em;line-height:1.15;color:#1c1917;margin-bottom:.6rem">
                Simple, honest pricing
            </h2>
            <p style="font-size:.95rem;color:#78716c">Start free. Upgrade when you need more.</p>

            {{-- Monthly / Yearly toggle --}}
            <div style="display:inline-flex;align-items:center;gap:0;background:#f0eeec;border-radius:100px;padding:4px;margin-top:1.5rem" id="billing-toggle">
                <button onclick="setBilling('monthly')" id="btn-monthly"
                    style="font-size:.8rem;font-weight:600;padding:.4rem 1.1rem;border-radius:100px;border:none;cursor:pointer;transition:all .2s;background:white;color:#1c1917;box-shadow:0 1px 4px rgba(0,0,0,.1)">
                    Monthly
                </button>
                <button onclick="setBilling('yearly')" id="btn-yearly"
                    style="font-size:.8rem;font-weight:600;padding:.4rem 1.1rem;border-radius:100px;border:none;cursor:pointer;transition:all .2s;background:transparent;color:#78716c;box-shadow:none">
                    Yearly <span style="font-size:.68rem;color:#22c55e;font-weight:700">−17%</span>
                </button>
            </div>
        </div>

        {{-- Cards --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:1.5rem" class="reveal d1">

            {{-- FREE --}}
            <div class="glass" style="border-radius:24px;padding:2.25rem 2rem;box-shadow:0 2px 12px rgba(0,0,0,.06)">
                <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#78716c;margin-bottom:1rem">Free</div>
                <div style="display:flex;align-items:baseline;gap:.25rem;margin-bottom:.4rem">
                    <span style="font-size:2.8rem;font-weight:800;letter-spacing:-.04em;color:#1c1917">$0</span>
                    <span style="font-size:.85rem;color:#a8a29e">/mo</span>
                </div>
                <p style="font-size:.82rem;color:#78716c;margin-bottom:1.75rem;line-height:1.5">Everything you need to get started.</p>
                <a href="{{ config('app.frontend_url', config('app.url')) }}/login"
                    style="display:flex;align-items:center;justify-content:center;gap:.4rem;width:100%;padding:.75rem;border-radius:10px;border:1.5px solid rgba(0,0,0,.12);background:white;font-size:.88rem;font-weight:600;color:#1c1917;text-decoration:none;transition:border-color .2s"
                    onmouseover="this.style.borderColor='rgba(0,0,0,.25)'" onmouseout="this.style.borderColor='rgba(0,0,0,.12)'">
                    Get started free
                </a>
                <ul style="list-style:none;padding:0;margin:1.75rem 0 0;display:flex;flex-direction:column;gap:.75rem">
                    @foreach(['5 recordings','Up to 5 min per recording','Shareable links','1 workspace','Chrome extension'] as $f)
                    <li style="display:flex;align-items:center;gap:.6rem;font-size:.85rem;color:#57534e">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#a8a29e" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        {{ $f }}
                    </li>
                    @endforeach
                </ul>
            </div>

            {{-- PRO --}}
            <div style="border-radius:24px;padding:2.25rem 2rem;background:#1c1917;position:relative;overflow:hidden;box-shadow:0 8px 40px rgba(0,0,0,.18)">
                <div style="position:absolute;width:300px;height:300px;background:radial-gradient(circle,rgba(249,115,22,.18) 0%,transparent 70%);border-radius:50%;top:-80px;right:-60px;pointer-events:none"></div>
                <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:1rem">
                    <span style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:rgba(255,255,255,.5)">Pro</span>
                    <span style="font-size:.65rem;font-weight:700;background:#f97316;color:white;border-radius:100px;padding:.15rem .55rem;letter-spacing:.04em">POPULAR</span>
                </div>
                <div style="display:flex;align-items:baseline;gap:.25rem;margin-bottom:.4rem;position:relative">
                    <span style="font-size:2.8rem;font-weight:800;letter-spacing:-.04em;color:white" id="price-display">$8</span>
                    <span style="font-size:.85rem;color:rgba(255,255,255,.4)" id="period-display">/mo</span>
                </div>
                <p style="font-size:.82rem;color:rgba(255,255,255,.45);margin-bottom:1.75rem;line-height:1.5;position:relative" id="yearly-note" style="display:none"></p>
                <a href="{{ config('app.frontend_url', config('app.url')) }}/login" class="btn-p"
                    style="display:flex;align-items:center;justify-content:center;gap:.4rem;width:100%;padding:.75rem;border-radius:10px;font-size:.88rem;box-shadow:none;position:relative">
                    Get started with Pro
                </a>
                <ul style="list-style:none;padding:0;margin:1.75rem 0 0;display:flex;flex-direction:column;gap:.75rem;position:relative">
                    @foreach(['Unlimited recordings','Unlimited recording length','Shareable links','Unlimited workspaces','Team collaboration','Priority support','Early access to new features'] as $f)
                    <li style="display:flex;align-items:center;gap:.6rem;font-size:.85rem;color:rgba(255,255,255,.75)">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        {{ $f }}
                    </li>
                    @endforeach
                </ul>
            </div>

        </div>

        <p class="reveal d2" style="text-align:center;font-size:.8rem;color:#a8a29e;margin-top:1.5rem">
            No credit card required for free plan &nbsp;·&nbsp; Cancel Pro anytime
        </p>
    </div>
</section>

@endsection

@push('scripts')
<script>
    // Scroll reveal
    const io = new IntersectionObserver(es => es.forEach(e => { if (e.isIntersecting) e.target.classList.add('in'); }), { threshold: 0.1 });
    document.querySelectorAll('.reveal, .reveal-r').forEach(el => io.observe(el));

    // 3D tilt on preview
    function tilt(e, el) {
        const r = el.getBoundingClientRect();
        const x = ((e.clientX - r.left) / r.width  - 0.5) * 8;
        const y = ((e.clientY - r.top)  / r.height - 0.5) * -8;
        el.style.transform = `perspective(1200px) rotateY(${x}deg) rotateX(${y}deg)`;
    }
    function resetTilt(el) {
        el.style.transform = 'perspective(1200px) rotateY(0) rotateX(0)';
    }

    // Pricing billing toggle
    function setBilling(cycle) {
        const priceEl  = document.getElementById('price-display');
        const periodEl = document.getElementById('period-display');
        const noteEl   = document.getElementById('yearly-note');
        const btnM     = document.getElementById('btn-monthly');
        const btnY     = document.getElementById('btn-yearly');
        if (!priceEl) return;
        const activeStyle   = 'background:white;color:#1c1917;box-shadow:0 1px 4px rgba(0,0,0,.1)';
        const inactiveStyle = 'background:transparent;color:#78716c;box-shadow:none';
        if (cycle === 'yearly') {
            priceEl.textContent  = '$80';
            periodEl.textContent = '/yr';
            noteEl.textContent   = 'Billed annually — saves you $16/yr';
            noteEl.style.display = 'block';
            btnY.style.cssText  += ';' + activeStyle;
            btnM.style.cssText  += ';' + inactiveStyle;
        } else {
            priceEl.textContent  = '$8';
            periodEl.textContent = '/mo';
            noteEl.style.display = 'none';
            btnM.style.cssText  += ';' + activeStyle;
            btnY.style.cssText  += ';' + inactiveStyle;
        }
    }

</script>
@endpush
