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

if ($_SESSION["role"] == 'admin') {

    if (isset($_POST['add_book'])) {
        // Get the form data
        $title = mysqli_real_escape_string($conn, $_POST['book_title']);
        $author_id = (int)$_POST['author_id'];
        $category_id = (int)$_POST['category_id'];
        
        // Handle image upload
        $imagePath = NULL; // Default if no image is uploaded
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            // Validate the uploaded image (optional checks like file type, size)
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (in_array($_FILES['image']['type'], $allowedTypes)) {
                $uploadDir = 'img/books/'; // Directory to store images
                $uploadFile = $uploadDir . basename($_FILES['image']['name']);
                
                // Move the uploaded file to the target directory
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
                    $imagePath = basename($_FILES['image']['name']);
                } else {
                    echo "Failed to upload image.";
                }
            } else {
                echo "Invalid file type. Only JPG, PNG, and GIF are allowed.";
            }
        }
    
        // Insert the book into the database (including image path if uploaded)
        $query = "INSERT INTO books (title, author_id, category_id, image) 
                  VALUES ('$title', $author_id, $category_id, '$imagePath')";
        if (mysqli_query($conn, $query)) {
            echo "Book added successfully!";
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    }

    if (isset($_GET['edit_book'])) {
        $book_id = (int)$_GET['edit_book'];
        $bookQuery = "SELECT * FROM books WHERE book_id = $book_id";
        $bookResult = mysqli_query($conn, $bookQuery);
        $book = mysqli_fetch_assoc($bookResult);
        if ($book) {
            // Pre-populate form with existing book data
            ?>
            <h4>Edit Book</h4>
            <form method="POST" enctype="multipart/form-data" class="form-inline mb-3">
                <input type="text" name="book_title" placeholder="Book Title" value="<?php echo htmlspecialchars($book['title']); ?>" class="form-control mr-2" required>
                
                <select name="author_id" class="form-control mr-2" required>
                    <option value="">Select Author</option>
                    <?php
                    $authors = mysqli_query($conn, "SELECT * FROM authors");
                    while ($author = mysqli_fetch_assoc($authors)) {
                        $selected = ($book['author_id'] == $author['author_id']) ? 'selected' : '';
                        echo "<option value='" . $author['author_id'] . "' $selected>" . $author['name'] . "</option>";
                    }
                    ?>
                </select>

                <select name="category_id" class="form-control mr-2" required>
                    <option value="">Select Category</option>
                    <?php
                    $categories = mysqli_query($conn, "SELECT * FROM categories");
                    while ($category = mysqli_fetch_assoc($categories)) {
                        $selected = ($book['category_id'] == $category['category_id']) ? 'selected' : '';
                        echo "<option value='" . $category['category_id'] . "' $selected>" . $category['name'] . "</option>";
                    }
                    ?>
                </select>

                <!-- Current Image -->
                <?php if ($book['image']): ?>
                    <p><strong>Current Image:</strong> <img src="img/books/<?php echo $book['image']; ?>" width="100"></p>
                <?php endif; ?>

                <!-- Add Image Upload for New Image -->
                <input type="file" name="image" class="form-control mr-2">

                <button type="submit" name="update_book" class="btn btn-primary">Update Book</button>
            </form>
            <?php
        }
    }

    if (isset($_POST['update_book'])) {
        $book_id = (int)$_GET['edit_book'];
        $title = mysqli_real_escape_string($conn, $_POST['book_title']);
        $author_id = (int)$_POST['author_id'];
        $category_id = (int)$_POST['category_id'];
        
        // Handle image upload (only if a new image is provided)
        $imagePath = NULL;
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            // Validate and upload the image (similar to the add book case)
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (in_array($_FILES['image']['type'], $allowedTypes)) {
                $uploadDir = 'img/books/';
                $uploadFile = $uploadDir . basename($_FILES['image']['name']);
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
                    $imagePath = basename($_FILES['image']['name']);
                } else {
                    echo "Failed to upload image.";
                }
            } else {
                echo "Invalid file type.";
            }
        }

        // Update the book in the database, including the new image path if uploaded
        $updateQuery = "UPDATE books SET title = '$title', author_id = $author_id, category_id = $category_id";
        
        if ($imagePath) {
            $updateQuery .= ", image = '$imagePath'";
        }

        $updateQuery .= " WHERE book_id = $book_id";
        
        if (mysqli_query($conn, $updateQuery)) {
            echo "Book updated successfully!";
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

        // Set a default image if no image is available
        $imagePath = $book['image'] ? 'img/books/' . $book['image'] : 'img/books/default.jpg';
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
