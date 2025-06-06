<?php
include("./config/db.php");

session_start(); // Start session at the top

// Check if user is already logged in
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    if ($_SESSION['role'] === 'Admin') {
        header("Location: index.php");
    } elseif ($_SESSION['role'] === 'Teacher') {
        header("Location: index_teacher.php");
    }
    exit;
}

$errorMsg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Sanitize inputs to prevent SQL injection
    $username = mysqli_real_escape_string($conn, $username);
    $password = mysqli_real_escape_string($conn, $password);

    // Query the user table
    $query = "SELECT * FROM user WHERE username = '$username' AND password = '$password'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        $role = $user['role'];

        // Store user data in session
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;

        // Redirect based on role
        if ($role == 'Admin') {
            header("Location: index.php");
        } elseif ($role == 'Teacher') {
            header("Location: index_teacher.php");
        }
        exit;
    } else {
        $errorMsg = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="School Management System Login">
    <meta name="author" content="">
    <title>School Management - Login</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom styles -->
    <style>
        body {
            background-color: #E5E5E5FF;
            font-family: 'Nunito', sans-serif;
            margin: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 900px;
            height: 500px;
            display: flex;
        }

        .login-image {
            background-color: #4171DAFF;
            width: 50%;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 1rem;
            background-image: url('https://via.placeholder.com/450x500');
            background-size: cover;
            background-position: center;
            background-blend-mode: overlay;
        }

        .login-image .logo-container {
            text-align: center;
            margin-bottom: 1rem;
        }

        .login-image .logo-container img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
        }

        .login-image .text-overlay {
            color: white;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
            text-align: center;
        }

        .login-image .text-overlay h2 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .login-image .text-overlay h3 {
            font-size: 1.2rem;
        }

        .login-form {
            width: 50%;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background-color: white;
            color: #333;
        }

        .login-form h3 {
            font-size: 2rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4171DAFF;
        }

        .login-form h3::before {

            margin-right: 10px;
        }

        .login-form .form-control {
            background-color: #ffffff;
            border: 1px solid #ced4da;
            border-radius: 5px;
            padding: 0.5rem;
            margin-bottom: 1rem;
            color: #333;
        }

        .login-form .form-control:focus {
            border-colorCharSet: #4171DAFF;
            box-shadow: 0 0 5px rgba(65, 113, 218, 0.5);
        }

        .btn-login {
            background-color: #4171DAFF;
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 5px;
            width: 100%;
            transition: background-color 0.3s ease;
        }

        .btn-login:hover {
            background-color: #033e99;
        }

        .link-text {
            font-size: 0.9rem;
            color: #4171DAFF;
            text-align: center;
            margin-top: 1rem;
        }

        .link-text a {
            color: #4171DAFF;
            text-decoration: none;
        }

        .link-text a:hover {
            text-decoration: underline;
        }

        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        @media (max-width: 768px) {
            .login-container {
                width: 100%;
                height: auto;
                flex-direction: column;
            }

            .login-image,
            .login-form {
                width: 100%;
                height: auto;
                min-height: 250px;
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-image">
            <div class="logo-container">
                <img src="img/mylogo.png" alt="School Logo" width="150" height="150">
            </div>
            <div class="text-overlay">
                <h2>ລະບົບຈັດການຂໍ້ມູນ</h2>
                <h3>
                    ໂຮງຮຽນອານຸບານ - ປະຖົມສານຝັນ
                </h3>
            </div>
        </div>

        <div class="login-form">

            <h3>ເຂົ້າສູ່ລະບົບ</h3>
            <?php if (!empty($errorMsg)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($errorMsg); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <form method="post" action="login.php">
                <div class="mb-3">
                    <input type="text" class="form-control" name="username" id="username" placeholder="ຊື່ຜູ້ໃຊ້"
                        required>
                </div>
                <div class="mb-3">
                    <input type="password" class="form-control" name="password" id="password" placeholder="ລະຫັດຜ່ານ"
                        required>
                </div>
                <button type="submit" class="btn-login">ເຂົ້າສູ່ລະບົບ</button>
            </form>
            <div class="link-text">
                <a href="#">ລຶມລະຫັດຜ່ານ?</a>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>

</html>