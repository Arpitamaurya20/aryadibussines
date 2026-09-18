<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corporate Login | Aryadi Business</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            background: radial-gradient(circle at 50% 50%, #0d1e3d 0%, #070d1e 100%);
            background-color: #070d1e;
            position: relative;
        }

        /* Glowing background auras */
        .glow-orb-1 {
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(27, 160, 227, 0.12) 0%, rgba(27, 160, 227, 0) 70%);
            top: -10%;
            left: -10%;
            border-radius: 50%;
            filter: blur(60px);
            z-index: 1;
            animation: float-1 12s ease-in-out infinite alternate;
        }

        .glow-orb-2 {
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(13, 44, 92, 0.25) 0%, rgba(13, 44, 92, 0) 70%);
            bottom: -15%;
            right: -10%;
            border-radius: 50%;
            filter: blur(80px);
            z-index: 1;
            animation: float-2 16s ease-in-out infinite alternate;
        }

        @keyframes float-1 {
            0% {
                transform: translate(0, 0) scale(1);
            }

            100% {
                transform: translate(40px, 30px) scale(1.1);
            }
        }

        @keyframes float-2 {
            0% {
                transform: translate(0, 0) scale(1);
            }

            100% {
                transform: translate(-30px, -40px) scale(1.05);
            }
        }

        .container {
            width: 90%;
            max-width: 550px;
            text-align: center;
            background: rgba(10, 20, 42, 0.45);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 28px;
            padding: 60px 40px;
            color: #fff;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.4);
            z-index: 10;
            position: relative;
        }

        /* Glass card shine effect */
        .container::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 100%;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0) 50%);
            border-radius: 28px;
            pointer-events: none;
        }

        .logo-img {
            height: 70px;
            width: auto;
            margin-bottom: 12px;
            filter: drop-shadow(0 8px 16px rgba(27, 160, 227, 0.25));
        }

        .brand-title {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 3px;
            color: #e2e8f0;
            margin-bottom: 35px;
            text-transform: uppercase;
        }

        h1 {
            font-size: 40px;
            font-weight: 700;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #ffffff 0%, #a5b4fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.5px;
        }

        p {
            color: #94a3b8;
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 40px;
        }

        .btn-back {
            display: inline-block;
            padding: 12px 30px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            color: #ffffff;
            background: linear-gradient(135deg, #1ba0e3 0%, #0d2c5c 100%);
            border-radius: 50px;
            box-shadow: 0 4px 15px rgba(27, 160, 227, 0.3);
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(27, 160, 227, 0.45);
        }

        @media (max-width: 576px) {
            .container {
                padding: 40px 24px;
            }

            h1 {
                font-size: 32px;
            }
        }
    </style>
</head>

<body>

    <!-- Background Auras -->
    <div class="glow-orb-1"></div>
    <div class="glow-orb-2"></div>

    <div class="container">
        <div class="logo">
            <img src="assets/logo.png" alt="Aryadi Business" class="logo-img">
            <div class="brand-title">Aryadi Business</div>
        </div>

        <h1>Coming Soon</h1>
        <!-- <p>Our secure corporate client portal is currently under development to serve you better. We'll be launching soon!</p> -->

        <a href="index.php" class="btn-back">Return to Homepage</a>
    </div>

</body>

</html>