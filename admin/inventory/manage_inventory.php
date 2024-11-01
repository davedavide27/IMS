<?php
require_once('./../../config.php');

// Initialize input variables
$entry_code = '';
$entry_date = date("Y-m-d");
$description = '';
$remarks = ''; // Add remarks variable
$product_id = '';
$quantity = '';
$purchase_price = '';
$selling_price = '';

if (isset($_GET['entry_code'])) {
    // Use entry_code to fetch the record for editing
    $qry = $conn->query("SELECT * FROM `inventory_entries` WHERE entry_code = '{$_GET['entry_code']}'");
    if ($qry->num_rows > 0) {
        $res = $qry->fetch_array();
        foreach ($res as $k => $v) {
            if (!is_numeric($k))
                $$k = $v;
        }

        // Fetch the associated inventory item
        $item_query = $conn->query("SELECT ii.*, p.name as product_name, p.purchase_price, p.selling_price FROM `inventory_items` ii 
                                     INNER JOIN `products` p ON ii.product_id = p.id 
                                     WHERE ii.inventory_id = '{$id}' LIMIT 1");
        if ($item_query->num_rows > 0) {
            $item = $item_query->fetch_assoc();
            $product_id = $item['product_id'];
            $quantity = $item['quantity'];
            $purchase_price = $item['purchase_price'];
            $selling_price = $item['selling_price'];
        }
    }
} else {
    // Fetch latest entry code for new entries
    $latest_code_query = $conn->query("SELECT MAX(entry_code) AS max_code FROM `inventory_entries`");
    $latest_code = $latest_code_query->fetch_assoc()['max_code'];
    $entry_code = $latest_code ? (intval($latest_code) + 1) : 1; // Auto-increment entry code
}

// Fetch products for the select dropdown
$products = [];
$product_query = $conn->query("SELECT * FROM `products` WHERE delete_flag = 0 ORDER BY `id` ASC");
while ($row = $product_query->fetch_assoc()) {
    $products[$row['id']] = $row; // Store products by ID
}

// Encode products array to JSON for JavaScript
$inventory_arr = json_encode($products);
?>



<div class="container-fluid">
    <form action="" id="inventory-form">
        <input type="hidden" name="id" value="<?= isset($id) ? $id : '' ?>"> <!-- Detect if editing -->
        <div class="row">
            <div class="col-md-6 form-group">
                <label for="entry_code" class="control-label">Entry Code</label>
                <input type="text" id="entry_code" name="entry_code" class="form-control form-control-sm form-control-border rounded-0" value="<?= isset($entry_code) ? $entry_code : '' ?>" readonly required>
            </div>
            <div class="col-md-6 form-group">
                <label for="entry_date" class="control-label">Entry Date</label>
                <input type="date" id="entry_date" name="entry_date" class="form-control form-control-sm form-control-border rounded-0" value="<?= isset($entry_date) ? $entry_date : date("Y-m-d") ?>" required>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 form-group">
                <label for="description" class="control-label">Entry Description</label>
                <textarea rows="2" id="description" name="description" class="form-control form-control-sm rounded-0" required><?= isset($description) ? $description : "" ?></textarea>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 form-group">
                <label for="remarks" class="control-label">Remarks</label> <!-- New label for remarks -->
                <textarea rows="2" id="remarks" name="remarks" class="form-control form-control-sm rounded-0"><?= isset($remarks) ? $remarks : "" ?></textarea> <!-- New textarea for remarks -->
            </div>
        </div>
        <div class="row">
            <div class="form-group col-md-6">
                <label for="product_id" class="control-label">Products</label>
                <select id="product_id" name="product_id" class="form-control form-control-sm form-control-border select2" required>
                    <option value="" disabled selected></option>
                    <?php foreach ($products as $product) : ?>
                        <option value="<?= $product['id'] ?>" <?= ($product['id'] == $product_id) ? 'selected' : '' ?>><?= $product['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="quantity" class="control-label">Quantity</label>
                <input type="number" id="quantity" name="quantity" class="form-control form-control-sm form-control-border" value="<?= $quantity ?>" required>
            </div>
        </div>
        <div class="row">
            <div class="form-group col-md-6">
                <label for="purchase_price" class="control-label">Purchase Price</label>
                <input type="number" step="any" id="purchase_price" name="purchase_price" class="form-control form-control-sm form-control-border" value="<?= isset($purchase_price) ? $purchase_price : '' ?>" readonly required>
            </div>
            <div class="form-group col-md-6">
                <label for="selling_price" class="control-label">Selling Price</label>
                <input type="number" step="any" id="selling_price" name="selling_price" class="form-control form-control-sm form-control-border" value="<?= isset($selling_price) ? $selling_price : '' ?>" readonly required>
            </div>
        </div>
    </form>
</div>

<script>
$(function() {
    // Modal initialization
    $('#uni_modal').on('shown.bs.modal', function() {
        $('.select2').select2({
            placeholder: "Please select here",
            width: "100%",
            dropdownParent: $('#uni_modal')
        });
    });

    // Form submission handling
    $('#uni_modal #inventory-form').submit(function(e) {
        e.preventDefault(); // Prevent default form submission
        var _this = $(this);
        $('.pop-msg').remove();
        var el = $('<div>').addClass("pop-msg alert").hide();

        // Validation for required fields
        var entryDescription = $('#description').val();
        var productId = $('#product_id').val();
        var quantity = $('#quantity').val();

        if (!entryDescription || !productId || !quantity) {
            el.addClass("alert-danger").text("All fields are required.");
            _this.prepend(el);
            el.show('slow');
            $('html,body,.modal').animate({ scrollTop: 0 }, 'fast');
            return; // Exit the function if validation fails
        }

        start_loader(); // Assuming you have a function to show loading

        // Create FormData object
        var formData = new FormData($(this)[0]);

        // Check if this product already exists in the inventory
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=check_product_exists",
            method: 'POST',
            data: { product_id: productId },
            dataType: 'json',
            success: function(resp) {
                if (resp.status === 'success') {
                    // If product exists, update quantity instead
                    var existingQuantity = resp.current_quantity;
                    var newQuantity = parseInt(existingQuantity) + parseInt(quantity);
                    formData.append('quantity', newQuantity); // Update quantity to be saved

                    $.ajax({
                        url: _base_url_ + "classes/Master.php?f=update_inventory_entry",
                        data: formData,
                        cache: false,
                        contentType: false,
                        processData: false,
                        method: 'POST',
                        dataType: 'json',
                        success: function(resp) {
                            handleResponse(resp, _this); // Handle the response
                        },
                        error: function(err) {
                            console.log(err);
                            alert_toast("Error saving inventory!", 'error');
                            end_loader();
                        }
                    });
                } else {
                    // If product does not exist, create a new entry
                    $.ajax({
                        url: _base_url_ + "classes/Master.php?f=save_inventory_entry",
                        data: formData,
                        cache: false,
                        contentType: false,
                        processData: false,
                        method: 'POST',
                        dataType: 'json',
                        success: function(resp) {
                            handleResponse(resp, _this); // Handle the response
                        },
                        error: function(err) {
                            console.log(err);
                            alert_toast("Error saving new inventory!", 'error');
                            end_loader();
                        }
                    });
                }
            },
            error: function(err) {
                console.error(err);
                alert_toast("Error checking product!", 'error');
                end_loader();
            }
        });
    });

    // Function to handle responses
    function handleResponse(resp, _this) {
        if (resp.status === 'success') {
            _this[0].reset(); // Reset form fields
            fetchLatestEntryCode(); // Fetch and update the latest entry code for new entries
            $('.select2').val(null).trigger('change'); // Reset select2 dropdown
            $('#uni_modal').modal('hide'); // Close the modal
            location.reload(); // Refresh the page
        } else {
            var el = $('<div>').addClass("pop-msg alert alert-danger").text(resp.msg || "An error occurred.");
            _this.prepend(el);
            el.show('slow');
            $('html,body,.modal').animate({ scrollTop: 0 }, 'fast');
        }
        end_loader();
    }

    // Fetch the latest entry code on modal open for new entries
    function fetchLatestEntryCode() {
        if (!$('input[name="id"]').val()) { // Check if it's a new entry
            $.ajax({
                url: _base_url_ + "classes/Master.php?f=get_latest_entry_code",
                method: 'GET',
                dataType: 'json',
                success: function(resp) {
                    if (resp.status === 'success') {
                        $('#entry_code').val(resp.latest_code); // Update entry code field
                    } else {
                        console.error(resp.msg);
                    }
                },
                error: function(err) {
                    console.error(err);
                }
            });
        }
    }

    fetchLatestEntryCode(); // Call this function to fetch the latest entry code when the modal opens

    // Populate prices based on selected product
    $('#product_id').change(function() {
        var productId = $(this).val();
        if (productId) {
            var product = <?= $inventory_arr ?>[productId]; // Get the product data
            $('#purchase_price').val(product.purchase_price); // Set purchase price
            $('#selling_price').val(product.selling_price); // Set selling price
        } else {
            $('#purchase_price').val(''); // Reset purchase price
            $('#selling_price').val(''); // Reset selling price
        }
    });
});
</script>
