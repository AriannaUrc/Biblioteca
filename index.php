<?php
session_start();
include 'db_connection.php';

// Hardcoded admin credentials
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'adminpass');

// Logout functionality
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit;
}

// Admin login check
if (isset($_POST['login'])) {
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
        } else {
            echo "Error: " . mysqli_error($conn) . "<br>";
        }
    }
}

// Admin can add new books, authors, and categories
if (isset($_POST['add_book']) && $_SESSION['role'] == 'admin') {
    $title = $_POST['book_title'];
    $author_id = $_POST['author_id'];
    $category_id = $_POST['category_id'];

    $query = "INSERT INTO books (title, author_id, category_id) VALUES ('$title', '$author_id', '$category_id')";
    if (mysqli_query($conn, $query)) {
        echo "Book added successfully.<br>";
    } else {
        echo "Error: " . mysqli_error($conn) . "<br>";
    }
}

if (isset($_POST['add_author']) && $_SESSION['role'] == 'admin') {
    $author_name = $_POST['author_name'];

    $query = "INSERT INTO authors (name) VALUES ('$author_name')";
    if (mysqli_query($conn, $query)) {
        echo "Author added successfully.<br>";
    } else {
        echo "Error: " . mysqli_error($conn) . "<br>";
    }
}

if (isset($_POST['add_category']) && $_SESSION['role'] == 'admin') {
    $category_name = $_POST['category_name'];

    $query = "INSERT INTO categories (name) VALUES ('$category_name')";
    if (mysqli_query($conn, $query)) {
        echo "Category added successfully.<br>";
    } else {
        echo "Error: " . mysqli_error($conn) . "<br>";
    }
}

// User requests a book with a search filter
if (isset($_POST['request_book']) && $_SESSION['role'] == 'user') {
    $book_id = $_POST['book_id'];
    $user_id = $_SESSION['user_id'];

    // Check if the user is suspended
    $suspensionQuery = "SELECT * FROM suspensions WHERE user_id = $user_id AND suspended_until > NOW()";
    $suspensionResult = mysqli_query($conn, $suspensionQuery);
    if (mysqli_num_rows($suspensionResult) > 0) {
        echo "Your account is suspended. You cannot borrow books.<br>";
    } else {
        // Check if the book is available
        $bookQuery = "SELECT * FROM books WHERE book_id = $book_id AND available = 1";
        $bookResult = mysqli_query($conn, $bookQuery);
        if (mysqli_num_rows($bookResult) > 0) {
            // Lend the book
            $lendDate = date('Y-m-d');
            $lendQuery = "INSERT INTO lending (user_id, book_id, lend_date) VALUES ($user_id, $book_id, '$lendDate')";
            mysqli_query($conn, $lendQuery);

            // Update book availability
            $updateBookQuery = "UPDATE books SET available = 0 WHERE book_id = $book_id";
            mysqli_query($conn, $updateBookQuery);

            echo "Book requested successfully.<br>";
        } else {
            echo "This book is not available.<br>";
        }
    }
}

// User returns a book with a search filter
if (isset($_POST['return_book']) && $_SESSION['role'] == 'user') {
    $lending_id = $_POST['lending_id'];
    $user_id = $_SESSION['user_id'];
    $return_date = date('Y-m-d');

    // Update lending record with return date
    $query = "UPDATE lending SET return_date = '$return_date' WHERE lending_id = $lending_id AND user_id = $user_id";
    if (mysqli_query($conn, $query)) {
        // Get the lending record
        $lendQuery = "SELECT * FROM lending WHERE lending_id = $lending_id";
        $result = mysqli_query($conn, $lendQuery);
        $lendRecord = mysqli_fetch_assoc($result);

        // Calculate overdue days if any
        $lend_date = new DateTime($lendRecord['lend_date']);
        $return_date_obj = new DateTime($return_date);
        $interval = $lend_date->diff($return_date_obj);
        $daysOverdue = $interval->days;

        // If overdue more than 30 days, suspend the user
        if ($daysOverdue > 30) {
            $suspend_days = $daysOverdue * 2;
            $suspend_query = "INSERT INTO suspensions (user_id, suspended_until) VALUES ('{$lendRecord['user_id']}', DATE_ADD(CURRENT_DATE, INTERVAL $suspend_days DAY))";
            mysqli_query($conn, $suspend_query);
            echo "Your account has been suspended for $suspend_days days due to overdue books.<br>";
        }

        // Mark the book as available
        $updateBookQuery = "UPDATE books SET available = 1 WHERE book_id = {$lendRecord['book_id']}";
        mysqli_query($conn, $updateBookQuery);

        echo "Book returned successfully.<br>";
    } else {
        echo "Error returning book.<br>";
    }
}

// Search for available books
$search_query = '';
if (isset($_POST['search_books'])) {
    $search_query = $_POST['search_books'];
    $bookQuery = "SELECT * FROM books WHERE title LIKE '%$search_query%' AND available = 1";
} else {
    $bookQuery = "SELECT * FROM books WHERE available = 1";
}

$bookResult = mysqli_query($conn, $bookQuery);

// Display the search forms and results
if (!isset($_SESSION['role'])) {
    // Login or registration form
    echo '
    <h3>Register</h3>
    <form method="POST">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="register">Register</button>
    </form>
    <h3>Login</h3>
    <form method="POST">
        <input type="text" name="login_username" placeholder="Username" required>
        <input type="password" name="login_password" placeholder="Password" required>
        <button type="submit" name="login">Login</button>
    </form>';
} else {
    // Admin or user dashboard
    echo "Logged in as " . $_SESSION['username'] . " (" . $_SESSION['role'] . ")<br>";

    // Admin dashboard
    if ($_SESSION['role'] == 'admin') {
        echo '<h3>Admin Dashboard</h3>';
        // Other admin functionalities

    }

    // User dashboard
    if ($_SESSION['role'] == 'user') {
        echo '<h3>User Dashboard</h3>';

        // Search and request book form
        echo '
        <h4>Search for Books to Request</h4>
        <form method="POST">
            <input type="text" name="search_books" placeholder="Search for books" value="' . htmlspecialchars($search_query) . '">
            <button type="submit">Search</button>
        </form>';
        
        // Display available books
        while ($book = mysqli_fetch_assoc($bookResult)) {
            echo "Book: " . $book['title'] . "<br>";
            echo '<form method="POST">
                    <input type="hidden" name="book_id" value="' . $book['book_id'] . '">
                    <button type="submit" name="request_book">Request Book</button>
                  </form>';
        }

        // Search and return book form
        echo '
        <h4>Search for Books to Return</h4>
        <form method="POST">
            <input type="text" name="search_books" placeholder="Search for borrowed books" value="' . htmlspecialchars($search_query) . '">
            <button type="submit">Search</button>
        </form>';

        // Display books user has borrowed
        $user_books_query = "SELECT * FROM lending JOIN books ON lending.book_id = books.book_id WHERE lending.user_id = {$_SESSION['user_id']} AND lending.return_date IS NULL";
        $user_books_result = mysqli_query($conn, $user_books_query);

        while ($user_book = mysqli_fetch_assoc($user_books_result)) {
            echo "Book: " . $user_book['title'] . "<br>";
            echo '<form method="POST">
                    <input type="hidden" name="lending_id" value="' . $user_book['lending_id'] . '">
                    <button type="submit" name="return_book">Return Book</button>
                  </form>';
        }
    }

    echo '
    <form method="POST">
        <button type="submit" name="logout">Logout</button>
    </form>';
}
?>
