<?php
function format_num($number)
{
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
    $swhere = " WHERE user_id = '{$_settings->userdata('id')}' ";
}

// Fetch user details for mapping
$users = $conn->query("SELECT id, username FROM `users` WHERE id IN (SELECT `user_id` FROM `sales` {$swhere})");
$user_arr = array_column($users->fetch_all(MYSQLI_ASSOC), 'username', 'id');

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
{$swhere} 
ORDER BY date(s.entry_date) ASC");

$entries = [];
while ($row = $inventory->fetch_assoc()) {
    $total_price = $row['quantity'] * $row['purchase_price']; // Calculate total price in PHP
    $row['total_price'] = $total_price; // Add total price to the row data
    $entries[] = $row;
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
        <!-- Left Section (Title) -->
        <h3 class="card-title">Purchase Entries For Purchases</h3>

        <!-- Centered Section (Filter and Print Buttons) -->
        <div style="display: flex; align-items: center; gap: 15px; justify-content: center;">
            <!-- Filter by PO Number -->
            <form id="filterForm" class="form-inline" style="display: inline-block;">
                <div class="input-group">
                    <input type="text" class="form-control form-control-sm" id="po_number_filter" placeholder="Filter by PO Number">
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-info btn-sm">Filter</button>
                    </div>
                </div>
            </form>
            <button class="btn btn-secondary btn-flat btn-sm" id="printButton" type="button" onclick="submitPrintForm()">
                <i class="fa fa-print"></i> Print
            </button>
        </div>

        <!-- Right Section (Add New Button) -->
        <div class="card-tools" style="position: absolute; right: 0; padding-right: 20px;">
            <button class="btn btn-primary btn-flat btn-sm" id="create_new" type="button">
                <i class="fa fa-pen-square"></i> Add New Purchase Entry
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <table class="table table-hover table-striped table-bordered" id="inventoryTable">
                <colgroup>
                    <col width="10%"> <!-- Date column -->
                    <col width="5%"> <!-- Entry Code column -->
                    <col width="5%"> <!-- Product column -->
                    <col width="9%"> <!-- Description column -->
                    <col width="6%"> <!-- Quantity column -->
                    <col width="6%"> <!-- Total Price column -->
                    <col width="4%"> <!-- Remarks column -->
                    <col width="4%"> <!-- Status column -->
                </colgroup>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Entry Code</th>
                        <th>Product</th>
                        <th class="p-0">
                            <div class="d-flex w-100">
                                <div class="col-5 border">Description</div>
                                <div class="col-3 border">Quantity</div>
                                <div class="col-4 border">Total Price</div>
                            </div>
                        </th>
                        <th>PO Number</th>
                        <th>Status</th> <!-- Added Status column -->
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
                        $swhere = " WHERE user_id = '{$user_id}' ";
                    }

                    $users = $conn->query("SELECT id, username FROM `users` WHERE id IN (SELECT `user_id` FROM `inventory_entries` {$swhere})");
                    $user_arr = array_column($users->fetch_all(MYSQLI_ASSOC), 'username', 'id');

                    $inventory = $conn->query("SELECT id, entry_date, entry_code, description, quantity, remarks, user_id, product_id, status FROM `inventory_entries` {$swhere} ORDER BY date(entry_date) ASC");

                    while ($row = $inventory->fetch_assoc()):
                        $product = $conn->query("SELECT name, purchase_price FROM products WHERE id = '{$row['product_id']}'")->fetch_assoc();
                        $total_price = $row['quantity'] * $product['purchase_price'];
                    ?>
                        <tr>
                            <td class="text-center"><?= date("M d, Y", strtotime($row['entry_date'])) ?></td>
                            <td class=""><?= htmlspecialchars($row['entry_code'], ENT_QUOTES) ?></td>
                            <td class=""><?= htmlspecialchars($product['name'], ENT_QUOTES) ?></td>
                            <td class="p-0">
                                <div class="d-flex w-100">
                                    <div class="col-5 border"><?= htmlspecialchars($row['description'], ENT_QUOTES) ?></div>
                                    <div class="col-3 border text-right"><?= format_num($row['quantity']) ?></div>
                                    <div class="col-4 border text-right">₱<?= format_num($total_price) ?></div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($row['remarks'], ENT_QUOTES) ?></td>
                            <td class="text-center">
                                <?php
                                // Mapping the numeric status values to human-readable status
                                $status_map = [
                                    0 => 'NO STATUS',
                                    1 => 'APPROVED',
                                    2 => 'DENIED'
                                ];

                                // Get the status value from the row
                                $status = isset($row['status']) ? $row['status'] : 0; // Default to 0 if status is not set

                                // Map the status to its corresponding badge and style
                                switch ($status) {
                                    case 1:
                                        echo '<span class="badge badge-success bg-gradient-success">APPROVED</span>';
                                        break;
                                    case 2:
                                        echo '<span class="badge badge-danger bg-gradient-danger">DENIED</span>';
                                        break;
                                    default:
                                        echo '<span class="badge badge-dark">NO STATUS</span>';
                                        break;
                                }
                                ?>
                            </td>
                            <td><?= isset($user_arr[$row['user_id']]) ? $user_arr[$row['user_id']] : "N/A" ?></td>
                            <td class="text-center">
                                <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                                    Action
                                    <span class="sr-only">Toggle Dropdown</span>
                                </button>
                                <div class="dropdown-menu" role="menu">
                                    <a class="dropdown-item view_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">
                                        <span class="fa fa-eye text-dark"></span> View
                                    </a>

                                    <!-- Show Edit and Delete buttons if user is not a manager -->
                                    <?php if ($user_type != 3) { ?>
                                        <div class="dropdown-divider"></div> <!-- Divider for non-managers -->
                                        <a class="dropdown-item edit_data" href="javascript:void(0)" data-code="<?php echo $row['entry_code'] ?>" data-status="<?php echo $status; ?>">
                                            <span class="fa fa-edit text-primary"></span> Edit
                                        </a>
                                        <div class="dropdown-divider"></div> <!-- Divider shown only if Edit button is visible -->

                                        <a class="dropdown-item delete_data" href="javascript:void(0)" data-code="<?php echo $row['entry_code'] ?>" data-status="<?php echo $status; ?>">
                                            <span class="fa fa-trash text-danger"></span> Delete
                                        </a>
                                    <?php } ?>

                                    <!-- Show Approve and Deny options only for managers -->
                                    <?php if ($user_type == 3): // Only show these options for managers 
                                    ?>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item approve_data" href="javascript:void(0)" data-entry_code="<?php echo $row['entry_code']; ?>" data-status="1">
                                            <span class="fa fa-check text-success"></span> Approve
                                        </a>
                                        <a class="dropdown-item deny_data" href="javascript:void(0)" data-entry_code="<?php echo $row['entry_code']; ?>" data-status="2">
                                            <span class="fa fa-times text-danger"></span> Deny
                                        </a>
                                    <?php endif; ?>
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


    // Handle the click event for the approve/deny options
    $(document).on('click', '.approve_data, .deny_data', function() {
        const entry_code = $(this).data('entry_code'); // Get the entry_code of the item
        const status = $(this).data('status'); // Get the status (1: APPROVED, 2: DENIED)

        // Map numeric status to text for confirmation message
        let statusText = '';
        if (status == 1) {
            statusText = 'APPROVED';
        } else if (status == 2) {
            statusText = 'DENIED';
        } else {
            statusText = 'NO STATUS';
        }

        // Confirm the action with the same style as delete entry
        _conf(`Are you sure you want to change the status to "${statusText}" for entry code "${entry_code}"?`, "update_inventory_status", [entry_code, status]);
    });

    $(document).ready(function() {
        // Check if there is a success message in sessionStorage
        var successMessage = sessionStorage.getItem('update_status_message');
        if (successMessage) {
            // Display the success message using a custom toast or alert method
            alert_toast(successMessage, 'success');

            // Remove the message after displaying it to avoid showing it again on page reload
            sessionStorage.removeItem('update_status_message');

            // Optional: Delay for 3 seconds before doing anything else if needed
            setTimeout(function() {
                // You can add additional logic here after the message duration ends
            }, 3000); // 3-second duration
        }
    });


    // Function to update the inventory status
    function update_inventory_status(entry_code, status) {
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=update_status", // API endpoint to update the status
            method: 'POST',
            data: {
                entry_code: entry_code, // Pass the entry_code as a string
                status: status // Pass the status as a number (0, 1, or 2)
            },
            dataType: 'json',
            success: function(resp) {
                if (resp.status === 'success') {
                    // Store the success message in sessionStorage for later use
                    sessionStorage.setItem('update_status_message', resp.msg);

                    // Reload the page immediately
                    location.reload();
                } else {
                    // Show error message from the response
                    alert_toast(resp.msg || "An error occurred while updating the status.", 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText); // Log any AJAX errors for debugging
                alert_toast("An error occurred while updating the status.", 'error'); // Show a generic error message
            }
        });
    }



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
        form.action = 'http://localhost/ajms/admin/inventory/print_order.php'; // Update to the correct action URL

        // Add target="_blank" to open the form in a new tab
        form.target = '_blank';

        // Add PO number to the form
        const poNumberInput = document.createElement('input');
        poNumberInput.type = 'hidden';
        poNumberInput.name = 'po_number';
        poNumberInput.value = poNumber;
        form.appendChild(poNumberInput);

        // Select only visible rows in the table
        const rows = table.querySelectorAll('tbody tr:not([style="display: none;"])');
        rows.forEach((row, rowIndex) => {
            const cells = row.querySelectorAll('td');
            cells.forEach((cell, colIndex) => {
                // Create hidden input fields for each table cell
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `entries[${rowIndex}][${colIndex}]`; // Structured name for easy processing
                input.value = cell.textContent.trim();
                form.appendChild(input);
            });
        });

        // Append the form to the body and submit it
        document.body.appendChild(form);
        form.submit();
    }



    /*
    // Function to handle the print functionality (only prints the table without the action column and action buttons)
    function printPage() {
        // Find the header for the "Action" column (Assume it's the last column)
        const tableHeader = document.querySelector('#inventoryTable thead');
        const actionColumnIndex = Array.from(tableHeader.rows[0].cells).findIndex(cell => cell.textContent.trim() === 'Action');

        if (actionColumnIndex === -1) return; // If the "Action" column is not found, exit

        // Hide the "Action" column header and all action columns (cells in rows)
        const actionColumns = document.querySelectorAll(`#inventoryTable tbody tr td:nth-child(${actionColumnIndex + 1})`);
        const actionColumnHeader = tableHeader.rows[0].cells[actionColumnIndex];

        if (actionColumnHeader) actionColumnHeader.style.display = 'none'; // Hide the header
        actionColumns.forEach(cell => cell.style.display = 'none'); // Hide the action cells

        // Hide action dropdowns and buttons
        const actionDropdowns = document.querySelectorAll('.dropdown-toggle, .dropdown-menu, .dropdown-item');
        actionDropdowns.forEach(element => element.style.display = 'none'); // Hide action dropdown and items

        // Get the table content for printing
        const content = document.getElementById('inventoryTable').outerHTML;
        const styles = `
        <style>
            body { font-family: Arial, sans-serif; }
            table { width: 100%; border-collapse: collapse; }
            table th, table td { padding: 8px; text-align: left; border: 1px solid #ddd; }
            table th { background-color: #f2f2f2; }
            table td { background-color: #ffffff; }
            @media print {
                body { margin: 0; padding: 0; }
                table { margin-top: 20px; }
            }
        </style>
    `;

        // Open a new window for printing
        const newWindow = window.open('', '', 'height=1200,width=800');
        newWindow.document.write('<html><head><title>Print Table</title>' + styles + '</head><body>');
        newWindow.document.write('<h3>Purchase Entries</h3>'); // Add a title to the print view
        newWindow.document.write(content); // Write the table content into the new window
        newWindow.document.write('</body></html>');
        newWindow.document.close(); // Close the document to ensure it is ready

        // Automatically close the print window after printing with a delay
        newWindow.focus();

        // Attach `onafterprint` to close the print window after a delay
        newWindow.onafterprint = function() {
            setTimeout(() => {
                newWindow.close(); // Close the print window after 2 seconds
            }, 2000); // Delay of 2 seconds (2000 milliseconds)
        };

        // Trigger the print dialog
        newWindow.print();

        // Restore the action column header, action cells, and buttons after printing
        if (actionColumnHeader) actionColumnHeader.style.display = ''; // Restore action column header
        actionColumns.forEach(cell => cell.style.display = ''); // Restore action cells
        actionDropdowns.forEach(element => element.style.display = ''); // Restore action buttons
    }

*/
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
                            updateTableWithFilteredEntries(result.filteredEntries);
                        } else {
                            // If no entries are found, display a message
                            alert_toast("No entries found for the given PO number.", 'info');
                            updateTableWithFilteredEntries([]); // Clear the table
                        }
                    } else {
                        // If the response status is not success, display the error message
                        alert_toast(result.msg, 'error');
                        updateTableWithFilteredEntries([]); // Clear the table
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


    // Function to update the table with filtered entries
    function updateTableWithFilteredEntries(entries) {
        const tbody = document.querySelector('#inventoryTable tbody');
        tbody.innerHTML = ''; // Clear current rows

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
                const username = entry.username || "N/A"; // Default to "N/A" if no username

                // Create a new row for each entry
                const row = document.createElement('tr');

                // Populate row data
                row.innerHTML = `
                <td class="text-center">${formatDate(entry.entry_date)}</td>
                <td>${entry.entry_code}</td>
                <td>${product_name}</td>
                <td class="p-0">
                    <div class="d-flex w-100">
                        <div class="col-5 border">${entry.description || "N/A"}</div>
                        <div class="col-3 border text-right">${formattedQuantity}</div>
                        <div class="col-4 border text-right">₱${formattedTotalPrice}</div>
                    </div>
                </td>
                <td>${entry.remarks || "N/A"}</td>
                <td class="text-center">
                    <span class="badge badge-${entry.status === 1 ? 'success' : (entry.status === 2 ? 'danger' : 'dark')}">
                        ${entry.status === 1 ? 'APPROVED' : (entry.status === 2 ? 'DENIED' : 'NO STATUS')}
                    </span>
                </td>
                <td>${username}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                        Action
                        <span class="sr-only">Toggle Dropdown</span>
                    </button>
                    <div class="dropdown-menu" role="menu">
                        <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="${entry.id}" ${entry.status === 1 ? 'style="pointer-events: none; color: #ccc; cursor: not-allowed;"' : ''}>
                            <span class="fa fa-trash text-danger"></span> Delete
                        </a>
                        <!-- Include Approve and Deny options for managers -->
                        ${entry.is_manager ? `
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item approve_data" href="javascript:void(0)" data-entry_code="${entry.entry_code}" data-status="1">
                                <span class="fa fa-check text-success"></span> Approve
                            </a>
                            <a class="dropdown-item deny_data" href="javascript:void(0)" data-entry_code="${entry.entry_code}" data-status="2">
                                <span class="fa fa-times text-danger"></span> Deny
                            </a>
                        ` : ''}
                    </div>
                </td>
            `;

                // Append the new row to the table
                tbody.appendChild(row);
            });
        } else {
            // If no filtered entries are found, show a message or leave the table empty
            tbody.innerHTML = `<tr><td colspan="8" class="text-center">No entries found for the given PO number.</td></tr>`;
        }
    }
</script>