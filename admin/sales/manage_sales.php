<?php
require_once('./../../config.php');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Start session only if it's not already active
}

// Check if user is logged in and retrieve user ID
$user_id = isset($_SESSION['userdata']['id']) ? $_SESSION['userdata']['id'] : null;

// Fetch GL Codes from the chart_of_accounts table using the existing connection
$gl_codes = [];
$gl_query = $conn->query("SELECT code_sub_accountName FROM chart_of_accounts");
while ($row = $gl_query->fetch_assoc()) {
    $gl_codes[] = $row['code_sub_accountName'];
}

$customer_listing = [];
$customer_query = $conn->query("SELECT id, customer FROM customer_list WHERE deleted_flag = 0");

while ($row = $customer_query->fetch_assoc()) {
    $customer_listing[] = [
        'id' => $row['id'],
        'customer_name' => $row['customer']
    ];
}


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
                <label for="customer_id" class="control-label">Customer List</label>
                <select id="customer_id" name="customer_id" class="form-control form-control-sm form-control-border select2" required>
                    <option value="" disabled selected>Select a Customer</option>
                    <?php foreach ($customer_listing as $customer) : ?>
                        <option value="<?= $customer['id'] ?>"><?= $customer['customer_name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="generate_billing" class="control-label">Generate Billing</label>
                <br>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-danger" id="generate-billing-no" style="width: 80px;" onclick="setBillingToggle(0)">No</button>
                    <button type="button" class="btn btn-sm btn-light" id="generate-billing-yes" style="width: 80px;" onclick="setBillingToggle(1)">Yes</button>
                </div>
                <input type="hidden" id="generate_billing" name="generate_billing" value="0">
            </div>

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
    <div class="col-md-12 form-group">
        <label for="po_number" class="control-label">PO Number</label>
        <textarea rows="2" id="po_number" name="po_number" class="form-control form-control-sm rounded-0" required><?= isset($po_number) ? htmlspecialchars($po_number, ENT_QUOTES) : "" ?></textarea>
    </div>
</div>

<div class="row">
    <div class="form-group col-md-6">
        <label for="total_price" class="control-label">Total Price</label>
        <input type="number" step="any" id="total_price" name="total_price" class="form-control form-control-sm form-control-border" value="<?= isset($total_price) ? $total_price : '' ?>" readonly required>
    </div>
    <div class="form-group col-md-6">
        <label for="gl_code" class="control-label">GL Code</label>
        <select id="gl_code" name="gl_code" class="form-control form-control-sm form-control-border" required>
            <option value="" disabled selected>Select GL Code</option>
            <?php foreach ($gl_codes as $code): ?>
                <option value="<?= $code ?>" <?= isset($gl_code) && $gl_code == $code ? 'selected' : '' ?>><?= $code ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>
</form>
</div>

<script>
    // JavaScript to handle toggle functionality
    function setBillingToggle(value) {
        // Set the hidden input field value based on the toggle state
        document.getElementById('generate_billing').value = value;

        // Update button styles based on the value of 'value'
        if (value === 1) { // If value is 1 (Yes)
            document.getElementById('generate-billing-yes').classList.add('btn-primary');
            document.getElementById('generate-billing-yes').classList.remove('btn-light');
            document.getElementById('generate-billing-no').classList.add('btn-light');
            document.getElementById('generate-billing-no').classList.remove('btn-danger');
        } else { // If value is 0 (No)
            document.getElementById('generate-billing-no').classList.add('btn-danger');
            document.getElementById('generate-billing-no').classList.remove('btn-light');
            document.getElementById('generate-billing-yes').classList.add('btn-light');
            document.getElementById('generate-billing-yes').classList.remove('btn-primary');
        }
    }

    // Set default value to '0' initially (ensure 'No' is selected by default)
    setBillingToggle(0);

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
                po_number: $('#po_number').val(), // Include PO Number
                gl_code: $('#gl_code').val(),
                user_id: <?= json_encode($user_id) ?>, // Include user_id from PHP
                customer_id: $('#customer_id').val(), // Include customer listing
                generate_billing: $('#generate_billing').val() // Correctly capture the value (0 or 1) of generate_billing
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