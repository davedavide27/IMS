<?php
function format_num($number)
{
    $decimals = 0;
    $num_ex = explode('.', $number);
    $decimals = isset($num_ex[1]) ? strlen($num_ex[1]) : 0;
    return number_format($number, $decimals);
}
?>
<style>
    th.p-0,
    td.p-0 {
        padding: 0 !important;
    }
</style>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">Inventory Entries For Purchases</h3>
        <div class="card-tools">
            <button class="btn btn-primary btn-flat btn-sm" id="create_new" type="button"><i class="fa fa-pen-square"></i> Add New Inventory Entry</button>
        </div>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <table class="table table-hover table-striped table-bordered" id="inventoryTable">
                <colgroup>
                    <col width="10%"> <!-- Date column, slightly reduced -->
                    <col width="10%"> <!-- Entry Code column, slightly reduced -->
                    <col width="7%"> <!-- Product column, expanded to fit previous layout -->
                    <col width="10%"> <!-- Description column, expanded to fit previous layout -->
                    <col width="6%"> <!-- Quantity column -->
                    <col width="10%"> <!-- Total Price column -->
                    
                </colgroup>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Entry Code</th>
                        <th>Product</th> <!-- Added Product column in the header -->
                        <th class="p-0">
                            <div class="d-flex w-100">
                                <div class="col-5 border">Description</div>
                                <div class="col-3 border">Quantity</div>
                                <div class="col-4 border">Total Price</div> <!-- Updated column header -->
                            </div>
                        </th>
                        <th>Recorded By</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $swhere = "";
                    if ($_settings->userdata('type') != 1) {
                        $swhere = " WHERE user_id = '{$_settings->userdata('id')}' ";
                    }
                    $users = $conn->query("SELECT id, username FROM `users` WHERE id IN (SELECT `user_id` FROM `inventory_entries` {$swhere})");
                    $user_arr = array_column($users->fetch_all(MYSQLI_ASSOC), 'username', 'id');

                    // Fetch the necessary fields including product_id
                    $inventory = $conn->query("SELECT id, entry_date, entry_code, description, quantity, user_id, product_id FROM `inventory_entries` {$swhere} ORDER BY date(entry_date) ASC");
                    
                    while ($row = $inventory->fetch_assoc()):
                        // Fetch product details including purchase price
                        $product = $conn->query("SELECT name, purchase_price FROM products WHERE id = '{$row['product_id']}'")->fetch_assoc();
                        $total_price = $row['quantity'] * $product['purchase_price']; // Calculate total price using purchase price
                    ?>
                        <tr>
                            <td class="text-center"><?= date("M d, Y", strtotime($row['entry_date'])) ?></td>
                            <td class=""><?= htmlspecialchars($row['entry_code'], ENT_QUOTES) ?></td>
                            <td class=""><?= htmlspecialchars($product['name'], ENT_QUOTES) ?></td> <!-- Display product name -->
                            <td class="p-0">
                                <div class="d-flex w-100">
                                    <div class="col-5 border"><?= htmlspecialchars($row['description'], ENT_QUOTES) ?></div>
                                    <div class="col-3 border text-right"><?= format_num($row['quantity']) ?></div>
                                    <div class="col-4 border text-right"><?= format_num($total_price) ?></div> <!-- Display total price -->
                                </div>
                            </td>
                            <td><?= isset($user_arr[$row['user_id']]) ? $user_arr[$row['user_id']] : "N/A" ?></td>
                            <td class="text-center">
                                <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                                    Action
                                    <span class="sr-only">Toggle Dropdown</span>
                                </button>
                                <div class="dropdown-menu" role="menu">
                                <a class="dropdown-item view_data" href="javascript:void(0)" data-id ="<?php echo $row['id'] ?>">
                                        <span class="fa fa-eye text-dark"></span> View
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item edit_data" href="javascript:void(0)" data-code="<?php echo $row['entry_code'] ?>">
                                        <span class="fa fa-edit text-primary"></span> Edit
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item delete_data" href="javascript:void(0)" data-code="<?php echo $row['entry_code'] ?>">
                                        <span class="fa fa-trash text-danger"></span> Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Open modal to create a new inventory entry
        $('#create_new').click(function() {
            uni_modal("Add New Inventory Entry", "inventory/manage_inventory.php", 'mid-large');
        });

        // Edit existing inventory entry
        $(document).on('click', '.edit_data', function() {
            var entry_code = $(this).data('code');
            uni_modal("Edit", "inventory/edit_inventory.php?entry_code=" + entry_code, 'mid-large');
        });

        $('.view_data').click(function(){
            uni_modal("Inventory Details", "inventory/view_inventory.php?id=" + $(this).attr('data-id'));
        });

        // Confirm and delete the inventory entry
        $(document).on('click', '.delete_data', function() {
            var entry_code = $(this).data('code'); // Get entry_code from data attribute
            _conf("Are you sure to delete this Entry permanently?", "delete_inventory_entry", [entry_code]);
        });

        // Table setup
        $('.table td, .table th').addClass('py-1 px-2 align-middle');

        // Initialize DataTable with specific settings
        $('#inventoryTable').dataTable({
            columnDefs: [{
                orderable: false,
                targets: [2, 5] // Modify as needed based on the table columns
            }]
        });

        // Display alert message if available in session storage
        const message = sessionStorage.getItem('delete_message');
        if (message) {
            alert_toast(message, 'success', 5000); // Show message for 5 seconds
            sessionStorage.removeItem('delete_message'); // Clear session storage after showing the message
        }
    });

    // Fetch entry details for editing based on entry_code
    function fetch_entry_details(entry_code) {
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=get_inventory_entry", // API endpoint to fetch entry
            method: "GET",
            data: {
                entry_code: entry_code
            }, // Pass entry_code as data
            dataType: "json",
            success: function(resp) {
                if (resp.status === 'success') {
                    // Check if response data is valid
                    if (resp.data) {
                        // Populate the form with fetched data
                        $('#entry_code').val(resp.data.entry_code).prop('readonly', true); // Make entry code read-only
                        $('#entry_date').val(resp.data.entry_date);
                        $('#description').val(resp.data.description);
                        $('#product_id').val(resp.data.product_id).trigger('change');
                        $('#quantity').val(resp.data.quantity);

                        $('#uni_modal').modal('show'); // Show the modal for editing
                    } else {
                        alert_toast("No data found for this entry.", 'error');
                    }
                } else {
                    alert_toast("Entry not found.", 'error');
                }
            },
            error: function(err) {
                console.error(err); // Log error in console for debugging
                alert_toast("An error occurred while fetching entry details.", 'error');
            }
        });
    }

    // Form submission to save new or edited inventory entry
    $('#inventory-form').submit(function(e) {
        e.preventDefault(); // Prevent default form submission
        var _this = $(this); // Reference to the form
        $('.pop-msg').remove(); // Remove any previous message
        var el = $('<div>').addClass("pop-msg alert").hide(); // Placeholder for new message

        start_loader(); // Start loader before sending request
        var formData = new FormData($(this)[0]); // Get form data

        $.ajax({
            url: _base_url_ + "classes/Master.php?f=save_inventory_entry", // API for saving entry
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            method: 'POST',
            dataType: 'json',
            success: function(resp) {
                if (resp.status == 'success') {
                    location.reload(); // Reload page on success
                } else {
                    el.addClass("alert-danger").text(resp.msg || "An error occurred."); // Show error message
                    _this.prepend(el);
                    el.show('slow');
                }
                end_loader(); // End loader after response
            },
            error: err => {
                console.error(err); // Log error in console for debugging
                el.addClass("alert-danger").text("An error occurred while saving the entry."); // Show error message
                _this.prepend(el);
                el.show('slow');
                end_loader(); // End loader after response
            }
        });
    });

    // Function to delete inventory entry
    function delete_inventory_entry(entry_code) {
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=delete_inventory_entry", // API for deleting entry
            method: "POST",
            data: {
                entry_code: entry_code // Pass entry_code as data
            },
            dataType: "json",
            success: function(resp) {
                if (resp.status === 'success') {
                    sessionStorage.setItem('delete_message', resp.msg); // Set success message in session storage
                    location.reload(); // Reload the page to reflect changes
                } else {
                    alert_toast(resp.msg || "An error occurred while deleting the entry.", 'error');
                }
            },
            error: function(err) {
                console.error(err); // Log error in console for debugging
                alert_toast("An error occurred while deleting the entry.", 'error');
            }
        });
    }

    // Overriding _conf function to call delete_inventory_entry
    function _conf(message, action, params) {
        if (confirm(message)) {
            delete_inventory_entry(params[0]); // Call delete_inventory_entry with the provided entry_code
        }
    }
</script>
