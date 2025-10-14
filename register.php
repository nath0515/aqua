<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AquaDrop Register</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

<style>
    /* --- General Page Style --- */
    body, html {
        margin: 0;
        padding: 0;
        height: 100%;
        font-family: 'Poppins', sans-serif;
        background: linear-gradient(-45deg, #1e3c72, #2a5298, #1e3c72, #2a5298);
        background-size: 400% 400%;
        animation: gradientBG 10s ease infinite;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    @keyframes gradientBG {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    /* --- Layout --- */
    .register-container {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 90%;
        max-width: 1000px;
        height: auto;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 20px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        backdrop-filter: blur(10px);
        padding: 40px;
        gap: 30px;
    }

    .register-left {
        flex: 1;
        text-align: center;
        color: white;
    }

    .register-left img {
        width: 120px;
        margin-bottom: 15px;
        animation: float 3s ease-in-out infinite;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }

    .register-left h2 {
        letter-spacing: 3px;
    }

    .register-right {
        flex: 1;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .register-form {
        width: 100%;
        max-width: 400px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 20px;
        padding: 30px;
        backdrop-filter: blur(15px);
        box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        color: white;
        text-align: left;
    }

    .register-form h2 {
        text-align: center;
        font-weight: 600;
        margin-bottom: 20px;
    }

    label {
        display: block;
        margin-bottom: 6px;
        font-size: 14px;
    }

    .form-control {
        width: 100%;
        padding: 10px 14px;
        border-radius: 8px;
        border: none;
        background: rgba(255,255,255,0.2);
        color: #fff;
        margin-bottom: 15px;
        transition: all 0.3s ease;
    }

    .form-control::placeholder {
        color: #ddd;
    }

    .form-control:focus {
        background: rgba(255,255,255,0.3);
        outline: none;
        box-shadow: 0 0 5px rgba(255,255,255,0.5);
    }

    .register-btn {
        width: 100%;
        padding: 12px;
        border: none;
        border-radius: 30px;
        background: linear-gradient(45deg, #1e3c72, #2a5298);
        color: white;
        font-weight: bold;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .register-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    }

    .back-link {
        display: block;
        text-align: center;
        margin-top: 15px;
        color: #cde3ff;
        text-decoration: none;
        transition: 0.3s;
    }

    .back-link:hover {
        text-decoration: underline;
        color: #fff;
    }

    /* --- Responsive Design --- */
    @media (max-width: 900px) {
        .register-container {
            flex-direction: column;
            text-align: center;
            padding: 20px;
        }

        .register-left, .register-right {
            flex: none;
            width: 100%;
        }

        .register-left img {
            width: 100px;
        }

        .register-form {
            width: 100%;
            max-width: 100%;
            margin-top: 20px;
        }
    }

    @media (max-width: 480px) {
        .register-form {
            padding: 20px;
        }

        .form-control {
            font-size: 14px;
        }

        .register-btn {
            font-size: 14px;
            padding: 10px;
        }
    }
</style>
</head>

<body>
    <div class="register-container">
        <div class="register-left">
            <img src="your-logo.png" alt="AquaDrop Logo">
            <h2>AQUADROP</h2>
        </div>

        <div class="register-right">
            <form class="register-form">
                <h2>Create Account</h2>

                <label>Username</label>
                <input type="text" class="form-control" placeholder="Username">

                <label>Email Address</label>
                <input type="email" class="form-control" placeholder="Email">

                <label>Password</label>
                <input type="password" class="form-control" placeholder="Password">

                <label>Confirm Password</label>
                <input type="password" class="form-control" placeholder="Confirm Password">

                <button class="register-btn">Continue</button>
                <a href="#" class="back-link">Go back</a>
            </form>
        </div>
    </div>
</body>
</html>
