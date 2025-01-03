<?php
session_start();
include 'db_connection.php';

// Logout functionality
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit;
}


if(!isset($_SESSION["role"])){
    header("Location: login.php");
    exit;
}


if ($_SESSION["role"] == 'admin') {
    ?>
    .Admin account<br><br>


<?php
    // Pagination for user transactions
    $limit = 5;
    $page = isset($_GET['page']) ? $_GET['page'] : 1;
    $offset = ($page - 1) * $limit;

    // Fetch users
    $usersQuery = "SELECT * FROM users";
    $usersResult = mysqli_query($conn, $usersQuery);

    // Define $user_id only if the user clicks on a specific user
    if (isset($_GET['view_user_books'])) {
        $user_id = $_GET['view_user_books'];

        // Fetch user transactions for admin
        $transactionsQuery = "SELECT * FROM lending 
                            JOIN books ON lending.book_id = books.book_id 
                            WHERE lending.user_id = $user_id 
                            ORDER BY lending.lend_date DESC 
                            LIMIT $limit OFFSET $offset";
        $transactionsResult = mysqli_query($conn, $transactionsQuery);

        // Pagination for user transactions
        $totalTransactionsQuery = "SELECT COUNT(*) as total FROM lending WHERE user_id = $user_id";
        $totalTransactionsResult = mysqli_query($conn, $totalTransactionsQuery);
        $totalTransactionsRow = mysqli_fetch_assoc($totalTransactionsResult);
        $totalTransactions = $totalTransactionsRow['total'];
        $totalPages = ceil($totalTransactions / $limit);
    }

    // Start the page structure with Bootstrap container
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Panel</title>
        <!-- Bootstrap CSS -->
        <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>';

    // Start the page layout with Bootstrap container
    echo '<div class="container">';
    echo '<div class="row">';

    // Left Panel: List of Users
    echo '<div class="col-md-3 mb-4">';
    echo "<h4>Users</h4>";
    while ($user = mysqli_fetch_assoc($usersResult)) {
        echo "<a href='index.php?view_user_books=" . $user['user_id'] . "' class='btn btn-link'>" . $user['username'] . "</a><br>";
    }
    echo "</div>";

    // Right Panel: User Transactions & Admin Options
    echo '<div class="col-md-9">';
    if (isset($user_id)) { // Ensure that user_id is set before proceeding
        // Fetch user details
        $userQuery = "SELECT * FROM users WHERE user_id = $user_id";
        $userDetailsResult = mysqli_query($conn, $userQuery);
        $userDetails = mysqli_fetch_assoc($userDetailsResult);
        echo "<h3>User: " . $userDetails['username'] . "</h3>";

        // Check if user is suspended
        $suspensionQuery = "SELECT * FROM suspensions WHERE user_id = $user_id AND suspended_until > NOW()";
        $suspensionResult = mysqli_query($conn, $suspensionQuery);
        $isSuspended = mysqli_num_rows($suspensionResult) > 0 ? "Suspended" : "Active";
        echo "<p>Status: " . $isSuspended . "</p>";

        // Display transactions
        echo "<h4>Transactions</h4>";
        if (mysqli_num_rows($transactionsResult) > 0) {
            while ($transaction = mysqli_fetch_assoc($transactionsResult)) {
                echo "<strong>Book:</strong> " . $transaction['title'] . "<br>";
                echo "<strong>Lend Date:</strong> " . $transaction['lend_date'] . "<br>";
                echo "<strong>Return Date:</strong> " . ($transaction['return_date'] ? $transaction['return_date'] : "Not yet returned") . "<br><br>";
            }
        } else {
            echo "<p>No transactions found for this user.</p>";
        }

        // Pagination buttons
        echo "<div class='mt-3'>";
        if ($page > 1) {
            echo "<a href='index.php?view_user_books=$user_id&page=" . ($page - 1) . "' class='btn btn-secondary'>Previous</a> ";
        }
        if ($page < $totalPages) {
            echo "<a href='index.php?view_user_books=$user_id&page=" . ($page + 1) . "' class='btn btn-secondary'>Next</a>";
        }
        echo "</div>";
    }

   

    // End the right panel
    echo "</div>"; // End of right panel
    echo "</div>"; // End of row

     // Add new book form
     echo "<h4 class='mt-5'>Add New Book</h4>";
     echo '
     <form method="POST" class="form-inline">
         <input type="text" name="book_title" placeholder="Book Title" required class="form-control mr-2">
         <select name="author_id" class="form-control mr-2">
             <option value="">Select Author</option>';
     $authors = mysqli_query($conn, "SELECT * FROM authors");
     while ($author = mysqli_fetch_assoc($authors)) {
         echo '<option value="' . $author['author_id'] . '">' . $author['name'] . '</option>';
     }
     echo '</select>
         <select name="category_id" class="form-control mr-2">
             <option value="">Select Genre</option>';
     $categories = mysqli_query($conn, "SELECT * FROM categories");
     while ($category = mysqli_fetch_assoc($categories)) {
         echo '<option value="' . $category['category_id'] . '">' . $category['name'] . '</option>';
     }
     echo '</select>
         <button type="submit" name="add_book" class="btn btn-primary">Add Book</button>
     </form>';
 
     // Add new category form
     echo "<h4 class='mt-5'>Add New Category</h4>";
     echo '
     <form method="POST" class="form-inline">
         <input type="text" name="category_name" placeholder="Category Name" required class="form-control mr-2">
         <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
     </form>
     
     <h4 class="mt-5">Add New Author</h4>
    
     <form method="POST" class="form-inline">
         <input type="text" name="author_name" placeholder="Author Name" required class="form-control mr-2">
         <button type="submit" name="add_author" class="btn btn-primary">Add Author</button>
     </form>
     ';
 
     // Add new book functionality
     if (isset($_POST['add_book'])) {
         $title = $_POST['book_title'];
         $author_id = $_POST['author_id'];
         $category_id = $_POST['category_id'];
         $query = "INSERT INTO books (title, author_id, category_id) VALUES ('$title', '$author_id', '$category_id')";
         if (mysqli_query($conn, $query)) {
             echo "<div class='alert alert-success mt-3'>Book added successfully.</div>";
         } else {
             echo "<div class='alert alert-danger mt-3'>Error: " . mysqli_error($conn) . "</div>";
         }
     }
 
     // Add new category functionality
     if (isset($_POST['add_category'])) {
         $category_name = $_POST['category_name'];
         $query = "INSERT INTO categories (name) VALUES ('$category_name')";
         if (mysqli_query($conn, $query)) {
             echo "<div class='alert alert-success mt-3'>Category added successfully.</div>";
         } else {
             echo "<div class='alert alert-danger mt-3'>Error: " . mysqli_error($conn) . "</div>";
         }
     }

     // Add new author functionality
    if (isset($_POST['add_author'])) {
        $author_name = $_POST['author_name'];
        $query = "INSERT INTO authors (name) VALUES ('$author_name')";
        if (mysqli_query($conn, $query)) {
            echo "<div class='alert alert-success mt-3'>Author added successfully.</div>";
        } else {
            echo "<div class='alert alert-danger mt-3'>Error: " . mysqli_error($conn) . "</div>";
        }
    }

     ?>

    
    <br><br>
    <form method="POST">
        <button type="submit" name="logout" class="btn btn-danger mt-3">Logout</button>
    </form>
    

    </div> 

    <?php // Bootstrap JS (Optional for things like modals, dropdowns, etc)
    echo '<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>';
    echo '<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>';
    echo '<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>';

    echo "</body></html>";
}






