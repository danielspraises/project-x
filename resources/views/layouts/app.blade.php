<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800|space-grotesk:500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @php
        use App\Models\PlatformSetting;

        $defaultLight = ['primary' => '#5b5ff5', 'secondary' => '#7c3aed'];
        $defaultDark = ['primary' => '#8b8ff7', 'secondary' => '#9b6cff'];

        if (auth()->check() && auth()->user()->institution) {
            $institution = auth()->user()->institution;
            $theme = $institution->theme();
            $defaultLight = ['primary' => $theme['light_primary'], 'secondary' => $theme['light_secondary']];
            $defaultDark = ['primary' => $theme['dark_primary'], 'secondary' => $theme['dark_secondary']];
        } elseif (auth()->check() && auth()->user()->isSuperAdmin()) {
            $platform = PlatformSetting::get('branding', []);
            $defaultLight = ['primary' => $platform['light_primary'] ?? $platform['primary_color'] ?? '#5b5ff5', 'secondary' => $platform['light_secondary'] ?? $platform['secondary_color'] ?? '#7c3aed'];
            $defaultDark = ['primary' => $platform['dark_primary'] ?? $platform['primary_color'] ?? '#8b8ff7', 'secondary' => $platform['dark_secondary'] ?? $platform['secondary_color'] ?? '#9b6cff'];
        }
    @endphp
    <script>
        (() => {
            const saved = localStorage.getItem('projectXTheme');
            const theme = saved === 'dark' || saved === 'light' ? saved : 'light';
            document.documentElement.classList.toggle('theme-dark', theme === 'dark');
            document.documentElement.classList.toggle('theme-light', theme === 'light');
        })();
    </script>
    <style>
        :root {
            --brand-primary: {{ $defaultLight['primary'] }};
            --brand-secondary: {{ $defaultLight['secondary'] }};
            --brand-dark-primary: {{ $defaultDark['primary'] }};
            --brand-dark-secondary: {{ $defaultDark['secondary'] }};
        }
        html { scroll-behavior:smooth; }
        html.theme-dark { --brand-primary: var(--brand-dark-primary); --brand-secondary: var(--brand-dark-secondary); }
        body { font-family:Inter,system-ui,sans-serif; margin:0; background:var(--canvas); color:var(--ink); transition:background .25s ease,color .25s ease; }
        h1,h2,h3,h4,h5,h6,.font-display { font-family:'Space Grotesk',Inter,sans-serif; }
        [x-cloak]{display:none!important}
        .theme-init { color-scheme: light; }
        .theme-dark { color-scheme: dark; }
        .cx-app { min-height:100vh; background:var(--canvas); }
        .cx-noise { position:fixed; inset:0; pointer-events:none; opacity:.025; z-index:0; background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 160 160' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.9' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.55'/%3E%3C/svg%3E"); }
        .cx-orbit { position:fixed; width:560px; height:560px; right:-230px; top:110px; border-radius:50%; border:1px solid var(--line); box-shadow:0 0 100px color-mix(in srgb,var(--brand-primary) 10%,transparent); transform:rotateX(62deg) rotateZ(-15deg); pointer-events:none; z-index:0; }
        .cx-orbit:before,.cx-orbit:after { content:""; position:absolute; inset:13%; border:1px solid var(--line); border-radius:50%; }
        .cx-orbit:after { inset:30%; }
        .cx-topbar { background:var(--topbar); backdrop-filter:blur(24px); border-bottom:1px solid var(--line); box-shadow:0 10px 40px var(--shadow); }
        .cx-content { position:relative; z-index:2; }
        .cx-panel { background:var(--panel); border:1px solid var(--line); box-shadow:0 25px 80px var(--shadow), inset 0 1px var(--highlight); backdrop-filter:blur(22px); }
        .cx-panel-solid { background:var(--panel-solid); border:1px solid var(--line-strong); box-shadow:0 25px 80px var(--shadow), inset 0 1px var(--highlight); }
        .cx-card { position:relative; transform-style:preserve-3d; transition:transform .55s cubic-bezier(.2,.8,.2,1),box-shadow .55s ease,border-color .55s ease; overflow:hidden; }
        .cx-card:before { content:""; position:absolute; inset:0; background:linear-gradient(120deg,var(--card-sheen),transparent 30%,transparent 70%,rgba(255,255,255,.02)); pointer-events:none; }
        .cx-card:hover { box-shadow:0 34px 90px var(--shadow-strong),0 0 0 1px var(--line-strong); }
        .cx-card > * { position:relative; z-index:1; }
        .cx-kicker { font-size:10px; line-height:1.2; letter-spacing:.22em; text-transform:uppercase; font-weight:800; color:var(--muted); }
        .cx-title { font-size:clamp(28px,3.5vw,48px); line-height:1.02; letter-spacing:-.04em; font-weight:700; color:var(--ink); }
        .cx-subtitle { color:var(--muted); font-size:14px; line-height:1.75; }
        .cx-button { border:1px solid var(--line-strong); background:var(--control); color:var(--ink); box-shadow:0 10px 30px var(--shadow),inset 0 1px var(--highlight); transition:.25s ease; }
        .cx-button:hover { transform:translateY(-2px); background:var(--control-hover); border-color:color-mix(in srgb,var(--brand-primary) 30%,var(--line-strong)); }
        .cx-button-primary { background:var(--brand-primary)!important; border-color:var(--brand-primary)!important; color:#fff!important; box-shadow:0 16px 34px color-mix(in srgb,var(--brand-primary) 25%,transparent); }
        .cx-button-primary:hover,.cx-button-primary:focus,.cx-button-primary:active { background:color-mix(in srgb,var(--brand-primary) 88%,#000)!important; border-color:color-mix(in srgb,var(--brand-primary) 88%,#000)!important; color:#fff!important; filter:none!important; }
        .cx-icon-orb { width:52px;height:52px;border-radius:17px;display:grid;place-items:center;background:linear-gradient(145deg,color-mix(in srgb,var(--brand-primary) 78%,white),color-mix(in srgb,var(--brand-secondary) 70%,var(--canvas))); box-shadow:inset 2px 2px 5px rgba(255,255,255,.32),inset -5px -6px 13px rgba(0,0,0,.25),0 18px 38px var(--shadow-strong); transform:translateZ(24px); color:white; font-weight:800; }
        .cx-grid { background-image:linear-gradient(var(--grid-line) 1px,transparent 1px),linear-gradient(90deg,var(--grid-line) 1px,transparent 1px); background-size:44px 44px; }
        .cx-glow { box-shadow:0 0 90px color-mix(in srgb,var(--brand-primary) 14%,transparent); }
        .cx-stage { position:relative; isolation:isolate; }
        .cx-stage:after { content:""; position:absolute; width:380px;height:180px;left:18%;bottom:-80px;background:radial-gradient(ellipse,color-mix(in srgb,var(--brand-primary) 18%,transparent),transparent 70%);filter:blur(24px);z-index:-1; }
        .cx-stat { border-top:1px solid var(--line); padding-top:14px; }
        .cx-input { background:var(--input)!important; color:var(--ink)!important; border:1px solid var(--line-strong)!important; border-radius:14px!important; }
        .cx-input:focus { border-color:color-mix(in srgb,var(--brand-primary) 60%,white)!important; box-shadow:0 0 0 4px color-mix(in srgb,var(--brand-primary) 13%,transparent)!important; }
        .cx-input option { background:var(--option-bg); color:var(--ink); }
        .cx-reveal { opacity:0; transform:translateY(22px) scale(.985); transition:opacity .7s ease,transform .7s cubic-bezier(.2,.8,.2,1); }
        .cx-reveal.visible { opacity:1; transform:none; }
        .cx-bubble { position:fixed; border-radius:50%; pointer-events:none; filter:blur(1px); background:radial-gradient(circle at 32% 25%,rgba(255,255,255,.16),color-mix(in srgb,var(--brand-primary) 9%,transparent) 35%,transparent 72%); z-index:1; animation:cxFloat 13s ease-in-out infinite; }
        .cx-b1{width:180px;height:180px;left:8%;top:22%;animation-delay:-4s}.cx-b2{width:110px;height:110px;right:20%;top:28%;animation-delay:-8s}.cx-b3{width:240px;height:240px;right:-100px;bottom:8%;animation-delay:-6s}
        @keyframes cxFloat{50%{transform:translate3d(0,-26px,30px) rotate(8deg)}}
        .cx-divider{height:1px;background:var(--line)}
        .cx-modal{background:color-mix(in srgb,var(--canvas) 68%,transparent);backdrop-filter:blur(18px);}
        .cx-modal-card{max-height:min(720px,calc(100vh - 32px));overflow:auto;}
        @media(max-width:640px){.cx-title{font-size:31px}.cx-orbit{right:-360px}.cx-panel,.cx-panel-solid{box-shadow:0 18px 50px var(--shadow)}}
        @media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}.cx-card,.cx-reveal,.cx-bubble{transition:none!important;animation:none!important}.cx-reveal{opacity:1;transform:none}}
    </style>
</head>
<body class="antialiased">
<div class="cx-app">
    <div class="cx-noise"></div><div class="cx-orbit"></div>
    <span class="cx-bubble cx-b1"></span><span class="cx-bubble cx-b2"></span><span class="cx-bubble cx-b3"></span>
    <div id="page-loading-bar"></div>
    <div x-data="{ collapsed: localStorage.getItem('sidebarCollapsed') === 'true', mobileOpen:false }">
        <header class="cx-topbar fixed top-0 inset-x-0 h-[72px] z-50 flex items-center justify-between px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-3 min-w-0">
                <button @click="mobileOpen=!mobileOpen" class="cx-button sm:hidden rounded-xl p-2">☰</button>
                <button @click="collapsed=!collapsed;localStorage.setItem('sidebarCollapsed',collapsed)" class="cx-button hidden sm:inline-flex rounded-xl p-2">☰</button>
                <div class="min-w-0">{{ $header ?? '' }}</div>
            </div>
            <div class="flex items-center gap-3 shrink-0">
                <x-notification-bell />
                <span class="hidden md:inline-flex rounded-full border theme-badge px-3 py-1.5 text-[11px] font-bold">{{ Auth::user()->isSuperAdmin() ? 'SUPER ADMIN' : strtoupper(Auth::user()->role->name ?? '') }}</span>
                <button type="button" id="theme-toggle" class="theme-toggle" aria-label="Toggle colour theme" title="Toggle light and dark mode">
                    <span class="theme-toggle-icon" aria-hidden="true">☼</span>
                    <span class="hidden sm:inline theme-toggle-label">Light</span>
                </button>
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger"><button class="flex items-center gap-2"><div class="cx-profile-avatar h-9 w-9 rounded-xl grid place-items-center text-sm font-bold text-white" style="background:var(--brand-primary)">{{ collect(explode(' ',Auth::user()->name))->map(fn($p)=>strtoupper($p[0]??''))->take(2)->implode('') }}</div><span class="hidden lg:block text-sm font-semibold text-slate-300">{{ Auth::user()->name }}</span></button></x-slot>
                    <x-slot name="content"><x-dropdown-link :href="route('profile.edit')">{{ __('Profile') }}</x-dropdown-link><form method="POST" action="{{ route('logout') }}">@csrf<x-dropdown-link :href="route('logout')" onclick="event.preventDefault();this.closest('form').submit();">{{ __('Log Out') }}</x-dropdown-link></form></x-slot>
                </x-dropdown>
            </div>
        </header>
        <div x-show="mobileOpen" x-transition.opacity @click="mobileOpen=false" class="fixed inset-0 bg-black/70 z-40 sm:hidden"></div>
        @include('layouts.sidebar')
        <div class="pt-[72px] min-h-screen transition-all duration-300" :class="collapsed?'sm:pl-[72px]':'sm:pl-64'">
            <main class="cx-content min-h-[calc(100vh-72px)]">{{ $slot }}</main>
        </div>
    </div>
    <x-toast-container />
</div>
<script>
(()=>{
 const root=document.documentElement;
 const button=document.getElementById('theme-toggle');
 const sync=()=>{
   const dark=root.classList.contains('theme-dark');
   root.classList.toggle('theme-light',!dark);
   if(button){
     button.querySelector('.theme-toggle-icon').textContent=dark?'☾':'☼';
     button.querySelector('.theme-toggle-label').textContent=dark?'Dark':'Light';
     button.setAttribute('aria-label',dark?'Switch to light mode':'Switch to dark mode');
   }
 };
 button?.addEventListener('click',()=>{
   const dark=!root.classList.contains('theme-dark');
   root.classList.toggle('theme-dark',dark);
   root.classList.toggle('theme-light',!dark);
   localStorage.setItem('projectXTheme',dark?'dark':'light');
   sync();
 });
 sync();
})();
(()=>{

 const bar=document.getElementById('page-loading-bar');
 const start=()=>{if(!bar)return;bar.style.opacity='1';bar.style.width='72%'};
 document.addEventListener('click',e=>{const a=e.target.closest('a[href]');if(a&&a.origin===location.origin&&!a.target&&!a.hasAttribute('download'))start()});
 document.addEventListener('submit',start);
 const io=new IntersectionObserver(es=>es.forEach(x=>x.isIntersecting&&x.target.classList.add('visible')),{threshold:.08});document.querySelectorAll('.cx-reveal').forEach(x=>io.observe(x));
 document.querySelectorAll('[data-cx-tilt]').forEach(card=>{card.addEventListener('pointermove',e=>{const r=card.getBoundingClientRect(),x=(e.clientX-r.left)/r.width-.5,y=(e.clientY-r.top)/r.height-.5;card.style.transform=`perspective(1200px) rotateX(${(-y*5).toFixed(2)}deg) rotateY(${(x*7).toFixed(2)}deg) translateZ(3px)`});card.addEventListener('pointerleave',()=>card.style.transform='')});
 window.addEventListener('pageshow',()=>{if(bar){bar.style.width='100%';setTimeout(()=>{bar.style.opacity='0';bar.style.width='0'},220)}});
})();
</script>
</body>
</html>
