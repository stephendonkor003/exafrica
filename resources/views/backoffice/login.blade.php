<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Back Office Login | Extraordinary Africans</title>
    <link rel="stylesheet" href="{{ asset('css/backoffice.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bo-login-body">
    <video class="bo-login-video" autoplay muted loop playsinline preload="auto" aria-hidden="true">
        <source src="{{ asset('videos/citizens-talk-logo-reveal-4k.mp4') }}" type="video/mp4">
    </video>
    <main class="bo-login-shell">
        <section class="bo-login-panel">
            <div class="bo-login-brand">
                <span class="bo-brand-mark"><i class="fa fa-earth-africa" aria-hidden="true"></i></span>
                <div>
                    <strong>Extraordinary Africans</strong>
                    <span>Back Office</span>
                </div>
            </div>

            <div class="bo-login-copy">
                <h1>Operations Access</h1>
                <p>Sign in with a Super Admin account to manage nominations, publishing, categories, users, and voting phases.</p>
            </div>

            @if ($errors->any())
                <div class="bo-alert">{{ $errors->first() }}</div>
            @endif

            <form class="bo-login-form" method="POST" action="{{ route('backoffice.login.submit') }}">
                @csrf
                <label>
                    <span>Email</span>
                    <input name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
                </label>
                <label>
                    <span>Password</span>
                    <input name="password" type="password" autocomplete="current-password" required>
                </label>
                <button type="submit">
                    <i class="fa fa-arrow-right-to-bracket" aria-hidden="true"></i>
                    <span>Enter Back Office</span>
                </button>
            </form>
        </section>

        <aside class="bo-login-aside" aria-hidden="true">
            <div class="bo-aside-stat">
                <span>Protected</span>
                <strong>Super Admin Only</strong>
            </div>
            <div class="bo-aside-lines">
                <span></span><span></span><span></span><span></span>
            </div>
        </aside>
    </main>
</body>
</html>
