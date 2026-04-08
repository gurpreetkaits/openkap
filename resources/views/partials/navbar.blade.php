@php $light = $lightMode ?? false; @endphp

<style>
    #main-nav {
        position: fixed;
        top: 16px;
        left: 50%;
        transform: translateX(-50%);
        width: calc(100% - 2.5rem);
        max-width: 980px;
        z-index: 50;
        border-radius: 9999px;
        border: 1px solid {{ $light ? 'rgba(0,0,0,0.07)' : 'rgba(255,255,255,0.06)' }};
        transition: background .3s ease, border-color .3s ease, box-shadow .3s ease, top .3s ease;
        background: transparent;
    }
    #main-nav.scrolled {
        top: 10px;
        background: {{ $light ? 'rgba(255,255,255,0.88)' : 'rgba(12,12,12,0.88)' }};
        border-color: {{ $light ? 'rgba(0,0,0,0.08)' : 'rgba(255,255,255,0.07)' }};
        box-shadow: 0 4px 24px rgba(0,0,0,{{ $light ? '.08' : '.3' }});
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
    }
    /* Dropdown */
    .nav-dropdown { position: relative; }
    .nav-dropdown-menu {
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        min-width: 160px;
        border-radius: 14px;
        padding: 6px;
        margin-top: 12px;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity .18s ease, transform .18s ease;
        transform: translateX(-50%) translateY(-6px);
        background: {{ $light ? 'white' : '#111' }};
        border: 1px solid {{ $light ? 'rgba(0,0,0,0.08)' : 'rgba(255,255,255,0.08)' }};
        box-shadow: 0 8px 32px rgba(0,0,0,{{ $light ? '.1' : '.4' }});
    }
    /* Invisible bridge to prevent hover gap */
    .nav-dropdown-menu::before {
        content: '';
        position: absolute;
        top: -12px;
        left: 0;
        right: 0;
        height: 12px;
    }
    .nav-dropdown:hover .nav-dropdown-menu,
    .nav-dropdown:focus-within .nav-dropdown-menu {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
        transform: translateX(-50%) translateY(0);
    }
    .nav-dropdown-item {
        display: block;
        padding: 8px 14px;
        border-radius: 9px;
        font-size: .82rem;
        font-weight: 500;
        text-decoration: none;
        white-space: nowrap;
        transition: background .15s;
        color: {{ $light ? '#44403c' : 'rgba(255,255,255,.7)' }};
    }
    .nav-dropdown-item:hover {
        background: {{ $light ? '#f5f5f4' : 'rgba(255,255,255,.07)' }};
        color: {{ $light ? '#1c1917' : 'white' }};
    }
    /* Mobile menu card */
    #mobile-nav-menu {
        position: fixed;
        top: 72px;
        left: 50%;
        transform: translateX(-50%);
        width: calc(100% - 2.5rem);
        max-width: 980px;
        z-index: 49;
        border-radius: 20px;
        background: {{ $light ? 'rgba(255,255,255,0.96)' : 'rgba(12,12,12,0.96)' }};
        border: 1px solid {{ $light ? 'rgba(0,0,0,0.08)' : 'rgba(255,255,255,0.07)' }};
        box-shadow: 0 8px 40px rgba(0,0,0,.15);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        display: none;
    }
    #mobile-nav-menu.open { display: block; }
</style>

