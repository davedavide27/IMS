<?php
require_once('./../../config.php');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Start session only if it's not already active
}

// Check if user is logged in and retrieve user ID
$user_id = isset($_SESSION['userdata']['id']) ? $_SESSION['userdata']['id'] : null;

// Fetch products and available stocks for dropdown
$products = [];
$product_query = $conn->query("SELECT p.*, s.available_stocks FROM `products` p 
                               INNER JOIN `stocks` s ON p.id = s.product_id 
                               WHERE p.delete_flag = 0 ORDER BY p.id ASC");
while ($row = $product_query->fetch_assoc()) {
    $products[$row['id']] = $row; // Store products by ID
}

// Encode products array to JSON for JavaScript
$inventory_arr = json_encode($products);

// Default variables for new entries
$sales_code = 132; // Default sales_code variable
$purchase_date = date("Y-m-d");
$product_id = '';
$quantity = '';
$selling_price = ''; // Selling price variable
$available_stocks = ''; // Available stocks variable

// Fetch the latest sales_code for new entries
$latest_code_query = $conn->query("SELECT MAX(sales_code) AS max_code FROM `sales`");
$latest_code = $latest_code_query->fetch_assoc()['max_code'];
$sales_code = $latest_code ? (intval($latest_code) + 1) : 1; // Auto-increment sales code
?>

<div class="container-fluid">
    <form action="" id="sales-form">
        <div class="row">
            <div class="col-md-6 form-group">
                <label for="sales_code" class="control-label">Sales Code</label>
                <input type="text" id="sales_code" name="sales_code" class="form-control form-control-sm form-control-border rounded-0" value="<?= isset($sales_code) ? $sales_code : '' ?>" readonly required>
            </div>
            <div class="col-md-6 form-group">
                <label for="purchase_date" class="control-label">Purchase Date</label>
                <input type="date" id="purchase_date" name="purchase_date" class="form-control form-control-sm form-control-border rounded-0" value="<?= isset($purchase_date) ? $purchase_date : date("Y-m-d") ?>" required>
            </div>
        </div>
        <div class="row">
            <div class="form-group col-md-6">
                <label for="product_id" class="control-label">Products</label>
                <select id="product_id" name="product_id" class="form-control form-control-sm form-control-border select2" required>
                    <option value="" disabled selected></option>
                    <?php foreach ($products as $product) : ?>
                        <option value="<?= $product['id'] ?>"><?= $product['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="quantity" class="control-label">Quantity</label>
                <input type="number" id="quantity" name="quantity" class="form-control form-control-sm form-control-border" value="<?= $quantity ?>" required min="1">
            </div>
        </div>
        <div class="row">
            <div class="form-group col-md-6">
                <label for="available_stocks" class="control-label">Available Stocks</label>
                <input type="number" id="available_stocks" name="available_stocks" class="form-control form-control-sm form-control-border" value="<?= isset($available_stocks) ? $available_stocks : '' ?>" readonly required>
            </div>
            <div class="form-group col-md-6">
                <label for="selling_price" class="control-label">Price</label>
                <input type="number" step="any" id="selling_price" name="selling_price" class="form-control form-control-sm form-control-border" value="<?= isset($selling_price) ? $selling_price : '' ?>" readonly required>
            </div>
        </div>
        <div class="row">
            <div class="form-group col-md-6">
                <label for="total_price" class="control-label">Total Price</label>
                <input type="number" step="any" id="total_price" name="total_price" class="form-control form-control-sm form-control-border" value="<?= isset($total_price) ? $total_price : '' ?>" readonly required>
            </div>
        </div>
    </form>
</div>

<script>
$(function() {
    // Initialize select2 for product dropdown
    $('#product_id').select2({
        placeholder: "Please select here",
        width: "100%",
        dropdownParent: $('#uni_modal')
    });

    // Populate price and available stocks on product selection
    $('#product_id').change(function() {
        var selectedProduct = $(this).val();
        var products = <?= $inventory_arr ?>; // Product data passed from PHP

        if (products[selectedProduct]) {
            var productData = products[selectedProduct];
            $('#selling_price').val(productData.selling_price);
            $('#available_stocks').val(productData.available_stocks);
            $('#quantity').val(''); // Clear quantity for new input
            $('#total_price').val(''); // Clear total price
        } else {
            $('#selling_price').val('');
            $('#available_stocks').val('');
            $('#quantity').val('');
            $('#total_price').val('');
        }
    });

    // Calculate total price on quantity change
    $('#quantity').on('input', function() {
        var quantity = parseInt($(this).val()) || 0;
        var sellingPrice = parseFloat($('#selling_price').val()) || 0;

        // Calculate total price
        var totalPrice = quantity * sellingPrice;
        $('#total_price').val(totalPrice.toFixed(2));
    });

    // Save sales entry form submission handling
    $('#sales-form').submit(function(e) {
        e.preventDefault(); // Prevent default form submission

        // Check if entered quantity exceeds available stocks
        var availableStocks = parseInt($('#available_stocks').val());
        var enteredQuantity = parseInt($('#quantity').val());

        if (enteredQuantity <= 0) {
            alert_toast("Please enter a valid quantity.", 'error');
            return false;
        }

        if (enteredQuantity > availableStocks) {
            alert_toast("Entered quantity exceeds available stocks.", 'error');
            return false;
        }

        // Prepare data for the AJAX request
        var formData = {
            sales_code: $('#sales_code').val(),
            purchase_date: $('#purchase_date').val(),
            product_id: $('#product_id').val(),
            quantity: enteredQuantity,
            selling_price: $('#selling_price').val(),
            total_price: $('#total_price').val(),
            user_id: <?= json_encode($user_id) ?> // Include user_id from PHP
        };

        // AJAX request to save the sales entry
        $.ajax({
            type: "POST",
            url: _base_url_ + "classes/Master.php?f=sales_entry",
            data: formData,
            dataType: "json",
            success: function(response) {
                // Handle the response
                if (response && typeof response === "object") {
                    if (response.status === 'success') {
                        alert_toast("Sales entry saved successfully!", 'success');
                        $('#uni_modal').modal('hide'); // Close the modal
                        setTimeout(function() {
                            location.reload(); // Reload the page
                        }, 1000);
                    } else {
                        alert_toast("Error: " + response.msg, 'error');
                    }
                } else {
                    alert_toast("Unexpected response format.", 'error');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                alert_toast("Request failed: " + textStatus + ", " + errorThrown, 'error');
                console.error("AJAX error: ", textStatus, errorThrown, jqXHR.responseText);
            }
        });
    });
});
</script>
