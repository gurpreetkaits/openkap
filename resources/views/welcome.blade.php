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

    @media(max-width:900px){
        .feat-grid{grid-template-columns:1fr!important}
        .stats-grid{grid-template-columns:repeat(2,1fr)!important}
        .steps-row{flex-direction:column!important;gap:2rem!important}
        .step-line{display:none!important}
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
            <a href="#how-it-works" style="display:inline-flex;align-items:center;gap:.5rem;text-decoration:none;color:#44403c;font-size:.9rem;font-weight:500;padding:.85rem 1.4rem;border-radius:10px;border:1px solid rgba(0,0,0,.1);background:white;transition:border-color .2s" onmouseover="this.style.borderColor='rgba(0,0,0,.2)'" onmouseout="this.style.borderColor='rgba(0,0,0,.1)'">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                See how it works
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
                @if($workspaceCount > 0)
                    across <strong style="color:#1c1917;font-weight:600">{{ number_format($workspaceCount) }}</strong> {{ Str::plural('organization', $workspaceCount) }}
                @endif
            </span>
        </div>

    </div>

    {{-- Demo video placeholder — replace src with your video --}}
    <div class="reveal d2" style="margin-top:3rem;border-radius:20px;overflow:hidden;border:1px solid rgba(0,0,0,.08);box-shadow:0 8px 40px rgba(0,0,0,.1);background:#f5f5f4;aspect-ratio:16/9;display:flex;align-items:center;justify-content:center">
        <div style="text-align:center;color:#a8a29e">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity:.4;margin-bottom:.75rem"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8" fill="currentColor" stroke="none"/></svg>
            <p style="font-size:.85rem;margin:0">Drop your demo video here</p>
        </div>
    </div>
</div>
</section>

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
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem" class="reveal d1">

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
