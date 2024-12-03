<?php
// Include the necessary file that contains your class (adjust path as needed)
require_once '../classes/Master.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read the raw POST data
    $data = json_decode(file_get_contents('php://input'), true);

    // Make sure product_name is provided
    if (isset($data['product_name']) && !empty($data['product_name'])) {
        // Create an instance of the class
        $classInstance = new Master(); // Replace with your actual class name

        // Get the purchase price by product name
        $result = $classInstance->get_purchase_price_by_product_name($data['product_name']);

        // Send back the result as JSON
        echo $result;
    } else {
        echo json_encode(['status' => 'failed', 'msg' => 'Product name is missing.']);
    }
}
?>
