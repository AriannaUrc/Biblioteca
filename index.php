<?php
session_start();
include 'db_connection.php';

// Handle pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 6;
$offset = ($page - 1) * $limit;

if (isset($_POST["logout"])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION["role"])) {
    header("Location: login.php");
    exit;
}

echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css"> <!-- Custom styles -->
</head>
<body>';

echo '<div class="container mt-4">';

if (isset($_POST['search_book_to_update'])) {
    // Get the book title from the search
    $book_title_to_update = mysqli_real_escape_string($conn, $_POST['book_title_to_update']);

    // Fetch book details from the database
    $searchQuery = "SELECT * FROM books WHERE title LIKE '%$book_title_to_update%'";
    $searchResult = mysqli_query($conn, $searchQuery);

    if (mysqli_num_rows($searchResult) > 0) {
        // Fetch the first matching book
        $book = mysqli_fetch_assoc($searchResult);
        
        // Store the book details in session
        $_SESSION['book_to_update'] = $book;

        // Display book details in an editable form
        echo '<h4>Edit Book: ' . $book['title'] . '</h4>';
        echo '<form method="POST" enctype="multipart/form-data" class="form-inline mb-3">';
        echo '<input type="text" name="updated_book_title" value="' . $book['title'] . '" class="form-control mr-2" required>';
        
        echo '<select name="updated_author_id" class="form-control mr-2" required>';
        echo '<option value="">Select Author</option>';
        $authorsQuery = "SELECT * FROM authors";
        $authorsResult = mysqli_query($conn, $authorsQuery);
        while ($author = mysqli_fetch_assoc($authorsResult)) {
            $selected = ($author['author_id'] == $book['author_id']) ? 'selected' : '';
            echo "<option value='" . $author['author_id'] . "' $selected>" . $author['name'] . "</option>";
        }
        echo '</select>';
        
        echo '<select name="updated_category_id" class="form-control mr-2" required>';
        echo '<option value="">Select Category</option>';
        $categoriesQuery = "SELECT * FROM categories";
        $categoriesResult = mysqli_query($conn, $categoriesQuery);
        while ($category = mysqli_fetch_assoc($categoriesResult)) {
            $selected = ($category['category_id'] == $book['category_id']) ? 'selected' : '';
            echo "<option value='" . $category['category_id'] . "' $selected>" . $category['name'] . "</option>";
        }
        echo '</select>';

        // Show the existing image (or default image if none exists)
        if ($book['image_cnt']) {
            echo '<img src="data:image/jpeg;base64,' . $book['image_cnt'] . '" class="img-thumbnail mb-2" style="width: 100px; height: 100px;" />';
        } else {
            echo '<img src="img/books/default.jpg" class="img-thumbnail mb-2" style="width: 100px; height: 100px;" />';
        }

        echo '<input type="file" name="updated_image" class="form-control mr-2">';
        echo '<button type="submit" name="update_book" class="btn btn-primary">Update Book</button>';
        echo '</form>';
    } else {
        echo "<p>No book found with the title '$book_title_to_update'.</p>";
    }
}


if ($_SESSION["role"] == 'admin' && isset($_POST['update_book'])) {
    // Retrieve the book details from session
    if (isset($_SESSION['book_to_update'])) {
        $book = $_SESSION['book_to_update'];

        // Get the updated book details
        $updated_book_title = mysqli_real_escape_string($conn, $_POST['updated_book_title']);
        $updated_author_id = (int)$_POST['updated_author_id'];
        $updated_category_id = (int)$_POST['updated_category_id'];

        // Handle the image upload and save as base64 (if new image is uploaded)
        $updated_image_content = NULL; // Default if no image is uploaded
        if (isset($_FILES['updated_image']) && $_FILES['updated_image']['error'] == 0) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            if (in_array($_FILES['updated_image']['type'], $allowedTypes)) {
                // Convert the image to base64
                $imageData = file_get_contents($_FILES['updated_image']['tmp_name']);
                $updated_image_content = base64_encode($imageData); // Base64 image content for database
            } else {
                echo "Invalid image file type. Only JPEG, PNG, and JPG are allowed.";
            }
        }

        // Update the book details in the database
        $updateQuery = "UPDATE books SET title = '$updated_book_title', author_id = $updated_author_id, category_id = $updated_category_id";
        if ($updated_image_content) {
            $updateQuery .= ", image_cnt = '$updated_image_content'";
        }
        $updateQuery .= " WHERE book_id = " . $book['book_id']; // Assuming we know the book_id from the session

        if (mysqli_query($conn, $updateQuery)) {
            echo "Book updated successfully!";
        } else {
            echo "Error: " . mysqli_error($conn);
        }

        // Clear the session data after the update
        unset($_SESSION['book_to_update']);
    } else {
        echo "Error: Book not found in session.";
    }
}