if ($_SESSION['role'] == 'user') {
    $user_id = $_SESSION['user_id'];

    // User requests a book
    if (isset($_POST['request_book'])) {
        $book_id = $_POST['book_id'];

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

    // User returns a book
    if (isset($_POST['return_book'])) {
        $lending_id = $_POST['lending_id'];

        // Update the lending status to return the book
        $returnQuery = "UPDATE lending SET return_date = NOW() WHERE lending_id = $lending_id";
        if (mysqli_query($conn, $returnQuery)) {
            // Update book availability after return
            $bookIdQuery = "SELECT book_id FROM lending WHERE lending_id = $lending_id";
            $bookResult = mysqli_query($conn, $bookIdQuery);
            $book = mysqli_fetch_assoc($bookResult);
            $book_id = $book['book_id'];
            $updateBookQuery = "UPDATE books SET available = 1 WHERE book_id = $book_id";
            mysqli_query($conn, $updateBookQuery);
            echo "Book returned successfully.<br>";
        } else {
            echo "Failed to return the book.<br>";
        }
    }

    // Get all genres from the database
    $genres_query = "SELECT DISTINCT categories.name AS genre FROM books 
    JOIN categories ON books.category_id = categories.category_id";
    $genres_result = mysqli_query($conn, $genres_query);

    // Handle form submission for filtering books based on selected genres
    $selected_genres = [];
    if (isset($_POST['filter'])) {
        $selected_genres = $_POST['genres'] ?? []; // Get selected genres
    }

    // Handle search query
    $search_query = '';
    if (isset($_POST['search_books']) && !empty($_POST['search_books'])) {
        $search_query = mysqli_real_escape_string($conn, $_POST['search_books']);
    }

    // Pagination logic for displaying books (5 books per page)
    $limit = 5;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;

    // Filtered query based on genres and search
    $where_clauses = [];
    if ($search_query) {
        $where_clauses[] = "title LIKE '%$search_query%'";
    }
    if (!empty($selected_genres)) {
        $genres_list = implode("', '", $selected_genres);
        $where_clauses[] = "category_id IN (SELECT category_id FROM categories WHERE name IN ('$genres_list'))";
    }

    $where_sql = '';
    if (!empty($where_clauses)) {
        $where_sql = "WHERE " . implode(" AND ", $where_clauses);
    }

    // Get the filtered books with pagination
    $books_query = "SELECT * FROM books $where_sql LIMIT $limit OFFSET $offset";
    $books_result = mysqli_query($conn, $books_query);

    // Get the total number of books for pagination
    $total_books_query = "SELECT COUNT(*) AS total FROM books $where_sql";
    $total_books_result = mysqli_query($conn, $total_books_query);
    $total_books = mysqli_fetch_assoc($total_books_result)['total'];
    $total_pages = ceil($total_books / $limit);

    // Get the list of books requested by the user (only those that haven't been returned yet)
    $requested_books_query = "SELECT b.title, l.lending_id FROM lending l 
                               JOIN books b ON l.book_id = b.book_id 
                               WHERE l.user_id = $user_id AND l.return_date IS NULL";
    $requested_books_result = mysqli_query($conn, $requested_books_query);

    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>User Dashboard</title>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    </head>
    <body>
    <div class="container">
        <div class="row">
            <!-- Left Panel: Genres Filter -->
            <div class="col-md-3">
                <h3>Filter by Genre</h3>
                <form method="POST">
                    <ul class="list-group">
                        <?php while ($genre = mysqli_fetch_assoc($genres_result)) : ?>
                            <li class="list-group-item">
                                <label>
                                    <input type="checkbox" name="genres[]" value="<?= $genre['genre']; ?>" 
                                           <?= in_array($genre['genre'], $selected_genres) ? 'checked' : ''; ?>>
                                    <?= $genre['genre']; ?>
                                </label>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                    <button type="submit" name="filter" class="btn btn-primary mt-2">Filter</button>
                </form>
            </div>

            <!-- Right Panel: Books List -->
            <div class="col-md-9">
                <h3>User Dashboard</h3>

                <!-- Search Bar -->
                <h4>Search for Books to Request</h4>
                <form method="POST">
                    <input type="text" name="search_books" placeholder="Search for books" value="<?= $search_query; ?>" class="form-control">
                    <button type="submit" class="btn btn-primary mt-2">Search</button>
                </form>

                <h4>Available Books</h4>
                <div>
                    <?php if (mysqli_num_rows($books_result) > 0): ?>
                        <?php while ($book = mysqli_fetch_assoc($books_result)): ?>
                            <div class="d-flex justify-content-between">
                                <strong><?= $book['title']; ?></strong>
                                <?php if ($book['available'] == 1): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="book_id" value="<?= $book['book_id']; ?>">
                                        <button type="submit" name="request_book" class="btn btn-success btn-sm">Request Book</button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-danger btn-sm" disabled>Booked Out</button>
                                <?php endif; ?>
                            </div>
                            <hr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No books found</p>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <div class="mt-3">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1; ?>" class="btn btn-secondary">Previous</a>
                    <?php endif; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1; ?>" class="btn btn-secondary">Next</a>
                    <?php endif; ?>
                </div>

                <hr>

                <h4>Your Requested Books (Not Returned Yet)</h4>
                <div>
                    <?php if (mysqli_num_rows($requested_books_result) > 0): ?>
                        <?php while ($requested_book = mysqli_fetch_assoc($requested_books_result)): ?>
                            <div class="d-flex justify-content-between">
                                <strong><?= $requested_book['title']; ?></strong>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="lending_id" value="<?= $requested_book['lending_id']; ?>">
                                    <button type="submit" name="return_book" class="btn btn-warning btn-sm">Return</button>
                                </form>
                            </div>
                            <hr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>You have no books that need to be returned.</p>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>

    <!-- Logout Form -->
    <form method="POST">
        <button type="submit" name="logout" class="btn btn-danger mt-3">Logout</button>
    </form>
    </body>
    </html>

<?php 
}
