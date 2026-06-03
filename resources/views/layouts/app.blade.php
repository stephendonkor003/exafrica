@php
    $fallbackSeoSection = [
        'title' => 'Extraordinary African | Agenda 2063',
        'description' => 'Extraordinary Africans celebrates exceptional African changemakers, innovators, leaders, and creators aligned with Agenda 2063 and The Africa We Want.',
        'priority' => '1.0',
    ];
    $fallbackKeywords = [
        'Extraordinary Africans',
        'Agenda 2063',
        'African Union',
        'The Africa We Want',
        'African awards',
        'African innovation',
        'African changemakers',
        'African creatives',
    ];
    $fallbackLogos = [
        'share_card' => 'images/seo/extraordinary-africans-share-card.png',
        'agenda_2063' => 'images/seo/agenda-2063-logo.png',
        'african_union' => 'images/seo/african-union-logo.png',
    ];

    $defaultSection = config('seo.default_section', 'home');
    $seoSections = config('seo.sections', ['home' => $fallbackSeoSection]);
    $publicSections = is_array($seoSections) && count($seoSections) > 0
        ? $seoSections
        : ['home' => $fallbackSeoSection];
    $defaultSeoSection = $publicSections[$defaultSection] ?? $fallbackSeoSection;
    $configuredKeywords = config('seo.keywords', $fallbackKeywords);
    $configuredLogos = config('seo.logos', []);
    $logos = array_merge($fallbackLogos, is_array($configuredLogos) ? $configuredLogos : []);
    $seoDefaults = [
        'section' => $defaultSection,
        'title' => $defaultSeoSection['title'] ?? $fallbackSeoSection['title'],
        'description' => $defaultSeoSection['description'] ?? $fallbackSeoSection['description'],
        'keywords' => implode(', ', is_array($configuredKeywords) ? $configuredKeywords : $fallbackKeywords),
        'url' => url('/'),
        'site_name' => config('seo.site_name', 'Extraordinary Africans'),
        'brand' => config('seo.brand', 'Extraordinary African'),
        'image' => asset($logos['share_card']),
        'agenda_logo' => asset($logos['agenda_2063']),
        'au_logo' => asset($logos['african_union']),
    ];

    $seo = array_merge($seoDefaults, is_array($seo ?? null) ? $seo : []);
    $sectionUrl = static fn ($section) => $section === $defaultSection
        ? url('/')
        : url('/?section='.$section);
    $structuredData = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'WebSite',
                '@id' => url('/').'#website',
                'name' => $seo['site_name'],
                'alternateName' => $seo['brand'],
                'url' => url('/'),
                'inLanguage' => 'en',
            ],
            [
                '@type' => 'Organization',
                '@id' => url('/').'#organization',
                'name' => $seo['site_name'],
                'url' => url('/'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $seo['agenda_logo'],
                    'width' => 207,
                    'height' => 62,
                ],
                'image' => [$seo['image'], $seo['agenda_logo'], $seo['au_logo']],
            ],
            [
                '@type' => 'WebPage',
                '@id' => $seo['url'].'#webpage',
                'url' => $seo['url'],
                'name' => $seo['title'],
                'description' => $seo['description'],
                'isPartOf' => ['@id' => url('/').'#website'],
                'publisher' => ['@id' => url('/').'#organization'],
                'primaryImageOfPage' => [
                    '@type' => 'ImageObject',
                    'url' => $seo['image'],
                    'width' => 1200,
                    'height' => 630,
                ],
                'about' => [
                    [
                        '@type' => 'Thing',
                        'name' => 'Agenda 2063',
                        'sameAs' => 'https://www.agenda2063.africa/',
                        'image' => $seo['agenda_logo'],
                    ],
                    [
                        '@type' => 'Thing',
                        'name' => 'African Union',
                        'sameAs' => 'https://au.int/',
                        'image' => $seo['au_logo'],
                    ],
                ],
                'inLanguage' => 'en',
            ],
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $seo['title'] }}</title>
    <meta name="description" content="{{ $seo['description'] }}">
    <meta name="keywords" content="{{ $seo['keywords'] }}">
    <meta name="author" content="{{ $seo['site_name'] }}">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="googlebot" content="index, follow">
    <meta name="theme-color" content="#4A1628">
    <link rel="canonical" href="{{ $seo['url'] }}">
    <link rel="image_src" href="{{ $seo['image'] }}">
    <link rel="icon" type="image/png" href="{{ $seo['agenda_logo'] }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $seo['site_name'] }}">
    <meta property="og:title" content="{{ $seo['title'] }}">
    <meta property="og:description" content="{{ $seo['description'] }}">
    <meta property="og:url" content="{{ $seo['url'] }}">
    <meta property="og:image" content="{{ $seo['image'] }}">
    <meta property="og:image:secure_url" content="{{ $seo['image'] }}">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Extraordinary Africans, Agenda 2063, and African Union logos">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seo['title'] }}">
    <meta name="twitter:description" content="{{ $seo['description'] }}">
    <meta name="twitter:image" content="{{ $seo['image'] }}">
    <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <!-- Header -->
    <header class="site-header">
        <div class="header-left">
            <div class="logo">
                <div class="logo-text">
                    <span class="agenda">Agend<span class="a-icon">A</span></span>
                    <span class="year">2063</span>
                    <span class="tagline">The Africa<br>We Want</span>
                </div>
                <div class="logo-divider"></div>
                <button class="hamburger" id="hamburgerBtn" aria-label="Menu">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>
        
        <div class="header-right">
            <button class="search-btn" id="searchToggle" type="button" aria-label="Search">
                <i class="fa fa-search"></i>
            </button>
            <div class="search-bar" id="searchBar" role="search">
                <input id="siteSearchInput" type="search" placeholder="Search pages..." autocomplete="off" aria-label="Search pages" aria-controls="siteSearchResults" aria-expanded="false">
                <button id="siteSearchSubmit" type="button" aria-label="Run search"><i class="fa fa-search"></i></button>
            </div>
            <div class="search-results" id="siteSearchResults" role="listbox" aria-live="polite"></div>
        </div>
    </header>

    <div class="hero-language-control global-language-control" aria-label="Platform language">
        <div class="language-segment" role="group" aria-label="Choose platform language">
            <button type="button" class="language-option is-active" data-hero-language="english" data-video-label="English" data-video-src="{{ asset('videos/exaf-nomination-english.mp4') }}" aria-pressed="true">English</button>
            <button type="button" class="language-option" data-hero-language="french" data-video-label="French" data-video-src="{{ asset('videos/exaf-nomination-french.mp4') }}" aria-pressed="false">French</button>
            <button type="button" class="language-option" data-hero-language="arabic" data-video-label="Arabic" data-video-src="{{ asset('videos/exaf-nomination-arabic.mp4') }}" aria-pressed="false">Arabic</button>
        </div>
        <button type="button" class="hero-sound-toggle" id="heroSoundToggle" aria-label="Play video sound" title="Play video sound">
            <i class="fa fa-volume-xmark" aria-hidden="true"></i>
        </button>
    </div>

    <!-- Hero Section (only on home) -->
    <section class="hero-section" id="heroSection">
        <video class="hero-video" id="heroVideo" autoplay muted loop playsinline preload="auto" aria-label="Extraordinary Africans nomination video">
            <source id="heroVideoSource" src="{{ asset('videos/exaf-nomination-english.mp4') }}" type="video/mp4">
        </video>
        <div class="hero-overlay" aria-hidden="true"></div>
        <div class="hero-content">
            <div class="hero-text">
                <ul>
                    <li>Join <span class="highlight">Extraordinary Africans Initiative</span> and Showcase Your Talent!</li>
                </ul>
                <p>A Global Stage for Creativity, Innovation, and Excellence - Submit Your Entry Today!</p>
                <a href="{{ $sectionUrl('nominations') }}" class="btn-apply" onclick="showSection('nominations'); return false;">Apply now</a>
            </div>
        </div>
    </section>

    <!-- Inner page layout (sidebar + content) -->
    <div class="inner-layout" id="innerLayout" style="display:none;">
        <!-- Sidebar Navigation -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-scroll-up" id="scrollUp"><i class="fa fa-chevron-up"></i></div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="{{ url('/') }}" class="nav-link nav-home" id="homeLink">Home <span class="arrow"><i class="fa fa-house"></i></span></a></li>
                    <li><a href="{{ url('/?section=account') }}" class="nav-link" data-section="account">Account <span class="arrow"><i class="fa fa-user"></i></span></a></li>
                    <li><a href="{{ $sectionUrl('about') }}" class="nav-link" data-section="about">About <span class="arrow"><i class="fa fa-arrow-right"></i></span></a></li>
                    <li><a href="{{ $sectionUrl('categories') }}" class="nav-link" data-section="categories">Categories and Descriptions <span class="arrow"><i class="fa fa-arrow-right"></i></span></a></li>
                    <li><a href="{{ $sectionUrl('nominations') }}" class="nav-link" data-section="nominations">Nominations <span class="arrow"><i class="fa fa-arrow-right"></i></span></a></li>
                    <li><a href="{{ $sectionUrl('voting') }}" class="nav-link" data-section="voting">Voting <span class="arrow"><i class="fa fa-check-to-slot"></i></span></a></li>
                    <li><a href="{{ $sectionUrl('flow') }}" class="nav-link" data-section="flow">Flow of Events <span class="arrow"><i class="fa fa-arrow-right"></i></span></a></li>
                    <li><a href="{{ $sectionUrl('judges') }}" class="nav-link" data-section="judges">Meet our Judges <span class="arrow"><i class="fa fa-arrow-right"></i></span></a></li>
                    <li><a href="{{ $sectionUrl('winners') }}" class="nav-link" data-section="winners">Winners <span class="arrow"><i class="fa fa-arrow-right"></i></span></a></li>
                </ul>
            </nav>
            <div class="sidebar-scroll-down" id="scrollDown"><i class="fa fa-chevron-down"></i></div>
        </aside>

        <!-- Mobile sidebar backdrop -->
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

        <!-- Main Content -->
        <main class="main-content" id="mainContent">
            @yield('content')
        </main>
    </div>

    <!-- Social Float (visible on inner pages) -->
    <div class="social-float" id="socialFloat" style="display:none;">
        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
        <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
        <a href="#" aria-label="Email"><i class="fa fa-envelope"></i></a>
    </div>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="footer-inner">
            <div class="footer-left">
                <nav class="footer-nav">
                    <a href="{{ $sectionUrl('about') }}" onclick="showSection('about'); return false;">About</a> -
                    <a href="{{ $sectionUrl('categories') }}" onclick="showSection('categories'); return false;">Categories and Descriptions</a><br>
                    <a href="{{ $sectionUrl('nominations') }}" onclick="showSection('nominations'); return false;">Nominations</a> -
                    <a href="{{ $sectionUrl('voting') }}" onclick="showSection('voting'); return false;">Voting</a> -
                    <a href="{{ $sectionUrl('flow') }}" onclick="showSection('flow'); return false;">Flow of Events</a> -
                    <a href="{{ $sectionUrl('judges') }}" onclick="showSection('judges'); return false;" class="footer-highlight">Meet our Judges</a><br>
                    <a href="{{ $sectionUrl('winners') }}" onclick="showSection('winners'); return false;">Winners</a>
                </nav>
                <div class="footer-divider"></div>
                <div class="footer-social">
                    <span>CONNECT WITH US</span>
                    <div class="social-icons">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" aria-label="Email"><i class="fa fa-envelope"></i></a>
                    </div>
                </div>
                <div class="footer-divider"></div>
            </div>
            <div class="footer-right">
                <div class="tweets-widget">
                    <h4>RECENT TWEETS</h4>
                    <div class="tweet-item">
                        <span class="at">@</span>
                        <div class="tweet-line"></div>
                    </div>
                    <div class="tweet-item">
                        <span class="at">@</span>
                        <div class="tweet-line"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>Copyright &copy; 2026 Extraordinary African. All rights reserved. | <a href="#">Terms &amp; Conditions</a> | Agenda 2063</p>
        </div>
    </footer>

    <!-- Modal -->
    <div class="modal-overlay" id="modalOverlay">
        <div class="modal-box" id="modalBox">
            <button class="modal-close" id="modalClose" aria-label="Close">&times;</button>
            <div class="modal-media" id="modalMedia"></div>
            <div class="modal-body">
                <span class="modal-tag" id="modalTag"></span>
                <h2 class="modal-title" id="modalTitle"></h2>
                <p class="modal-subtitle" id="modalSubtitle"></p>
                <p class="modal-text" id="modalText"></p>
                <span class="modal-date-tag" id="modalDate"></span>
            </div>
        </div>
    </div>

    <div id="google_translate_element" class="translation-engine" aria-hidden="true"></div>

    <script src="{{ asset('js/app.js') }}"></script>
    <script src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
</body>
</html>