if ($_SESSION["role"] == 'admin') {

    echo '<h4>Update Book</h4>';
    echo '<form method="POST" class="form-inline mb-3">';
    echo '<input type="text" name="book_title_to_update" placeholder="Enter Book Title to Modify" class="form-control mr-2" required>';
    echo '<button type="submit" name="search_book_to_update" class="btn btn-warning">Search</button>';
    echo '</form>';

    // Add Book Logic
    if (isset($_POST['add_book'])) {
        // Get the form data
        $title = mysqli_real_escape_string($conn, $_POST['book_title']);
        $author_id = (int)$_POST['author_id'];
        $category_id = (int)$_POST['category_id'];
        
        // Handle image upload and save as base64
        $imagePath = NULL; // Default if no image is uploaded
        $imageContent = NULL; // Default if no base64 image is uploaded

        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            // Validate the uploaded image (optional checks like file type, size)
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            if (in_array($_FILES['image']['type'], $allowedTypes)) {
                // Convert image to base64
                $imageData = file_get_contents($_FILES['image']['tmp_name']);
                $imageContent = base64_encode($imageData); // Base64 content for database

                // Save the image file path on the server (this is what goes in 'image' column)
                $imagePath = 'img/books/' . basename($_FILES['image']['name']);
                move_uploaded_file($_FILES['image']['tmp_name'], $imagePath); // Save image to the server
            } else {
                echo "Invalid file type. Only JPEG, PNG, and JPG are allowed.";
            }
        }

        // Insert the book into the database (including base64 encoded image in `image_cnt` and image path in `image`)
        $query = "INSERT INTO books (title, author_id, category_id, image_cnt) 
                VALUES ('$title', $author_id, $category_id, '$imageContent')";
        if (mysqli_query($conn, $query)) {
            echo "Book added successfully!";
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    }
?>

    
    <div class="row">
        <!-- Left Panel: List of Users -->
        <div class="col-md-3">
            <h4 class="text-center">Users</h4>
            <div class="list-group">
                <?php
                $usersQuery = "SELECT * FROM users";
                $usersResult = mysqli_query($conn, $usersQuery);
                while ($user = mysqli_fetch_assoc($usersResult)) {
                    echo "<a href='index.php?view_user_books=" . $user['user_id'] . "' class='list-group-item list-group-item-action'>" . $user['username'] . "</a>";
                }
                ?>
            </div>
        </div>

        <!-- Right Panel: User Transactions & Admin Options -->
        <div class="col-md-9">
            <?php
            if (isset($_GET['view_user_books'])) {
                $user_id = $_GET['view_user_books'];

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

                // Fetch user transactions with pagination
                $transactionsQuery = "SELECT * FROM lending JOIN books ON lending.book_id = books.book_id WHERE lending.user_id = $user_id ORDER BY lending.lend_date DESC LIMIT $limit OFFSET $offset";
                $transactionsResult = mysqli_query($conn, $transactionsQuery);
                if (mysqli_num_rows($transactionsResult) > 0) {
                    echo "<h4>Transactions</h4>";
                    while ($transaction = mysqli_fetch_assoc($transactionsResult)) {
                        echo "<strong>Book:</strong> " . $transaction['title'] . "<br>";
                        echo "<strong>Lend Date:</strong> " . $transaction['lend_date'] . "<br>";
                        echo "<strong>Return Date:</strong> " . ($transaction['return_date'] ? $transaction['return_date'] : "Not yet returned") . "<br><hr>";
                    }
                } else {
                    echo "<p>No transactions found for this user.</p>";
                }

                // Pagination for transactions
                $totalTransactionsQuery = "SELECT COUNT(*) as total FROM lending WHERE user_id = $user_id";
                $totalTransactionsResult = mysqli_query($conn, $totalTransactionsQuery);
                $totalTransactionsRow = mysqli_fetch_assoc($totalTransactionsResult);
                $totalTransactions = $totalTransactionsRow['total'];
                $totalPages = ceil($totalTransactions / $limit);  // Calculate total pages
                ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li class="page-item"><a class="page-link" href="?view_user_books=<?php echo $user_id; ?>&page=<?php echo $page - 1; ?>">Previous</a></li>
                        <?php endif; ?>
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item"><a class="page-link" href="?view_user_books=<?php echo $user_id; ?>&page=<?php echo $page + 1; ?>">Next</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php } ?>
            <!-- Add New Book, Category, and Author Form -->
            <div class="col-mt-4">
                <h4>Add New Book</h4>
                <form method="POST" enctype="multipart/form-data" class="form-inline mb-3">
                    <input type="text" name="book_title" placeholder="Book Title" class="form-control mr-2" required>
                    
                    <select name="author_id" class="form-control mr-2" required>
                        <option value="">Select Author</option>
                        <?php
                        $authors = mysqli_query($conn, "SELECT * FROM authors");
                        while ($author = mysqli_fetch_assoc($authors)) {
                            echo "<option value='" . $author['author_id'] . "'>" . $author['name'] . "</option>";
                        }
                        ?>
                    </select>

                    <select name="category_id" class="form-control mr-2" required>
                        <option value="">Select Category</option>
                        <?php
                        $categories = mysqli_query($conn, "SELECT * FROM categories");
                        while ($category = mysqli_fetch_assoc($categories)) {
                            echo "<option value='" . $category['category_id'] . "'>" . $category['name'] . "</option>";
                        }
                        ?>
                    </select>

                    <!-- Add Image Upload -->
                    <input type="file" name="image" class="form-control mr-2">
                    
                    <button type="submit" name="add_book" class="btn btn-primary">Add Book</button>
                </form>



                <h4>Add New Category</h4>
                <form method="POST" class="form-inline mb-3">
                    <input type="text" name="category_name" placeholder="Category Name" class="form-control mr-2" required>
                    <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
                </form>

                <h4>Add New Author</h4>
                <form method="POST" class="form-inline mb-3">
                    <input type="text" name="author_name" placeholder="Author Name" class="form-control mr-2" required>
                    <button type="submit" name="add_author" class="btn btn-primary">Add Author</button>
                </form>
        </div>
    </div>
<?php
} elseif ($_SESSION["role"] == 'user') {
    // User panel content (book browsing, searching, borrowing)
?>
    <h3>Welcome, <?php echo $_SESSION['username']; ?>!</h3>

    <form method="GET" class="col-md-6 mb-4">
        <div class="form-row">
            <div class="col-md-8">
                <input type="text" name="search_books" class="form-control" placeholder="Search for books">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-success">Search</button>
            </div>
        </div>
    </form>

    <!-- Genre Filter Form -->
    <form method="GET" class="col-mb-4">
        <div class="form-row">
            <div class="col-md-6">
                <select name="genre" class="form-control">
                    <option value="">Select Genre</option>
                    <?php
                    $categoriesQuery = "SELECT * FROM categories";
                    $categoriesResult = mysqli_query($conn, $categoriesQuery);
                    while ($category = mysqli_fetch_assoc($categoriesResult)) {
                        $selected = (isset($_GET['genre']) && $_GET['genre'] == $category['category_id']) ? 'selected' : '';
                        echo "<option value='" . $category['category_id'] . "' $selected>" . $category['name'] . "</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">Filter by Genre</button>
            </div>
        </div>
    </form>

    <?php
    // Book List Query (with optional search and genre filter)
    $genreFilter = isset($_GET['genre']) ? "AND category_id = " . (int)$_GET['genre'] : '';
    //echo $genreFilter;
    $searchQuery = isset($_GET['search_books']) ? "AND title LIKE '%" . mysqli_real_escape_string($conn, $_GET['search_books']) . "%'" : '';

    if(isset($_GET['genre'])){
        if((int)$_GET['genre']!=0)
        $booksQuery = "SELECT * FROM books WHERE 1 $genreFilter $searchQuery LIMIT $limit OFFSET $offset";
        else
        $booksQuery = "SELECT * FROM books WHERE 1 $searchQuery LIMIT $limit OFFSET $offset";
    }
    else{
        $booksQuery = "SELECT * FROM books WHERE 1 $searchQuery LIMIT $limit OFFSET $offset";
    }
    

    $booksResult = mysqli_query($conn, $booksQuery);
    ?>

    <h4>Books</h4>
    <div class="row">
    <?php
    while ($book = mysqli_fetch_assoc($booksResult)) {
        // Check if the book is available
        $available = $book['available'] ? true : false;

        // Check if image content is available, if not use default image
        if ($book['image_cnt']) {
            // Create the data URI for base64 image
            $imagePath = 'data:image/jpeg;base64,' . $book['image_cnt'];  // Assuming the image is JPEG
        } else {
            $imagePath = 'img/books/default.jpg';  // Fallback default image if no image found
        }
    ?>

    <div class="col-md-4 mb-4">
        <div class="card" style="height: 100%; border: 1px solid #ddd;">
            <!-- Larger Card Image -->
            <img src="<?php echo $imagePath; ?>" class="card-img-top" alt="<?php echo $book['title']; ?>" style="width: 100%; object-fit: cover;">
            
            <!-- Card Body -->
            <div class="card-body">
                <h5 class="card-title" style="font-size: 1.25rem;"><?php echo $book['title']; ?></h5>
                <p class="card-text" style="font-size: 1rem;">Genre: <?php 

                $categoriesQuery = "SELECT * FROM categories WHERE category_id = ".$book['category_id'];
                $categoriesResult = mysqli_query($conn, $categoriesQuery);

                while ($category = mysqli_fetch_assoc($categoriesResult)) {
                    echo $category['name'];
                }

                //echo $book['category_id']; 
                
                ?></p>
                
                <!-- Display Borrow Button if Book is Available -->
                <?php if ($available): ?>
                    <a href="borrow_book.php?book_id=<?php echo $book['book_id']; ?>" class="btn btn-primary" style="font-size: 1rem;">Borrow</a>
                <?php else: ?>
                    <button class="btn btn-secondary" style="font-size: 1rem;" disabled>Unavailable</button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php
    }
    ?>
    </div>

    <nav>
        <ul class="pagination">
            <?php
                $totalBooksQuery = "SELECT COUNT(*) as total FROM books WHERE 1 $genreFilter $searchQuery";
                $totalBooksResult = mysqli_query($conn, $totalBooksQuery);
                $totalBooksRow = mysqli_fetch_assoc($totalBooksResult);
                $totalBooks = $totalBooksRow['total'];
                $totalPages = ceil($totalBooks / $limit);  // Calculate total pages
                ?>
                <?php if ($page > 1): ?>
                    <li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a></li>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                    <li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a></li>
                <?php endif; ?>
        </ul>
    </nav>

    <?php
    $username = $_SESSION['username']; // Fetch the username from session
    //echo "Username: " . $username . "<br>"; // Display the username

    // Fetch user details using username
    $userQuery = "SELECT * FROM users WHERE username = '$username'";
    $userDetailsResult = mysqli_query($conn, $userQuery);

    if ($userDetailsResult) {
        $userDetails = mysqli_fetch_assoc($userDetailsResult);
    } else {
        echo "Error fetching user details: " . mysqli_error($conn);
    }

    // Check if user is suspended
    $suspensionQuery = "SELECT * FROM suspensions WHERE user_id = " . $userDetails['user_id'] . " AND suspended_until > NOW()";
    $suspensionResult = mysqli_query($conn, $suspensionQuery);

    if ($suspensionResult) {
        $isSuspended = mysqli_num_rows($suspensionResult) > 0 ? "Suspended" : "Active";
        echo "<p>Status: " . $isSuspended . "</p>";
    } else {
        echo "Error checking suspension status: " . mysqli_error($conn);
    }

    // Fetch borrowed books (transactions) for the user
    
    $transactionsQuery = "SELECT * FROM lending 
                          JOIN books ON lending.book_id = books.book_id 
                          WHERE lending.user_id = " . $userDetails['user_id'] . " AND lending.return_date IS NULL 
                          ORDER BY lending.lend_date DESC";
    
    $transactionsResult = mysqli_query($conn, $transactionsQuery);

    //echo $transactionsQuery;

    if ($transactionsResult && mysqli_num_rows($transactionsResult) > 0) {
        echo "<h4>Your Borrowed Books</h4><br><hr>";
        while ($transaction = mysqli_fetch_assoc($transactionsResult)) {
            echo "<strong>Book:</strong> " . $transaction['title'] . "<br>";
            echo "<strong>Lend Date:</strong> " . $transaction['lend_date'] . "<br>";
            echo "<strong>Return Date:</strong> " . ($transaction['return_date'] ? $transaction['return_date'] : "Not yet returned") . "<br>";
            echo "<form method='POST' action='return_book.php' style='display:inline;'>
                    <input type='hidden' name='lending_id' value='" . $transaction['lending_id'] . "'>
                    <button type='submit' class='btn btn-danger btn-sm'>Return</button>
                  </form><hr>";
        }
    } else {
        echo "<p>No borrowed books.</p>";
    }
?>

    <?php
}
?>

<!-- Logout Form -->
<form method="POST" class="mt-4">
    <button type="submit" name="logout" class="btn btn-danger">Logout</button>
</form>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