<nav id="main-nav">
    <div style="padding: 0 1.25rem; height: 52px; display: flex; align-items: center; justify-content: space-between;">

        {{-- Logo --}}
        <a href="/" style="display:flex;align-items:center;gap:.6rem;text-decoration:none;flex-shrink:0">
            <img src="/logo.png" alt="OpenKap" style="width:28px;height:28px;border-radius:8px">
            <span style="font-size:.95rem;font-weight:700;letter-spacing:-.02em;color:{{ $light ? '#1c1917' : 'white' }}">OpenKap</span>
        </a>

        {{-- Desktop links --}}
        <div class="hidden md:flex" style="align-items:center;gap:.25rem">
            <a href="/#how-it-works" style="font-size:.84rem;font-weight:500;padding:.4rem .85rem;border-radius:100px;text-decoration:none;color:{{ $light ? '#57534e' : 'rgba(255,255,255,.65)' }};transition:color .2s,background .2s"
                onmouseover="this.style.color='{{ $light ? '#1c1917' : 'white' }}';this.style.background='{{ $light ? 'rgba(0,0,0,.05)' : 'rgba(255,255,255,.07)' }}'"
                onmouseout="this.style.color='{{ $light ? '#57534e' : 'rgba(255,255,255,.65)' }}';this.style.background='transparent'">
                How it works
            </a>
            <a href="/#pricing" style="font-size:.84rem;font-weight:500;padding:.4rem .85rem;border-radius:100px;text-decoration:none;color:{{ $light ? '#57534e' : 'rgba(255,255,255,.65)' }};transition:color .2s,background .2s"
                onmouseover="this.style.color='{{ $light ? '#1c1917' : 'white' }}';this.style.background='{{ $light ? 'rgba(0,0,0,.05)' : 'rgba(255,255,255,.07)' }}'"
                onmouseout="this.style.color='{{ $light ? '#57534e' : 'rgba(255,255,255,.65)' }}';this.style.background='transparent'">
                Pricing
            </a>

            {{-- Alternatives dropdown --}}
            <div class="nav-dropdown">
                <button style="font-size:.84rem;font-weight:500;padding:.4rem .85rem;border-radius:100px;border:none;background:transparent;cursor:pointer;display:flex;align-items:center;gap:.3rem;color:{{ $light ? '#57534e' : 'rgba(255,255,255,.65)' }};transition:color .2s,background .2s"
                    onmouseover="this.style.color='{{ $light ? '#1c1917' : 'white' }}';this.style.background='{{ $light ? 'rgba(0,0,0,.05)' : 'rgba(255,255,255,.07)' }}'"
                    onmouseout="this.style.color='{{ $light ? '#57534e' : 'rgba(255,255,255,.65)' }}';this.style.background='transparent'">
                    Compare
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="nav-dropdown-menu">
                    <a href="/alternative/loom" class="nav-dropdown-item">vs Loom</a>
                    <a href="/alternative/cap" class="nav-dropdown-item">vs Cap</a>
                </div>
            </div>

            {{-- Resources dropdown --}}
            <div class="nav-dropdown">
                <button style="font-size:.84rem;font-weight:500;padding:.4rem .85rem;border-radius:100px;border:none;background:transparent;cursor:pointer;display:flex;align-items:center;gap:.3rem;color:{{ $light ? '#57534e' : 'rgba(255,255,255,.65)' }};transition:color .2s,background .2s"
                    onmouseover="this.style.color='{{ $light ? '#1c1917' : 'white' }}';this.style.background='{{ $light ? 'rgba(0,0,0,.05)' : 'rgba(255,255,255,.07)' }}'"
                    onmouseout="this.style.color='{{ $light ? '#57534e' : 'rgba(255,255,255,.65)' }}';this.style.background='transparent'">
                    Resources
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="nav-dropdown-menu">
                    <a href="/blog" class="nav-dropdown-item">Blog</a>
                    <a href="/changelog" class="nav-dropdown-item">Changelog</a>
                    <a href="{{ config('app.frontend_url', config('app.url')) }}/feedback" class="nav-dropdown-item">Feedback</a>
                </div>
            </div>
        </div>

        {{-- Right: CTA + hamburger --}}
        <div style="display:flex;align-items:center;gap:.6rem;flex-shrink:0">
            <a href="{{ config('app.frontend_url', config('app.url')) }}/login"
                class="hidden sm:inline-flex"
                style="align-items:center;font-size:.84rem;font-weight:600;padding:.45rem 1.1rem;border-radius:100px;text-decoration:none;transition:all .2s;background:{{ $light ? '#1c1917' : 'white' }};color:{{ $light ? 'white' : '#1c1917' }};"
                onmouseover="this.style.opacity='.85'"
                onmouseout="this.style.opacity='1'">
                Sign in
            </a>

            {{-- Mobile hamburger --}}
            <button id="mobile-menu-btn" class="md:hidden"
                style="width:36px;height:36px;border-radius:100px;border:1px solid {{ $light ? 'rgba(0,0,0,.1)' : 'rgba(255,255,255,.1)' }};background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;color:{{ $light ? '#1c1917' : 'white' }}">
                <svg id="icon-menu" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                <svg id="icon-close" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="display:none"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
    </div>
</nav>

