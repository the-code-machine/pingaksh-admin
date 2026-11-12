<?php

session_start();

// If already logged in, route them to their respective dashboards
if (!empty($_SESSION['admin_id'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'carbon_editor') {
        header("Location: carbon/index.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

require_once __DIR__ . '/../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $login_id = trim($_POST['login_id'] ?? '');
    $password = $_POST['password'] ?? '';

    // Check for matching email OR name (username) in case the team member doesn't use an email format
    $stmt = $pdo->prepare("
        SELECT id, name, email, password_hash, role
        FROM users
        WHERE (email = ? OR name = ?) AND is_active = 1
        LIMIT 1
    ");

    $stmt->execute([$login_id, $login_id]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {

        session_regenerate_id(true);

        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_name'] = $user['name'];
        
        // Ensure both session keys are set so auth.php and header.php work perfectly
        $_SESSION['admin_role'] = $user['role']; 
        $_SESSION['role'] = $user['role']; 

        // Route team members directly to their restricted module
        if ($user['role'] === 'carbon_editor') {
            header("Location: carbon/index.php");
        } else {
            header("Location: index.php");
        }
        exit;
    }

    $error = "Invalid credentials or inactive account.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Login - Pingaksh Renewables</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #071b3a, #123d73);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-box {
            width: 400px;
            max-width: 92%;
            background: #fff;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,.25);
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo h1 {
            margin: 0;
            color: #0b2d5c;
            font-size: 26px;
        }

        .logo p {
            color: #777;
            margin-top: 8px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }

        input {
            width: 100%;
            padding: 13px;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 18px;
            font-size: 15px;
        }

        button {
            width: 100%;
            padding: 14px;
            border: 0;
            border-radius: 8px;
            background: #0b4f9c;
            color: white;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
        }

        button:hover {
            background: #083d79;
        }

        .error {
            background: #fee2e2;
            color: #b91c1c;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 18px;
            font-size: 14px;
        }
    </style>
</head>

<body>

<div class="login-box">

    <div class="logo">
        <h1>Pingaksh Renewables</h1>
        <p>Enterprise CMS & Telemetry Portal</p>
    </div>

    <?php if ($error): ?>
        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <label>Email or Username</label>
        <input
            type="text"
            name="login_id"
            placeholder="admin@example.com or carbon_team"
            required
        >

        <label>Password</label>
        <input
            type="password"
            name="password"
            placeholder="••••••••"
            required
        >

        <button type="submit">
            Login
        </button>

    </form>

</div>

</body>
</html>