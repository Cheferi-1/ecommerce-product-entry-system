<?php

$host = "localhost";
$port = 3307;
$dbname = "lab04_ecommerce";
$username = "root";
$password = "";

$errors = [];
$success = "";

$product_name = "";
$sku = "";
$category = "";
$unit_price = "";
$quantity = "";
$description = "";
$available_for_sale = "";

try {
    // Connect to database
    $conn = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Database connection failed. Check your database settings.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Collect form values
    $product_name = trim($_POST["product_name"] ?? "");
    $sku = strtoupper(trim($_POST["sku"] ?? ""));
    $category = trim($_POST["category"] ?? "");
    $unit_price = trim($_POST["unit_price"] ?? "");
    $quantity = trim($_POST["quantity"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $available_for_sale = isset($_POST["available_for_sale"]) ? "1" : "0";

    // Validate product name
    if (strlen($product_name) < 3 || strlen($product_name) > 100) {
        $errors[] = "Product name must contain 3 to 100 characters.";
    }

    // Validate SKU
    if ($sku == "" || strlen($sku) > 50) {
        $errors[] = "A valid SKU is required.";
    }

    // Validate category
    $categories = ["Electronics", "Clothing", "Food", "Furniture", "Books", "Other"];

    if (!in_array($category, $categories, true)) {
        $errors[] = "Please select a valid category.";
    }

    // Validate price
    if (!is_numeric($unit_price) || (float)$unit_price <= 0) {
        $errors[] = "Unit price must be greater than zero.";
    }

    // Validate quantity
    if (
        $quantity === "" ||
        !preg_match('/^\d+$/', $quantity) ||
        (int)$quantity < 0
    ) {
        $errors[] = "Quantity must be a non-negative whole number.";
    }

    // Validate description
    if (strlen($description) > 1000) {
        $errors[] = "Description cannot exceed 1000 characters.";
    }

    // Validate image
    $image_path = null;
    $uploaded_file = null;

    if (isset($_FILES["product_image"]) &&
        $_FILES["product_image"]["error"] != UPLOAD_ERR_NO_FILE) {

        if ($_FILES["product_image"]["error"] != UPLOAD_ERR_OK) {
            $errors[] = "There was a problem uploading the image.";

        } elseif ($_FILES["product_image"]["size"] > 2 * 1024 * 1024) {
            $errors[] = "Image size must not exceed 2MB.";

        } else {

            // Check actual image type
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($_FILES["product_image"]["tmp_name"]);

            $allowed_types = [
                "image/jpeg" => "jpg",
                "image/png" => "png"
            ];

            if (!isset($allowed_types[$mime])) {
                $errors[] = "Only JPEG and PNG images are allowed.";

            } elseif (getimagesize($_FILES["product_image"]["tmp_name"]) === false) {
                $errors[] = "The uploaded file is not a valid image.";

            } else {
                $extension = $allowed_types[$mime];

                // Create a safe filename
                $filename = bin2hex(random_bytes(16)) . "." . $extension;

                $upload_directory = __DIR__ . "/uploads/";

                if (!is_dir($upload_directory)) {
                    mkdir($upload_directory, 0755, true);
                }

                $uploaded_file = $upload_directory . $filename;
                $image_path = "uploads/" . $filename;
            }
        }
    }

    // Save product if there are no errors
    if (empty($errors)) {

        try {

            // Check duplicate SKU
            $check = $conn->prepare(
                "SELECT id FROM products WHERE sku = ?"
            );

            $check->execute([$sku]);

            if ($check->fetch()) {

                $errors[] = "This SKU already exists. Please use another SKU.";

            } else {

                // Move image into uploads folder
                if ($uploaded_file !== null) {
                    if (!move_uploaded_file(
                        $_FILES["product_image"]["tmp_name"],
                        $uploaded_file
                    )) {
                        throw new Exception("Image upload failed.");
                    }
                }

                // Insert product
                $sql = "INSERT INTO products
                (product_name, sku, category, unit_price, quantity,
                description, product_image, available_for_sale)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = $conn->prepare($sql);

                $stmt->execute([
                    $product_name,
                    $sku,
                    $category,
                    $unit_price,
                    $quantity,
                    $description,
                    $image_path,
                    $available_for_sale
                ]);

                $success = "Product saved successfully! Product ID: "
                    . $conn->lastInsertId();

                // Clear form
                $product_name = "";
                $sku = "";
                $category = "";
                $unit_price = "";
                $quantity = "";
                $description = "";
                $available_for_sale = "";
            }

        } catch (PDOException $e) {

            // Remove uploaded image if database insertion fails
            if ($uploaded_file !== null && file_exists($uploaded_file)) {
                unlink($uploaded_file);
            }

            if ($e->getCode() == "23000") {
                $errors[] = "This SKU already exists.";
            } else {
                $errors[] = "Could not save the product. Please try again.";
            }

        } catch (Exception $e) {

            $errors[] = "Could not upload the product image.";
        }
    }
}

// Get products for inventory listing
$stmt = $conn->query(
    "SELECT * FROM products ORDER BY category ASC, product_name ASC"
);

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>E-Commerce Product Entry</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

    <h1>E-Commerce Product Entry</h1>

    <p class="subtitle">Enter and manage your store products.</p>

    <?php if (!empty($errors)): ?>
        <div class="error">
            <strong>Please correct these errors:</strong>

            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success != ""): ?>
        <div class="success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <label>Product Name *</label>
        <input type="text" name="product_name"
            value="<?= htmlspecialchars($product_name) ?>" required>

        <label>SKU *</label>
        <input type="text" name="sku"
            value="<?= htmlspecialchars($sku) ?>" required>

        <label>Category *</label>
        <select name="category" required>
            <option value="">Select Category</option>

            <?php
            $categories = ["Electronics", "Clothing", "Food", "Furniture", "Books", "Other"];

            foreach ($categories as $item):
            ?>
                <option value="<?= htmlspecialchars($item) ?>"
                    <?= $category == $item ? "selected" : "" ?>>
                    <?= htmlspecialchars($item) ?>
                </option>
            <?php endforeach; ?>

        </select>

        <label>Unit Price (XAF) *</label>
        <input type="number" name="unit_price"
            min="0.01" step="0.01"
            value="<?= htmlspecialchars($unit_price) ?>" required>

        <label>Quantity *</label>
        <input type="number" name="quantity"
            min="0" step="1"
            value="<?= htmlspecialchars($quantity) ?>" required>

        <label>Description</label>
        <textarea name="description" rows="4"
            maxlength="1000"><?= htmlspecialchars($description) ?></textarea>

        <label>Product Image (JPEG or PNG, maximum 2MB)</label>
        <input type="file" name="product_image"
            accept=".jpg,.jpeg,.png,image/jpeg,image/png">

        <div class="checkbox">
            <input type="checkbox" name="available_for_sale" value="1"
                <?= $available_for_sale == "1" ? "checked" : "" ?>>

            <label>Available for sale</label>
        </div>

        <button type="submit">Save Product</button>

    </form>

</div>

<div class="inventory">

    <h2>Product Inventory</h2>

    <?php if (empty($products)): ?>

        <p>No products have been added yet.</p>

    <?php else: ?>

        <div class="table-container">

            <table>

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Category</th>
                        <th>Price (XAF)</th>
                        <th>Quantity</th>
                        <th>Available</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($products as $product): ?>

                        <tr>
                            <td><?= (int)$product["id"] ?></td>

                            <td>
                                <?php if ($product["product_image"]): ?>
                                    <img class="product-image"
                                        src="<?= htmlspecialchars($product["product_image"]) ?>"
                                        alt="Product image">
                                <?php else: ?>
                                    No image
                                <?php endif; ?>
                            </td>

                            <td><?= htmlspecialchars($product["product_name"]) ?></td>
                            <td><?= htmlspecialchars($product["sku"]) ?></td>
                            <td><?= htmlspecialchars($product["category"]) ?></td>
                            <td><?= number_format((float)$product["unit_price"], 2) ?></td>
                            <td><?= (int)$product["quantity"] ?></td>
                            <td>
                                <?= $product["available_for_sale"] ? "Yes" : "No" ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</div>

</body>
</html>