{{-- Mobile menu (floating card below nav) --}}
<div id="mobile-nav-menu">
    <div style="padding:1rem">
        <a href="/#how-it-works" style="display:block;padding:.65rem 1rem;border-radius:10px;font-size:.88rem;font-weight:500;text-decoration:none;color:{{ $light ? '#44403c' : 'rgba(255,255,255,.75)' }};transition:background .15s"
            onmouseover="this.style.background='{{ $light ? '#f5f5f4' : 'rgba(255,255,255,.06)' }}'"
            onmouseout="this.style.background='transparent'">How it works</a>
        <a href="/#pricing" style="display:block;padding:.65rem 1rem;border-radius:10px;font-size:.88rem;font-weight:500;text-decoration:none;color:{{ $light ? '#44403c' : 'rgba(255,255,255,.75)' }};transition:background .15s"
            onmouseover="this.style.background='{{ $light ? '#f5f5f4' : 'rgba(255,255,255,.06)' }}'"
            onmouseout="this.style.background='transparent'">Pricing</a>
        <a href="/blog" style="display:block;padding:.65rem 1rem;border-radius:10px;font-size:.88rem;font-weight:500;text-decoration:none;color:{{ $light ? '#44403c' : 'rgba(255,255,255,.75)' }};transition:background .15s"
            onmouseover="this.style.background='{{ $light ? '#f5f5f4' : 'rgba(255,255,255,.06)' }}'"
            onmouseout="this.style.background='transparent'">Blog</a>
        <a href="/changelog" style="display:block;padding:.65rem 1rem;border-radius:10px;font-size:.88rem;font-weight:500;text-decoration:none;color:{{ $light ? '#44403c' : 'rgba(255,255,255,.75)' }};transition:background .15s"
            onmouseover="this.style.background='{{ $light ? '#f5f5f4' : 'rgba(255,255,255,.06)' }}'"
            onmouseout="this.style.background='transparent'">Changelog</a>
        <a href="/alternative/loom" style="display:block;padding:.65rem 1rem;border-radius:10px;font-size:.88rem;font-weight:500;text-decoration:none;color:{{ $light ? '#44403c' : 'rgba(255,255,255,.75)' }};transition:background .15s"
            onmouseover="this.style.background='{{ $light ? '#f5f5f4' : 'rgba(255,255,255,.06)' }}'"
            onmouseout="this.style.background='transparent'">vs Loom</a>
        <a href="/alternative/cap" style="display:block;padding:.65rem 1rem;border-radius:10px;font-size:.88rem;font-weight:500;text-decoration:none;color:{{ $light ? '#44403c' : 'rgba(255,255,255,.75)' }};transition:background .15s"
            onmouseover="this.style.background='{{ $light ? '#f5f5f4' : 'rgba(255,255,255,.06)' }}'"
            onmouseout="this.style.background='transparent'">vs Cap</a>
        <div style="border-top:1px solid {{ $light ? 'rgba(0,0,0,.07)' : 'rgba(255,255,255,.07)' }};margin-top:.5rem;padding-top:.75rem">
            <a href="{{ config('app.frontend_url', config('app.url')) }}/login"
                style="display:flex;align-items:center;justify-content:center;padding:.75rem;border-radius:10px;font-size:.88rem;font-weight:600;text-decoration:none;background:{{ $light ? '#1c1917' : 'white' }};color:{{ $light ? 'white' : '#1c1917' }}">
                Sign in
            </a>
        </div>
    </div>
</div>

<script>
    // Scroll: add .scrolled class after 20px
    (function() {
        const nav = document.getElementById('main-nav');
        function onScroll() {
            nav.classList.toggle('scrolled', window.scrollY > 20);
        }
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    })();

    // Mobile menu toggle
    document.addEventListener('DOMContentLoaded', function() {
        const btn   = document.getElementById('mobile-menu-btn');
        const menu  = document.getElementById('mobile-nav-menu');
        const iconM = document.getElementById('icon-menu');
        const iconX = document.getElementById('icon-close');
        if (!btn) return;
        btn.addEventListener('click', function() {
            const open = menu.classList.toggle('open');
            iconM.style.display = open ? 'none'  : 'block';
            iconX.style.display = open ? 'block' : 'none';
        });
        menu.querySelectorAll('a').forEach(a => a.addEventListener('click', function() {
            menu.classList.remove('open');
            iconM.style.display = 'block';
            iconX.style.display = 'none';
        }));
    });
</script>
