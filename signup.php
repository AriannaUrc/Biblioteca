<?php 
session_start();
include 'db_connection.php';

// User registration (for normal users)
if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'user'; // Default role for users

    // Check if the username already exists
    $query = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // If a row is found with the same username, show an error message
    if ($result->num_rows > 0) {
        echo "There is already a user with this name.";
    } else {
        $query = "INSERT INTO users (username, password, role) VALUES ('$username', '$password', '$role')";
        if (mysqli_query($conn, $query)) {
            echo "User registered successfully.<br>";
            header("Location: login.php");
        } else {
            echo "Error: " . mysqli_error($conn) . "<br>";
        }
    }
}

?>
<!doctype html>
<html lang="en">
  <head>
    <title>Create new account</title>
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
    <link rel="stylesheet" type="text/css" href="css/signup.css">

    <div class="signup-form">
        <form action="" method="post">
            <div class="form-header">
                <h2>Sign up</h2>
                <p>Scegli la tua password e il tuo nome utente!</p>
            </div>
            <div class="form-group">
                <label for="user_name">Username</label>
                <input type="text" class="form-control" name="username" placeholder="name" autocomplete="off" Required>
            </div>

            <div class="form-group">
                <label for="user_pass">Password</label>
                <input type="password" class="form-control" name="password" placeholder="Password" autocomplete="off" Required>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block btn-lg" name="register">Register</button>
            </div>
        </form>

        <div class="text-center small" style="color: #67428B;"> Already have an account? <a href="login.php">Sign in</a></div>
    </div>
   </body>
</html>