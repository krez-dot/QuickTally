<?php
require_once __DIR__ . '/classes/Product.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$id = (int) ($_GET['id'] ?? 0);

if ($id > 0) {
    $product = Product::findById($id);
    if ($product) {
        try {
            $product->delete();
            $_SESSION['flash_success'] = 'Product deleted successfully.';
        } catch (PDOException $e) {
            // Likely blocked by FK constraint because it appears in past sale_items
            $_SESSION['flash_error'] = 'Cannot delete this product: it is referenced by existing sales records.';
        }
    } else {
        $_SESSION['flash_error'] = 'Product not found.';
    }
}

header('Location: products.php');
exit;
