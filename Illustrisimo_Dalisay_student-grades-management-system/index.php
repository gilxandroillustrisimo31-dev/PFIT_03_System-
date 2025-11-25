<?php
include 'includes/conn.php';
include 'includes/functions.php';

if (isLoggedIn()) {
    redirect(getDashboardUrl());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        
        redirect(getDashboardUrl());
    } else {
        $error = "Invalid username or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Student Grade Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>

    .login-container {
        position: relative;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;

        background-image: url("img/college background.jpg");
        background-size: cover;          
        background-position: center;    
        background-repeat: no-repeat;
    }

    .login-container::before {
        content: "";
        position: absolute;
        inset: 0;                     
        backdrop-filter: blur(4px);   
        background: rgba(0,0,0,0.45); 
        z-index: 1;                  
    }


    .login-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        padding: 2rem;
        width: 100%;
        max-width: 400px;
        z-index: 2;
        opacity: 85%;
    }

    .logo {
        text-align: center;
        margin-bottom: 2rem;
    }
    
    .logo i {
        font-size: 3rem;
        color: #667eea;
        margin-bottom: 1rem;
    }

    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo">
                <i class="fas fa-graduation-cap"></i>
                <h3>Student Grades Management</h3>
                <p class="text-muted">Sign in to your account</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 mb-3">
                    <i class="fas fa-sign-in-alt"></i> Log In
                </button>
            </form>
            
            
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>