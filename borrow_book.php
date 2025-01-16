<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== 'user') {
    header("Location: login.php");
    exit;
}

if (isset($_GET['book_id'])) {
    $book_id = (int)$_GET['book_id'];

    // Check if the book is available
    $bookQuery = "SELECT * FROM books WHERE book_id = $book_id";
    $bookResult = mysqli_query($conn, $bookQuery);
    $book = mysqli_fetch_assoc($bookResult);

    if ($book && $book['available']) {
        // Check if the user is suspended
        $user_id = $_SESSION['user_id'];
        $suspensionQuery = "SELECT * FROM suspensions WHERE user_id = $user_id AND suspended_until > NOW()";
        $suspensionResult = mysqli_query($conn, $suspensionQuery);
        if (mysqli_num_rows($suspensionResult) > 0) {
            echo "You are suspended and cannot borrow books at the moment.";
            exit;
        }

        // Borrow the book
        $lendDate = date('Y-m-d');
        $returnDate = NULL;
        $insertLendingQuery = "INSERT INTO lending (user_id, book_id, lend_date) VALUES ($user_id, $book_id, '$lendDate')";
        
        if (mysqli_query($conn, $insertLendingQuery)) {
            // Update the book to mark it as unavailable
            $updateBookQuery = "UPDATE books SET available = 0 WHERE book_id = $book_id";
            mysqli_query($conn, $updateBookQuery);
            echo "Book borrowed successfully!";
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    } else {
        echo "Sorry, the book is unavailable.";
    }
}
header("Location: index.php");
?>
