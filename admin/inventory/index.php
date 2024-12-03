<?php
function format_num($number)
{
    // Determine the number of decimals
    $decimals = 0;
    $num_ex = explode('.', $number);
    $decimals = isset($num_ex[1]) ? strlen($num_ex[1]) : 0;
    return number_format($number, $decimals);
}

// Initialize $swhere as an empty string
$swhere = "";

// Check if the user is not an Admin (type 1) or Manager (type 3)
if ($_settings->userdata('type') != 1 && $_settings->userdata('type') != 3) {
    // Filter based on the logged-in user's ID
    $swhere = " WHERE user_id = '" . $_settings->userdata('id') . "' ";
}

// Fetch user details for mapping
$users = $conn->query("SELECT id, username FROM `users_inventory` WHERE id IN (SELECT `user_id` FROM `sales` " . $swhere . ")");
$user_arr = array();
if ($users) {
    while ($user_row = $users->fetch_assoc()) {
        $user_arr[$user_row['id']] = $user_row['username'];
    }
}

// Fetch inventory data
$inventory = $conn->query("SELECT 
    s.id, 
    s.entry_code, 
    s.entry_date, 
    s.product_id, 
    s.description, 
    s.remarks, 
    s.quantity, 
    s.status, 
    s.user_id, 
    p.name AS product_name, 
    p.purchase_price,
    s.date_created, 
    s.date_updated 
FROM `inventory_entries` s 
JOIN `products` p ON s.product_id = p.id 
" . $swhere . " 
ORDER BY date(s.entry_date) ASC");

$entries = array();
if ($inventory) {
    while ($row = $inventory->fetch_assoc()) {
        // Calculate total price in PHP
        $total_price = $row['quantity'] * $row['purchase_price'];
        $row['total_price'] = $total_price; // Add total price to the row data
        $entries[] = $row;
    }
}
?>


<script>
    // Pass the PHP arrays to JavaScript
    const userArr = <?php echo json_encode($user_arr); ?>;
    const inventoryEntries = <?php echo json_encode($entries); ?>;

    // Function to format numbers in JavaScript (same as PHP format_num function)
    function formatNum(number) {
        const decimals = number % 1 === 0 ? 0 : number.toString().split('.')[1].length;
        return number.toFixed(decimals);
    }

    // Assuming you're populating the table dynamically, you can use the data here:
    const tableBody = document.getElementById('inventory-table-body'); // Assuming you have a table with an ID 'inventory-table-body'

    inventoryEntries.forEach(entry => {
        const row = document.createElement('tr');

        // Create table cells with formatted data
        row.innerHTML = `
            <td>${entry.entry_code}</td>
            <td>${entry.entry_date}</td>
            <td>${entry.product_name}</td>
            <td>${entry.description}</td>
            <td>${entry.remarks}</td>
            <td>${entry.quantity}</td>
            <td>${formatNum(entry.purchase_price)}</td>
            <td>${formatNum(entry.total_price)}</td>
            <td>${userArr[entry.user_id] || 'N/A'}</td>
            <td>${entry.status}</td>
        `;

        tableBody.appendChild(row);
    });
</script>

<style>
    th.p-0,
    td.p-0 {
        padding: 0 !important;
    }
</style>
<div class="card card-outline card-primary">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <!-- Title and Approve Selected Button -->
        <div style="display: flex; align-items: center; gap: 15px;">
            <h3 class="card-title">Purchase Entries For Purchases</h3>

            <!-- Actions Dropdown -->
            <?php if ($user_type == '3'): // Only display for user type 3 (manager) 
            ?>
                <div class="dropdown">
                    <button class="btn btn-primary btn-flat btn-sm dropdown-toggle" type="button" id="actionsDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-check-circle"></i> Actions
                    </button>
                    <div class="dropdown-menu" aria-labelledby="actionsDropdown">
                        <button class="dropdown-item approve_data" type="button" data-status="1">
                            <i class="fa fa-check" style="color: green;"></i> Approve Selected
                        </button>
                        <button class="dropdown-item deny_data" type="button" data-status="2">
                            <i class="fa fa-times" style="color: red;"></i> Deny Selected
                        </button>

                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Filter Form and Print Button -->
        <div style="display: flex; align-items: center; gap: 15px; justify-content: center;">
            <!-- Filter Form -->
            <form id="filterForm" class="form-inline" style="display: inline-block;">
                <div class="input-group">
                    <input type="text" class="form-control form-control-sm" id="po_number_filter" placeholder="Filter by PO Number">
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-info btn-sm">Filter</button>
                    </div>
                </div>
            </form>

            <!-- Print Button -->
            <button class="btn btn-secondary btn-flat btn-sm" id="printButton" type="button" onclick="submitPrintForm()">
                <i class="fa fa-print"></i> Print
            </button>
        </div>

        <!-- Add New Purchase Entry Button -->
        <div class="card-tools" style="position: absolute; right: 0; padding-right: 20px;">
            <?php if ($user_type != 3): // Hide the button for user type 3 
            ?>
                <button class="btn btn-primary btn-flat btn-sm" id="create_new" type="button">
                    <i class="fa fa-pen-square"></i> Add New Purchase Entry
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Card Body -->
    <div class="card-body">
        <div class="container-fluid">
            <!-- Inventory Table -->
            <table class="table table-hover table-striped table-bordered" id="inventoryTable">
                <colgroup>
                    <col width="7%">
                    <col width="5%">
                    <col width="5%">
                    <col width="5%">
                    <col width="8%">
                    <col width="5%">
                    <col width="5%">
                    <col width="7%">
                    <col width="7%">
                </colgroup>
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" id="selectAll"><a> Select All</a>
                        </th>
                        <th>Date</th>
                        <th>Entry Code</th>
                        <th>Product</th>
                        <th class="p-0">
                            <div class="d-flex w-100">
                                <div class="col-4 border">Description</div>
                                <div class="col-4 border">Quantity</div>
                                <div class="col-4 border">Total Price</div>
                            </div>
                        </th>
                        <th>PO Number</th>
                        <th>Status</th>
                        <th>Recorded By</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $swhere = "";
                    $user_type = $_settings->userdata('type'); // Get the logged-in user's type
                    $user_id = $_settings->userdata('id'); // Get the logged-in user's ID

                    if ($user_type == 1 || $user_type == 3) {
                        $swhere = "";
                    } else {
                        $swhere = " WHERE user_id = '" . $user_id . "'";
                    }

                    $users = $conn->query("SELECT id, username FROM `users_inventory` WHERE id IN (SELECT `user_id` FROM `inventory_entries` " . $swhere . ")");
                    $user_arr = array();
                    while ($user_row = $users->fetch_assoc()) {
                        $user_arr[$user_row['id']] = $user_row['username'];
                    }

                    $inventory = $conn->query("SELECT id, entry_date, entry_code, description, quantity, remarks, user_id, product_id, status FROM `inventory_entries` " . $swhere . " ORDER BY date(entry_date) ASC");

                    while ($row = $inventory->fetch_assoc()) {
                        $product_query = $conn->query("SELECT name, purchase_price FROM products WHERE id = '" . $row['product_id'] . "'");
                        $product = $product_query->fetch_assoc();
                        $product_name = isset($product['name']) ? $product['name'] : 'Unknown';
                        $purchase_price = isset($product['purchase_price']) ? $product['purchase_price'] : 0;
                        $total_price = $row['quantity'] * $purchase_price;
                    ?>
                        <tr id="row-<?php echo $row['id']; ?>" class="<?php echo ($row['status'] == 1) ? 'approved' : (($row['status'] == 2) ? 'denied' : ''); ?>">
                            <td><input type="checkbox" class="selectItem" data-id="<?php echo $row['entry_code']; ?>"></td>
                            <td class="text-center"><?php echo date("M d, Y", strtotime($row['entry_date'])); ?></td>
                            <td><?php echo htmlspecialchars($row['entry_code'], ENT_QUOTES); ?></td>
                            <td><?php echo htmlspecialchars($product_name, ENT_QUOTES); ?></td>
                            <td class="p-0">
                                <div class="d-flex w-100">
                                    <div class="col-4 border"><?php echo htmlspecialchars($row['description'], ENT_QUOTES); ?></div>
                                    <div class="col-4 border text-right"><?php echo number_format($row['quantity']); ?></div>
                                    <div class="col-4 border text-right">₱<?php echo number_format($total_price, 2); ?></div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($row['remarks'], ENT_QUOTES); ?></td>
                            <td class="text-center">
                                <?php
                                $status_map = array(
                                    0 => '<span class="badge badge-dark">NO STATUS</span>',
                                    1 => '<span class="badge badge-success bg-gradient-success">APPROVED</span>',
                                    2 => '<span class="badge badge-danger bg-gradient-danger">DENIED</span>',
                                );
                                echo isset($status_map[$row['status']]) ? $status_map[$row['status']] : $status_map[0];
                                ?>
                            </td>
                            <td><?php echo isset($user_arr[$row['user_id']]) ? htmlspecialchars($user_arr[$row['user_id']], ENT_QUOTES) : 'N/A'; ?></td>
                            <td class="text-center">
                                <!-- Action Dropdown -->
                                <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                                    Action
                                    <span class="sr-only">Toggle Dropdown</span>
                                </button>
                                <div class="dropdown-menu" role="menu">
                                    <a class="dropdown-item view_data" href="javascript:void(0)" data-id="<?php echo $row['id']; ?>">
                                        <span class="fa fa-eye text-dark"></span> View
                                    </a>
                                    <?php if ($user_type != 3) { ?>
                                        <div class="dropdown-divider"></div>
                                        <?php if ($row['status'] != 'Approved' && $row['status'] != 1) { ?>
                                            <a class="dropdown-item edit_data" href="javascript:void(0)" data-code="<?php echo $row['entry_code']; ?>">
                                                <span class="fa fa-edit text-primary"></span> Edit
                                            </a>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item delete_data" href="javascript:void(0)" data-code="<?php echo $row['entry_code']; ?>">
                                                <span class="fa fa-trash text-danger"></span> Delete
                                            </a>
                                        <?php } else { ?>
                                            <a class="dropdown-item disabled" href="javascript:void(0)" aria-disabled="true">
                                                <span class="fa fa-edit text-muted"></span> Edit
                                            </a>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item disabled" href="javascript:void(0)" aria-disabled="true">
                                                <span class="fa fa-trash text-muted"></span> Delete
                                            </a>
                                        <?php } ?>
                                    <?php } ?>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<script>
    $(document).ready(function() {
        // Open modal to create a new inventory entry
        $('#create_new').click(function() {
            uni_modal("Add New Purchase Entry", "inventory/manage_inventory.php", 'mid-large');
        });
        // View inventory details
        $('.view_data').click(function() {
            var entry_id = $(this).data('id'); // Get the ID from the data-id attribute
            uni_modal("Inventory Details", "inventory/view_inventory.php?id=" + entry_id); // Open modal with details
        });

        // Function to check if the status is APPROVED (1)
        function isApproved(entry_code) {
            return new Promise(function(resolve, reject) {
                $.ajax({
                    url: _base_url_ + "classes/Master.php?f=check_status", // API endpoint to check status
                    type: 'GET',
                    data: {
                        entry_code: entry_code
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'APPROVED') {
                            resolve(true); // Resolve with true if status is approved
                        } else {
                            resolve(false); // Resolve with false if not approved
                        }
                    },
                    error: function() {
                        alert("An error occurred while checking the status.");
                        reject(false); // Reject promise if error occurs
                    }
                });
            });
        }

        $(document).ready(function() {
            // Disable Edit and Delete buttons if the status is APPROVED (1)
            $(".edit_data, .delete_data").each(function() {
                var status = $(this).data("status");
                if (status == 1) {
                    $(this).css("pointer-events", "none"); // Disable interaction
                    $(this).css("opacity", "0.5"); // Optional: Make the button look disabled
                }
            });

            // Edit existing inventory entry
            $(document).on('click', '.edit_data', function() {
                var entry_code = $(this).data('code');
                var status = $(this).data('status'); // Get status from the data-status attribute

                // Check if the status is approved before allowing edit
                if (status == 1) {
                    alert("This entry is already approved and cannot be edited.");
                } else {
                    uni_modal("Edit", "inventory/edit_inventory.php?entry_code=" + entry_code, 'mid-large');
                }
            });

            // Confirm and delete the inventory entry
            $(document).on('click', '.delete_data', function() {
                var entry_code = $(this).data('code');
                var status = $(this).data('status'); // Get status from the data-status attribute

                // Check if the status is approved before allowing delete
                if (status == 1) {
                    alert("This entry is already approved and cannot be deleted.");
                } else {
                    _conf("Are you sure to delete this Entry permanently?", "delete_inventory_entry", [entry_code]);
                }
            });
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

        // Table setup
        $('.table td, .table th').addClass('py-1 px-2 align-middle');

        // Initialize DataTable with specific settings
        $('#inventoryTable').dataTable({
            columnDefs: [{
                orderable: false,
                targets: [2, 3] // Modify as needed based on the table columns
            }]
        });

        // Display alert message if available in session storage
        const message = sessionStorage.getItem('delete_message');
        if (message) {
            alert_toast(message, 'success', 5000); // Show message for 5 seconds
            sessionStorage.removeItem('delete_message'); // Clear session storage after showing the message
        }
    });






    $(document).ready(function() {
        // Select All Checkbox Event
        $('#selectAll').change(function() {
            const isChecked = $(this).prop('checked');

            // Only select checkboxes for visible rows
            $("tr:visible .selectItem").prop('checked', isChecked);
        });

        // Individual Checkbox Change Event
        $(".selectItem").change(function() {
            const isChecked = $(this).prop('checked');
            const visibleCheckboxes = $(".selectItem:visible");

            // If any individual checkbox is unchecked, uncheck "Select All"
            if (!isChecked) {
                $('#selectAll').prop('checked', false);
            }
            // If all visible checkboxes are checked, check "Select All"
            else if (visibleCheckboxes.length === $(".selectItem:checked:visible").length) {
                $('#selectAll').prop('checked', true);
            }
        });

        // Approve or Deny Action
        $('.approve_data, .deny_data').click(function() {
            const status = $(this).data('status');
            const selectedEntries = collectSelectedEntries();

            if (selectedEntries.length === 0) {
                alert_toast('No entries selected for approval/denial.', 'error');
                return;
            }

            fetch(_base_url_ + "classes/Master.php?f=update_status", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        entry_codes: selectedEntries,
                        status: status
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        location.reload();
                        window.addEventListener('load', function() {
                            alert_toast(data.msg, 'success');
                        }, 3000);
                    } else {
                        alert(data.msg);
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('An error occurred while processing your request.');
                });
        });

        // Function to Collect Selected Entry IDs (Only visible checkboxes)
        function collectSelectedEntries() {
            const selectedEntries = [];
            $(".selectItem:checked:visible").each(function() {
                const entryId = $(this).data('id');
                if (entryId) {
                    selectedEntries.push(entryId);
                }
            });
            return selectedEntries;
        }
    });









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


<script>
    function submitPrintForm() {
        const table = document.querySelector('#inventoryTable'); // Ensure this is the correct table ID or class

        if (!table) {
            console.error('Table not found!');
            return; // Exit the function if table is not found
        }

        const poNumber = document.getElementById('po_number_filter').value.trim().toUpperCase(); // Get PO number from input field

        // Set the PO number in the PO Details section
        const poNumberDisplay = document.getElementById('poNumberDisplay');
        if (poNumberDisplay) {
            poNumberDisplay.textContent = `PO #: ${poNumber || '____________________'}`;
        }

        // Create a new form element
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `${window.location.origin}/ajms/admin/inventory/print_order.php?page=inventory`; // Correct full path
        form.target = '_blank'; // Open the form in a new tab

        // Add PO number to the form
        const poNumberInput = document.createElement('input');
        poNumberInput.type = 'hidden';
        poNumberInput.name = 'po_number';
        poNumberInput.value = poNumber;
        form.appendChild(poNumberInput);

        // Select only visible rows in the table
        const visibleRows = table.querySelectorAll('tbody tr:not([style="display: none;"])');
        let poDate = ''; // This will store the entry_date
        let entries = []; // Array to store entries for the form
        let productsToFetchPrice = []; // Array to store product names for which we need to fetch the purchase price

        visibleRows.forEach((row, rowIndex) => {
            const cells = row.querySelectorAll('td');

            if (cells.length < 7) {
                console.warn(`Row ${rowIndex}: Insufficient cells. Skipping this row.`);
                return; // Skip rows with insufficient columns
            }

            const statusCell = cells[6]; // Status column (adjust index if necessary)
            if (!statusCell) {
                console.warn(`Row ${rowIndex}: Status cell not found. Skipping this row.`);
                return; // Skip rows with missing status cell
            }

            const status = statusCell.textContent.trim();
            console.log(`Row ${rowIndex}: Status = ${status}`); // Debug log

            // Check if the status is 'APPROVED'
            if (status === 'APPROVED') {
                // Only process rows with status 'APPROVED'
                const productName = cells[3]?.textContent.trim() || "N/A"; // Product name (third column)
                const flexContainer = cells[4]?.querySelector('.d-flex');

                if (!flexContainer) {
                    console.warn(`Row ${rowIndex}: Flex container not found. Skipping this row.`);
                    return; // Skip if the flex container is missing
                }

                const description = flexContainer.querySelector('.col-4:nth-child(1)')?.textContent.trim() || "N/A";
                const quantityText = flexContainer.querySelector('.col-4:nth-child(2)')?.textContent.trim() || "0";
                const total = flexContainer.querySelector('.col-4:nth-child(3)')?.textContent.trim() || "0.00";

                const quantity = parseFloat(quantityText.replace(/[^0-9.-]+/g, "")) || 0; // Parse quantity safely

                // Add the product details to the entries array
                entries.push({
                    product_name: productName,
                    description: description,
                    quantity: quantity,
                    total: total
                });

                // Add the product to the list of products to fetch purchase price
                productsToFetchPrice.push(productName);

                // Extract entry_date from the first row with approved status (assuming it's in the first column)
                const entryDate = cells[1]?.textContent.trim() || "N/A"; // Adjust index based on actual column for entry_date
                if (!poDate) {
                    poDate = entryDate; // Only set the poDate once
                }
            }
        });

        if (entries.length === 0) {
            alert_toast("No products with status 'APPROVED' found.", 'error');
            return; // Exit if no approved products are found
        }

        // Set the PO date (entry_date) in the PO Details section
        const poDateDisplay = document.getElementById('poDateDisplay');
        if (poDateDisplay) {
            poDateDisplay.textContent = `PO Date: ${poDate || '____________________'}`;
        }

        // Add PO date (entry_date) to the form
        const poDateInput = document.createElement('input');
        poDateInput.type = 'hidden';
        poDateInput.name = 'po_date';
        poDateInput.value = poDate;
        form.appendChild(poDateInput);

        // Function to fetch purchase prices for all products
        function fetchPurchasePrices(products) {
            return new Promise((resolve, reject) => {
                const promises = products.map(product => {
                    return new Promise((resolveProduct, rejectProduct) => {
                        const xhr = new XMLHttpRequest();
                        xhr.open('GET', _base_url_ + "classes/Master.php?f=get_purchase_price_by_product_name&product_name=" + encodeURIComponent(product), true);
                        xhr.onreadystatechange = function() {
                            if (xhr.readyState === 4 && xhr.status === 200) {
                                try {
                                    const response = JSON.parse(xhr.responseText);
                                    if (response.status === 'success') {
                                        resolveProduct({
                                            product_name: product,
                                            purchase_price: response.purchase_price
                                        });
                                    } else {
                                        resolveProduct({
                                            product_name: product,
                                            purchase_price: '0.00'
                                        }); // If error, set default
                                    }
                                } catch (error) {
                                    rejectProduct(error);
                                }
                            }
                        };
                        xhr.send();
                    });
                });

                Promise.all(promises)
                    .then(results => resolve(results))
                    .catch(error => reject(error));
            });
        }

        fetchPurchasePrices(productsToFetchPrice)
            .then(priceResults => {
                priceResults.forEach(priceData => {
                    entries.forEach(entry => {
                        if (entry.product_name === priceData.product_name) {
                            entry.purchase_price = priceData.purchase_price;
                        }
                    });
                });

                // Add entries to the form as a JSON-encoded string
                const entriesInput = document.createElement('input');
                entriesInput.type = 'hidden';
                entriesInput.name = 'entries';
                entriesInput.value = JSON.stringify(entries);
                form.appendChild(entriesInput);

                document.body.appendChild(form);
                form.submit();
            })
            .catch(error => {
                console.error("Error fetching purchase prices:", error);
                alert_toast("Error fetching purchase prices.", 'error');
            });
    }




    // Function to format date in "M d, Y" format
    function formatDate(dateString) {
        const options = {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        };
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', options);
    }

    //-----------------------------------------------------------------------------------------------------

    // JavaScript function to format numbers similarly to the PHP format_num function
    function format_num(number) {
        if (isNaN(number) || number === null || number === undefined) {
            return '0'; // Default to 0 if the number is invalid
        }

        // Convert to number in case it's a string
        number = parseFloat(number);

        // Split the number into its integer and decimal parts
        const num_ex = number.toString().split('.');
        const decimals = num_ex[1] ? num_ex[1].length : 0;

        // Use toLocaleString to apply comma separation for thousands and handle decimals
        return number.toLocaleString('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    }

    // Function to handle the filtering by PO Number
    document.getElementById('filterForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent form from reloading the page

        const poNumber = document.getElementById('po_number_filter').value.trim().toUpperCase(); // Convert input to uppercase

        if (poNumber) {
            // Call the function to filter inventory entries by PO number
            filterEntriesByPONumber(poNumber);
        } else {
            // If no PO number is provided, show all entries again
            resetFilter();
        }
    });

    // Function to filter inventory entries by PO number
    function filterEntriesByPONumber(poNumber) {
        console.log('Filtering inventory entries by PO Number:', poNumber);

        // Send the PO number to the API endpoint
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=filter_inventory_by_po_number", // API endpoint for filtering
            method: "POST",
            data: {
                po_number: poNumber
            },
            success: function(response) {
                try {
                    const result = JSON.parse(response); // Parse the response

                    // Check if the response is successful
                    if (result.status === "success") {
                        console.log('Filtered Entries:', result.filteredEntries);
                        if (result.filteredEntries && result.filteredEntries.length > 0) {
                            // Update the table with filtered entries
                            updateTableWithFilteredEntries(result.filteredEntries, result.userType);
                        } else {
                            // If no entries are found, display a message
                            alert_toast("No entries found for the given PO number.", 'warning');
                            updateTableWithFilteredEntries([], result.userType); // Clear the table
                        }
                    } else {
                        // If the response status is not success, display the error message
                        alert_toast(result.msg, 'error');
                        updateTableWithFilteredEntries([], result.userType); // Clear the table
                    }
                } catch (error) {
                    console.error('Error parsing response:', error);
                    alert_toast("An error occurred while filtering entries.", 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                alert_toast("An error occurred while filtering entries.", 'error');
            }
        });
    }

    function updateTableWithFilteredEntries(entries, userType) {
        const tbody = document.querySelector('#inventoryTable tbody');
        tbody.innerHTML = ''; // Clear current rows

        // Define the status map for badges
        const statusMap = {
            0: '<span class="badge badge-dark">NO STATUS</span>',
            1: '<span class="badge badge-success bg-gradient-success">APPROVED</span>',
            2: '<span class="badge badge-danger bg-gradient-danger">DENIED</span>',
        };

        // Add the updated table header structure
        const thead = document.querySelector('#inventoryTable thead');
        thead.innerHTML = `
        <tr>
            <th>
                <input type="checkbox" id="selectAll">
            </th>
            <th>Date</th>
            <th>Entry Code</th>
            <th>Product</th>
            <th class="p-0">
                <div class="d-flex w-100">
                    <div class="col-4 border">Description</div>
                    <div class="col-4 border">Quantity</div>
                    <div class="col-4 border">Total Price</div>
                </div>
            </th>
            <th>PO Number</th>
            <th>Status</th>
            <th>Recorded By</th>
            <th>Action</th>
        </tr>
    `;


        // Add the checkbox logic for "Check All"
        const selectAllCheckbox = document.getElementById('selectAll');
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.selectItem');
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
        });

        if (entries.length > 0) {
            entries.forEach(entry => {
                // Ensure product_name and purchase_price exist before calculating total price
                const product_name = entry.product_name || "N/A"; // Default to "N/A" if no product name
                const purchase_price = parseFloat(entry.product_price) || 0; // Ensure it's a valid number
                const quantity = parseInt(entry.quantity) || 0; // Ensure it's a valid number
                const total_price = purchase_price * quantity; // Calculate the total price correctly

                // Format the total price and quantity using format_num function
                const formattedTotalPrice = format_num(total_price); // Apply formatting here
                const formattedQuantity = format_num(quantity); // Apply formatting for quantity as well

                // Get the username (fetched from the query)
                const user_inventory_username = entry.user_inventory_username || "N/A"; // Default to "N/A" if no username
                // Get the badge HTML based on status
                const statusBadge = statusMap[entry.status] || statusMap[0];

                // Create a new row for each entry
                const row = document.createElement('tr');

                // Generate dropdown actions with conditions
                let dropdownActions = '';
                const isApproved = entry.status == 1;
                const isDenied = entry.status == 2;

                // If userType is not manager (3), allow actions based on status
                if (userType !== 3) { // If userType is not manager (3)
                    if (!isApproved) {
                        dropdownActions = `
                    <a class="dropdown-item view_data" href="javascript:void(0)" data-id="${entry.id}">
                        <span class="fa fa-eye text-dark"></span> View
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item edit_data" href="javascript:void(0)" data-code="${entry.entry_code}">
                        <span class="fa fa-edit text-primary"></span> Edit
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item delete_data" href="javascript:void(0)" data-code="${entry.entry_code}">
                        <span class="fa fa-trash text-danger"></span> Delete
                    </a>
                `;
                    } else {
                        // Disable actions if the status is "APPROVED" or "DENIED"
                        dropdownActions = `
                    <a class="dropdown-item view_data" href="javascript:void(0)" data-id="${entry.id}">
                        <span class="fa fa-eye text-dark"></span> View
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item disabled" href="javascript:void(0)" aria-disabled="true">
                        <span class="fa fa-edit text-muted"></span> Edit
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="javascript:void(0)" style="pointer-events: none; color: #ccc; cursor: not-allowed;">
                        <span class="fa fa-trash text-muted"></span> Delete
                    </a>
                `;
                    }
                } else { // If userType is manager (3), hide Edit and Delete and add Approve/Deny actions
                    dropdownActions = `
                <a class="dropdown-item view_data" href="javascript:void(0)" data-id="${entry.id}">
                    <span class="fa fa-eye text-dark"></span> View
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item ${isApproved ? 'disabled' : ''}" href="javascript:void(0)" 
                    data-entry_code="${entry.entry_code}" data-status="1" ${isApproved ? 'style="pointer-events: none; color: #ccc; cursor: not-allowed;"' : ''}>
                    <span class="fa fa-check text-success"></span> Approve
                </a>
                <a class="dropdown-item ${isDenied ? 'disabled' : ''}" href="javascript:void(0)" 
                    data-entry_code="${entry.entry_code}" data-status="2" ${isDenied ? 'style="pointer-events: none; color: #ccc; cursor: not-allowed;"' : ''}>
                    <span class="fa fa-times text-danger"></span> Deny
                </a>
            `;
                }

                // Populate row data
                row.innerHTML = `
            <td><input type="checkbox" class="selectItem" data-id="${entry.id}" /></td>
            <td class="text-center">${formatDate(entry.entry_date)}</td>
            <td>${entry.entry_code}</td>
            <td>${product_name}</td>
            <td class="p-0">
                <div class="d-flex w-100">
                    <div class="col-4 border">${entry.description || "N/A"}</div>
                    <div class="col-4 border text-right">${formattedQuantity}</div>
                    <div class="col-4 border text-right">₱${formattedTotalPrice}</div>
                </div>
            </td>
            <td>${entry.remarks || "N/A"}</td>
            <td class="text-center">${statusBadge}</td>
            <td>${user_inventory_username}</td>
            <td class="text-center">
                <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                    Action
                    <span class="sr-only">Toggle Dropdown</span>
                </button>
                <div class="dropdown-menu" role="menu">
                    ${dropdownActions}
                </div>
            </td>
        `;

                // Append the new row to the table
                tbody.appendChild(row);
            });
        } else {
            // If no filtered entries are found, show a message or leave the table empty
            tbody.innerHTML = `<tr><td colspan="9" class="text-center">No entries found for the given PO number.</td></tr>`;
        }
    }
</script>