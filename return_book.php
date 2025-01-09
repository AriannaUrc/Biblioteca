<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== 'user') {
    header("Location: login.php");
    exit;
}

if (isset($_POST['lending_id'])) {
    $lending_id = (int)$_POST['lending_id'];

    // Mark the book as returned
    $returnDate = date('Y-m-d');
    $returnQuery = "UPDATE lending SET return_date = '$returnDate' WHERE lending_id = $lending_id";
    if (mysqli_query($conn, $returnQuery)) {
        // Update book availability
        $lendingQuery = "SELECT book_id FROM lending WHERE lending_id = $lending_id";
        $lendingResult = mysqli_query($conn, $lendingQuery);
        $lendingRow = mysqli_fetch_assoc($lendingResult);
        $book_id = $lendingRow['book_id'];

        $updateBookQuery = "UPDATE books SET available = 1 WHERE book_id = $book_id";
        mysqli_query($conn, $updateBookQuery);

        echo "Book returned successfully!";
    } else {
        echo "Error while returning the book.";
    }
}

header("Location: index.php");
?>
