<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include '../config/koneksi.php';


if (isset($_SESSION['role']) && $_SESSION['role'] == 'peracik') {
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
    <title>Perscents Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #E2E8F0; 
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #5C7186;
            overflow: hidden; 
        }

        @keyframes bounceDown {
            0% {
                opacity: 0;
                transform: translateY(-500px); 
            }
            60% {
                opacity: 1;
                transform: translateY(25px);
            }
            75% {
                transform: translateY(-10px);
            }
            90% {
                transform: translateY(5px); 
            }
            100% {
                transform: translateY(0); 
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        .container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 900px;
            width: 100%;
            padding: 20px;
            gap: 60px;
        }

        .left-section {
            flex: 1;
            animation: bounceDown 1.2s cubic-bezier(0.25, 1, 0.5, 1) forwards;
        }

        .logo {
            display: flex;
            align-items: center; 
            gap: 10px; 
        }

        .logo-img {
            max-height: 40px; 
            width: auto;
            display: block;
        }

        .logo-text {
            font-family: 'Playball', cursive; 
            font-size: 28px;
            color: #5C7186;
        }

        .left-section h1 {
            font-size: 42px;
            font-weight: 700;
            line-height: 1.2;
            color: #5C7186;
            margin-bottom: 15px;
        }

        .left-section p {
            font-size: 15px;
            color: #71859B;
            line-height: 1.5;
        }

        .right-section {
            flex: 1;
            max-width: 400px;
            width: 100%;
            animation: fadeIn 1s ease-out delayed;
        }

        .login-card {
            background-color: #FFFFFF;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #7A8D9F;
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper .icon-left {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #7A8D9F;
            font-size: 14px;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #7A8D9F;
            font-size: 14px;
            cursor: pointer;
            transition: color 0.2s;
        }

        .toggle-password:hover {
            color: #5C7186;
        }

        .input-wrapper input {
            width: 100%;
            padding: 12px 40px 12px 40px; 
            border: 2px solid #D6DFE8;
            border-radius: 25px;
            font-size: 14px;
            color: #5C7186;
            outline: none;
            transition: border-color 0.3s ease;
        }

        .input-wrapper input:focus {
            border-color: #5C7186;
        }

        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
        }

        .signup-text {
            font-size: 13px;
            color: #7A8D9F;
        }

        .signup-text strong {
            color: #5C7186;
            cursor: pointer;
        }

        .login-btn {
            background-color: #5C7186;
            color: #FFFFFF;
            border: none;
            padding: 12px 35px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .login-btn:hover {
            background-color: #4A5B6C;
        }

        .error-message {
            background-color: #fadbd8;
            color: #e74c3c;
            padding: 10px;
            border-radius: 8px;
            font-size: 13px;
            text-align: center;
            margin-bottom: 20px;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                text-align: center;
                gap: 40px;
            }
            .logo {
                justify-content: center;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="left-section">
            <div class="logo">
                <img src="../assets/logo_perscents.png" alt="Perscents Logo" class="logo-img">
                <span class="logo-text">Perscents</span>
            </div>
            <h1>Login Ke<br>Dashboard Peracik</h1>
            <p>Sebelum lanjut, masuk ke akun<br>peracik dulu yuk!</p>
        </div>

        <div class="right-section">
            <div class="login-card">
                
                <?php if ($error != ''): ?>
                    <div class="error-message">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form action="" method="POST"> 
                    <div class="form-group">
                        <label>Email</label>
                        <div class="input-wrapper">
                            <i class="fa-regular fa-user icon-left"></i>
                            <input type="email" name="email" required> 
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-lock icon-left"></i>
                            <input type="password" name="password" id="passwordField" required>
                            <i class="fa-regular fa-eye toggle-password" id="togglePasswordIcon"></i>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" name="login" class="login-btn">Login</button> 
                    </div>
                </form>

            </div>
        </div>
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