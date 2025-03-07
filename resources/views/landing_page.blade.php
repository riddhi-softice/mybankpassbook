<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bank Passbook - Manage Your Bank Account on the Go</title>
    <meta name="description" content="My Bank Passbook helps you manage your bank account efficiently with features like viewing balance, transactions, and more. Available on Google Play and App Store.">
    <meta name="keywords" content="bank passbook, mobile banking, account balance, view transactions, online banking, mobile banking app, Google Play, App Store">
    <meta name="author" content="My Bank Passbook Team">

    <!-- Open Graph / Facebook Meta Tags -->
    <meta property="og:title" content="My Bank Passbook - Manage Your Bank Account on the Go">
    <meta property="og:description" content="Track your bank account balance, view recent transactions, and access other banking services from your smartphone. Available on both Google Play and App Store.">
    <meta property="og:image" content="{{ asset('public/assets/landing_page/logo.png') }}">
    <meta property="og:url" content="https://www.mybankpassbook.com">
    <meta property="og:type" content="website">

    <!-- Twitter Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="My Bank Passbook - Manage Your Bank Account on the Go">
    <meta name="twitter:description" content="Use My Bank Passbook to view your account balance, recent transactions, and manage your bank account from your smartphone. Available on Google Play and App Store.">
    <meta name="twitter:image" content="https://www.mybankpassbook.com/assets/twitter-image.jpg">

    <!-- Apple Meta Tags -->
    <meta name="apple-mobile-web-app-title" content="My Bank Passbook">
    <link rel="apple-touch-icon" href="https://www.mybankpassbook.com/assets/apple-touch-icon.png">

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('public/assets/landing_page/img/logo.png') }}" type="image/x-icon">

    <link rel="stylesheet" href="{{ asset('public/assets/landing_page/final.css') }}">
</head>
<body>
    <header>
        <div class="logo">
            <img src="{{ asset('public/assets/landing_page/img/logo1.png') }}" alt="Bank Logo">
        </div>
        <nav>
            <ul>
                <li><a href="#">Home</a></li>
                <li><a href="https://mybankpassbook.com/privacy_policy.html">Privacy Policy</a></li>
            </ul>
        </nav>
    </header>

    <section class="hero">
        <div class="container">
            <div class="content">
                <h1>My Bank Passbook</h1>
                <div class="app-buttons">
                    <a href="https://play.google.com/store/apps/details?id=com.allbankpassbook.balanchecker" class="playstore">
                        <img src="{{ asset('public/assets/landing_page/img/image.png') }}" alt="Google Play">
                    </a>
                    <!-- <a href="https://www.apple.com/in/app-store/" class="appstore">
                        <img src="{{ asset('public/assets/landing_page/image-1.png') }}" alt="App Store">
                    </a> -->
                </div>
            </div>
            <div class="phone">
                <img src="{{ asset('public/assets/landing_page/img/phone-mockup.png') }}" alt="Phone App Mockup">
                <!-- <div class="overlay">
                    <div class="transaction-popup">
                        <h3>Bank Name</h3>
                        <p>A/C No: XX3672</p>
                        <p>Active Balance: ₹ XX,XX,XXX/-</p>
                        <button>View Transactions</button>
                    </div>
                </div> -->
            </div>
        </div>
    </section>
</body>
</html>
