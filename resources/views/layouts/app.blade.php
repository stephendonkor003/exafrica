<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Extraordinary African | Agenda 2063</title>
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
            <button class="search-btn" id="searchToggle" aria-label="Search">
                <i class="fa fa-search"></i>
            </button>
            <div class="search-bar" id="searchBar">
                <input type="text" placeholder="Search...">
                <button><i class="fa fa-search"></i></button>
            </div>
        </div>
    </header>

    <!-- Hero Section (only on home) -->
    <section class="hero-section" id="heroSection">
        <div class="hero-content">
            <div class="hero-text">
                <ul>
                    <li>Join <span class="highlight">Extraordinary Africans Initiative</span> and Showcase Your Talent!</li>
                </ul>
                <p>A Global Stage for Creativity, Innovation, and Excellence - Submit Your Entry Today!</p>
                <a href="#" class="btn-apply" onclick="showSection('nominations'); return false;">Apply now</a>
            </div>
            <div class="hero-logo">
                <div class="ea-logo">
                    <div class="ea-text">
                        <span class="extra">EXTRA</span>
                        <span class="ordinary">ORDINARY</span>
                        <span class="african">AFRICAN</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="hero-shapes">
            <div class="shape circle-blue"></div>
            <div class="shape circle-yellow"></div>
            <div class="shape circle-pink"></div>
            <div class="shape triangle"></div>
            <div class="shape dots-ring"></div>
        </div>
    </section>

    <!-- Inner page layout (sidebar + content) -->
    <div class="inner-layout" id="innerLayout" style="display:none;">
        <!-- Sidebar Navigation -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-scroll-up" id="scrollUp"><i class="fa fa-chevron-up"></i></div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="#" class="nav-link nav-home" id="homeLink">Home <span class="arrow"><i class="fa fa-house"></i></span></a></li>
                    <li><a href="#" class="nav-link" data-section="account">Account <span class="arrow"><i class="fa fa-user"></i></span></a></li>
                    <li><a href="#" class="nav-link" data-section="backoffice">Back Office <span class="arrow"><i class="fa fa-briefcase"></i></span></a></li>
                    <li><a href="#" class="nav-link" data-section="about">About <span class="arrow"><i class="fa fa-arrow-right"></i></span></a></li>
                    <li><a href="#" class="nav-link" data-section="categories">Categories and Descriptions <span class="arrow"><i class="fa fa-arrow-right"></i></span></a></li>
                    <li><a href="#" class="nav-link" data-section="nominations">Nominations <span class="arrow"><i class="fa fa-arrow-right"></i></span></a></li>
                    <li><a href="#" class="nav-link" data-section="voting">Voting <span class="arrow"><i class="fa fa-check-to-slot"></i></span></a></li>
                    <li><a href="#" class="nav-link" data-section="flow">Flow of Events <span class="arrow"><i class="fa fa-arrow-right"></i></span></a></li>
                    <li><a href="#" class="nav-link" data-section="judges">Meet our Judges <span class="arrow"><i class="fa fa-arrow-right"></i></span></a></li>
                    <li><a href="#" class="nav-link" data-section="winners">Winners <span class="arrow"><i class="fa fa-arrow-right"></i></span></a></li>
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
                    <a href="#" onclick="showSection('about'); return false;">About</a> -
                    <a href="#" onclick="showSection('categories'); return false;">Categories and Descriptions</a><br>
                    <a href="#" onclick="showSection('backoffice'); return false;">Back Office</a> -
                    <a href="#" onclick="showSection('nominations'); return false;">Nominations</a> -
                    <a href="#" onclick="showSection('voting'); return false;">Voting</a> -
                    <a href="#" onclick="showSection('flow'); return false;">Flow of Events</a> -
                    <a href="#" onclick="showSection('judges'); return false;" class="footer-highlight">Meet our Judges</a><br>
                    <a href="#" onclick="showSection('winners'); return false;">Winners</a>
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
            <p>Copyright &copy; 2024 Extraordinary African. All rights reserved. | <a href="#">Terms &amp; Conditions</a> | Agenda 2063</p>
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

    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
