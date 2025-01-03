<?php
session_start();
// Hardcoded admin credentials
include 'db_connection.php';

define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'adminpass');

// Admin login check
if (isset($_POST['sign_in'])) {
    $username = $_POST['login_username'];
    $password = $_POST['login_password'];

    // Check if the credentials match the hardcoded admin ones
    if ($username == ADMIN_USERNAME && $password == ADMIN_PASSWORD) {
        $_SESSION['role'] = 'admin';
        $_SESSION['username'] = $username;
        $_SESSION['logged_in'] = true;
        header("Location: index.php"); // Redirect to avoid form resubmission
        exit;
    } else {
        // Check if it's a normal user login
        $query = "SELECT * FROM users WHERE username = '$username'";
        $result = mysqli_query($conn, $query);
        $user = mysqli_fetch_assoc($result);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['role'] = $user['role'];
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['logged_in'] = true;
            header("Location: index.php"); // Redirect to avoid form resubmission
            exit;
        } else {
            echo "Invalid credentials. Please try again.<br>";
        }
    }
}
?>

<!doctype html>
<html lang="en">
  <head>
    <title>Login to your account</title>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- <link href="https://fonts.googleapis.com/css?family=Roboto|Courgette|Pacifico:400,700" rel="stylesheet"> CANT FIND-->
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
  </head>
  <body>
      
    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
    <link rel="stylesheet" type="text/css" href="css/signin.css">

    <div class="signin-form">
        <form action="" method="post">
            <div class="form-header">
                <h2>Sign in</h2>
                <p>Login to BibliotecaApp</p>
            </div>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" class="form-control" name="login_username" placeholder="Name" autocomplete="off" Required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" name="login_password" placeholder="Password" autocomplete="off" Required>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block btn-lg" name="sign_in">Sign in</button>
            </div>
            <!-- <?php //include("signin_user.php")?> -->
        </form>

        <div class="text-center small" style="color: #67428B;"> Don't have an account? <a href="signup.php">Create one</a></div>
    </div>
   </body>
</html>