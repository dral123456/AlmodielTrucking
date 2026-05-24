<?php
// Simple PHP Landing Page
$title = "Almodiel Trucking Service - Reliable Delivery Solutions";
$year = date("Y");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f4f6f9;
            color: #333;
        }

        header {
            background: #111827;
            color: white;
            padding: 20px 60px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            transition: 0.3s;
        }

        nav a:hover {
            color: #60a5fa;
        }

        .hero {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
            background:
                linear-gradient(rgba(0,0,0,0.55), rgba(0,0,0,0.55)),
                url('https://images.unsplash.com/photo-1519003722824-194d4455a60c?q=80&w=2070&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            color: white;
        }

        .hero-content {
            max-width: 800px;
            position: relative;
            z-index: 2;
        }

        .hero-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.4);
        }

        .hero h1 {
            font-size: 55px;
            margin-bottom: 20px;
        }

        .hero p {
            font-size: 18px;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .btn {
            display: inline-block;
            padding: 15px 30px;
            background: white;
            color: #2563eb;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: 0.3s;
        }

        .btn:hover {
            background: #dbeafe;
        }

        .features {
            padding: 80px 60px;
            background: white;
        }

        .section-title {
            text-align: center;
            margin-bottom: 50px;
        }

        .section-title h2 {
            font-size: 40px;
            margin-bottom: 10px;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
        }

        .feature-card {
            background: #f9fafb;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.08);
            transition: 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-card h3 {
            margin-bottom: 15px;
            color: #2563eb;
        }

        .sponsors {
            padding: 80px 60px;
            background: #ffffff;
        }

        .about {
            padding: 80px 60px;
            background: #eef2ff;
            text-align: center;
        }

        .about p {
            max-width: 800px;
            margin: auto;
            line-height: 1.8;
            font-size: 18px;
        }

        footer {
            background: #111827;
            color: white;
            text-align: center;
            padding: 20px;
        }

        @media (max-width: 768px) {
            header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .hero h1 {
                font-size: 38px;
            }

            .hero p {
                font-size: 16px;
            }

            .features,
            .about {
                padding: 60px 20px;
            }
        }
    </style>
</head>
<body>

<header>
    <div class="logo">Almodiel Trucking </div>

    <nav>
        <a href="#home">Home</a>
        <a href="#features">Features</a>
        <a href="#about">About</a>
        <a href="#contact">Contact</a>
    </nav>
</header>

<section class="hero" id="home">
    <div class="hero-overlay"></div>

    <div class="hero-content">
        <h1>Reliable Trucking Services Across The Country</h1>

        <p>
            Fast, secure, and professional delivery solutions for businesses and customers.
            Almodiel Trucking Service helps move your cargo safely and efficiently.
        </p>

        <a href="#" class="btn" id="bookServiceBtn">Book a Service</a>
    </div>
</section>

<section class="features" id="features">
    <div class="section-title">
        <h2>Our Features</h2>
        <p>Everything you need for your website</p>
    </div>

    <div class="feature-grid">
        <div class="feature-card">
            <h3>Fast Performance</h3>
            <p>
                Optimized design and clean code for better speed and responsiveness.
            </p>
        </div>

        <div class="feature-card">
            <h3>Responsive Design</h3>
            <p>
                Looks perfect on desktop, tablet, and mobile devices.
            </p>
        </div>

        <div class="feature-card">
            <h3>Easy Customization</h3>
            <p>
                Edit colors, text, and layouts quickly based on your needs.
            </p>
        </div>
    </div>
</section>

<section class="sponsors">
    <div class="section-title">
        <h2>Official Sponsor</h2>
        <p>Trusted partner supporting our trucking operations</p>
    </div>

    <div class="feature-grid">
        <div class="feature-card">
            <h3>Gamay's Eatery</h3>
            <p>
                A cozy place where delicious homemade meals, warm smiles, and affordable prices come together to make every customer feel at home.
            </p>
        </div>
    </div>
</section>

<section class="about" id="about">
    <div class="section-title">
        <h2>About Us</h2>
    </div>

    <p>
        We create modern and professional web solutions using PHP, HTML, CSS, and JavaScript.
        This landing page can be used for portfolios, businesses, school projects, or startup websites.
    </p>
</section>

<footer id="contact">
    <p>
        © <?php echo $year; ?> Almodiel Trucking Service. All Rights Reserved.
    </p>
</footer>

</body>
<script>
document.addEventListener("DOMContentLoaded", function () {

    const bookBtn = document.getElementById("bookServiceBtn");

    if(bookBtn){

        bookBtn.addEventListener("click", function (e) {

            e.preventDefault();

            window.location.href = "?route=customer-login";

        });

    }

});
</script>
</html>
