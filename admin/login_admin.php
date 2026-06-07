<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include '../config/koneksi.php';

if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
    header("Location: index.php");
    exit;
}

$error = '';

if (isset($_POST['login'])) {
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password']; 
    $query  = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
        if ($password === $row['password']) {
            $_SESSION['id_user']  = $row['id'];
            $_SESSION['nama']     = $row['nama'];
            $_SESSION['role']     = $row['role']; 
            header("Location: index.php");
            exit;
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Email tidak terdaftar!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PERSCENTS | Admin Portal</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            /* Background Image dengan overlay gelap agar tulisan terbaca */
            background-image: linear-gradient(rgba(47, 65, 86, 0.7), rgba(47, 65, 86, 0.7)), url('../assets/dokumentasi/foto_background.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            color: #2B3674;
        }

        .login-container {
            width: 100%;
            max-width: 440px;
            background: rgba(255, 255, 255, 0.95); /* Sedikit transparan agar menyatu dengan foto */
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 50px 45px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
            text-align: center;
            margin: 20px;
        }

        .brand-logo {
            width: 70px;
            height: 70px;
            background: #F4F7FE;
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
        }

        .brand-logo img {
            width: 45px;
            height: 45px;
            object-fit: contain;
        }

        h2 {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 8px;
            color: #2B3674;
            letter-spacing: -0.5px;
        }

        p.subtitle {
            color: #7A86A1;
            font-size: 14px;
            margin-bottom: 35px;
        }

        .form-group {
            text-align: left;
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #2B3674;
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper i.icon-left {
            position: absolute;
            left: 18px;
            color: #A3AED0;
        }

        .input-wrapper input {
            width: 100%;
            padding: 15px 18px 15px 48px;
            border: 2px solid #E9EDF7;
            border-radius: 14px;
            outline: none;
            font-size: 14px;
            color: #2B3674;
            background: #FAFCFF;
            transition: all 0.3s ease;
        }

        .input-wrapper input:focus {
            border-color: #5A738E;
            background: #FFFFFF;
        }

        .input-wrapper i.toggle-password {
            position: absolute;
            right: 18px;
            color: #A3AED0;
            cursor: pointer;
        }

        .login-btn {
            width: 100%;
            padding: 16px;
            background: #5A738E;
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 15px;
        }

        .login-btn:hover {
            background: #495e75;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 25px;
            color: #EE5D50;
            background: #FEECEE;
            border: 1px solid #fecaca;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="brand-logo">
            <img src="../assets/Logo_Perscents.png" alt="Logo" onerror="this.src='../assets/perscents_kotak.png'; this.onerror=null;">
        </div>
        
        <h2>Portal Admin</h2>
        <p class="subtitle">Masuk untuk mengelola sistem PERSCENTS</p>

        <?php if ($error): ?>
            <div class="alert">
                <i class="fa-solid fa-circle-exclamation"></i> <?= $error; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <div class="input-wrapper">
                    <i class="fa-regular fa-envelope icon-left"></i>
                    <input type="email" name="email" placeholder="admin@perscents.com" required> 
                </div>
            </div>

            <div class="form-group">
                <label>Password</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-lock icon-left"></i>
                    <input type="password" name="password" id="passwordField" placeholder="••••••••" required>
                    <i class="fa-regular fa-eye toggle-password" id="togglePasswordIcon"></i>
                </div>
            </div>

            <button type="submit" name="login" class="login-btn">Secure Login</button> 
        </form>
    </div>

    <script>
        const togglePasswordIcon = document.querySelector('#togglePasswordIcon');
        const passwordField = document.querySelector('#passwordField');

        togglePasswordIcon.addEventListener('click', function () {
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>