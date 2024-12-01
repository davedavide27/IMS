<?php
// Function to format numbers with the correct number of decimal places
function format_num($number)
{
    $decimals = 0;
    if (strpos($number, '.') !== false) {
        $num_ex = explode('.', $number);
        $decimals = isset($num_ex[1]) ? strlen($num_ex[1]) : 0;
    }
    return number_format($number, $decimals);
}
?>

<?php
// Initialize $swhere as an empty string
$swhere = "";

// Check if the user is not an Admin (type 1) or Manager (type 3)
if ($_settings->userdata('type') != 1 && $_settings->userdata('type') != 3) {
    // Filter based on the logged-in user's ID
    $user_id = $_settings->userdata('id'); // Fetch user ID
    $swhere = " WHERE user_id = '" . $conn->real_escape_string($user_id) . "' ";
}

// Fetch user details for mapping
$user_arr = array(); // Initialize an empty array to store user data
$user_query = $conn->query("SELECT id, username FROM `users_inventory` WHERE id IN (SELECT `user_id` FROM `sales` {$swhere})");
if ($user_query) {
    while ($row = $user_query->fetch_assoc()) {
        $user_arr[$row['id']] = $row['username']; // Map user ID to username
    }
}

// Fetch sales entries with optional filter
$sales = $conn->query("
    SELECT 
        s.id, 
        s.purchase_date, 
        s.product_id, 
        s.quantity, 
        s.sales_code, 
        s.selling_price, 
        s.user_id, 
        s.status, 
        s.po_number 
    FROM 
        `sales` s 
    {$swhere} 
    ORDER BY 
        DATE(s.purchase_date) ASC
");

// Ensure the query was successful
if (!$sales) {
    die("Error fetching sales data: " . $conn->error);
}
?>



<script>
    // Convert PHP array to JavaScript object (mapping user_id to username)
    const userArr = <?php echo json_encode($user_arr); ?>;
</script>

<style>
    th.p-0,
    td.p-0 {
        padding: 0 !important;
    }
</style>
<div class="card card-outline card-primary">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3 class="card-title">Sales Entries</h3>
        <div style="display: flex; align-items: center; gap: 15px; justify-content: center;">
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
        <div class="card-tools" style="position: absolute; right: 0; padding-right: 20px;">
            <button class="btn btn-primary btn-flat btn-sm" id="create_new" type="button">
                <i class="fa fa-pen-square"></i> Add New Sales Entry
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <table class="table table-hover table-striped table-bordered" id="salesTable">
                <colgroup>
                    <col width="15%">
                    <col width="20%">
                    <col width="10%">
                    <col width="10%">
                    <col width="10%">
                    <col width="8%">
                    <col width="15%">
                    <col width="10%">
                </colgroup>
                <thead>
                    <tr>
                        <th>Purchase Date</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>PO Number</th>
                        <th>Recorded By</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Check user type and set conditions
                    $swhere = "";
                    if ($_settings->userdata('type') != 1 && $_settings->userdata('type') != 3) {
                        $swhere = " WHERE user_id = '" . $conn->real_escape_string($_settings->userdata('id')) . "' ";
                    }

                    // Fetch user details
                    $user_arr = array();
                    $users_query = $conn->query("SELECT id, username FROM `users_inventory` WHERE id IN (SELECT `user_id` FROM `sales` {$swhere})");
                    if ($users_query) {
                        while ($row = $users_query->fetch_assoc()) {
                            $user_arr[$row['id']] = $row['username'];
                        }
                    }

                    // Fetch sales entries
                    $sales_query = $conn->query("SELECT s.id, s.purchase_date, s.product_id, s.quantity, s.sales_code, s.selling_price, s.user_id, s.status, s.po_number 
                    FROM `sales` s {$swhere} ORDER BY DATE(s.purchase_date) ASC");

                    if ($sales_query) {
                        while ($row = $sales_query->fetch_assoc()):
                            // Fetch product details
                            $product_query = $conn->query("SELECT name FROM `products` WHERE id = '" . $conn->real_escape_string($row['product_id']) . "'");
                            $product = $product_query ? $product_query->fetch_assoc() : array('name' => 'Unknown');
                            $total_price = $row['quantity'] * $row['selling_price'];
                    ?>
                            <tr>
                                <td class="text-center"><?= htmlspecialchars(date("M d, Y", strtotime($row['purchase_date'])), ENT_QUOTES) ?></td>
                                <td class=""><?= htmlspecialchars($product['name'], ENT_QUOTES) ?></td>
                                <td class="text-right"><?= htmlspecialchars(format_num($row['quantity']), ENT_QUOTES) ?></td>
                                <td class="text-right">₱<?= htmlspecialchars(format_num($row['selling_price']), ENT_QUOTES) ?></td>
                                <td class="text-right">₱<?= htmlspecialchars(format_num($total_price), ENT_QUOTES) ?></td>
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
                                <td><?= isset($row['po_number']) && !empty($row['po_number']) ? htmlspecialchars($row['po_number'], ENT_QUOTES) : "N/A" ?></td>
                                <td><?= isset($user_arr[$row['user_id']]) ? htmlspecialchars($user_arr[$row['user_id']], ENT_QUOTES) : "N/A" ?></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                                        Action
                                        <span class="sr-only">Toggle Dropdown</span>
                                    </button>
                                    <div class="dropdown-menu" role="menu">
                                        <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="<?= htmlspecialchars($row['id'], ENT_QUOTES) ?>"
                                            <?php if ($row['status'] == 1) {
                                                echo 'style="pointer-events: none; color: #ccc; cursor: not-allowed;"';
                                            } ?>>
                                            <span class="fa fa-trash text-danger"></span> Delete
                                        </a>
                                        <?php if ($_settings->userdata('type') == 3): ?>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item approve_data" href="javascript:void(0)" data-sales_code="<?= htmlspecialchars($row['sales_code'], ENT_QUOTES) ?>" data-status="1">
                                                <span class="fa fa-check text-success"></span> Approve
                                            </a>
                                            <a class="dropdown-item deny_data" href="javascript:void(0)" data-sales_code="<?= htmlspecialchars($row['sales_code'], ENT_QUOTES) ?>" data-status="2">
                                                <span class="fa fa-times text-danger"></span> Deny
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                    <?php
                        endwhile;
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Open modal to create a new sales entry
        $('#create_new').click(function() {
            uni_modal("Add New Sales Entry", "sales/manage_sales.php", 'mid-large');
        });
        /*
        // Edit existing sales entry
        $(document).on('click', '.edit_data', function() {
            var id = $(this).data('id');
            uni_modal("Edit", "sales/edit_sales.php?id=" + id, 'mid-large');
        });
        */
        // Confirm and delete the sales entry
        $(document).on('click', '.delete_data', function() {
            var id = $(this).data('id');
            _conf("Are you sure to delete this entry permanently?", "delete_sale", [id]);
        });

        // Initialize DataTable with specific settings
        $('#salesTable').dataTable({
            columnDefs: [{
                orderable: false,
                targets: [5, 6] // Modify as needed based on the table columns
            }]
        });
    });
    // Handle the click event for the approve/deny options
    $(document).on('click', '.approve_data, .deny_data', function() {
        const sales_code = $(this).data('sales_code'); // Get the sales_code of the item
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
        _conf(`Are you sure you want to change the status to "${statusText}" for sales code "${sales_code}"?`, "update_sales_status", [sales_code, status]);
    });

    // Check for success message in sessionStorage when the page is ready
    $(document).ready(function() {
        // Check if there is a success message in sessionStorage
        var successMessage = sessionStorage.getItem('update_sales_status_message');
        if (successMessage) {
            // Display the success message using a custom toast or alert method
            alert_toast(successMessage, 'success');

            // Remove the message after displaying it to avoid showing it again on page reload
            sessionStorage.removeItem('update_sales_status_message');

            // Optional: Delay for 3 seconds before doing anything else if needed
            setTimeout(function() {
                // You can add additional logic here after the message duration ends
            }, 3000); // 3-second duration
        }
    });

    // Function to update the sales status
    function update_sales_status(sales_code, status) {
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=update_sales_status", // API endpoint to update the status
            method: 'POST',
            data: {
                sales_code: sales_code, // Pass the sales_code as a string
                status: status // Pass the status as a number (1 or 2)
            },
            dataType: 'json',
            success: function(resp) {
                if (resp.status === 'success') {
                    // Store the success message in sessionStorage for later use
                    sessionStorage.setItem('update_sales_status_message', resp.msg);

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



    // Function to delete sales entry
    function delete_sale(id) {
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=delete_sale", // API for deleting sale
            method: "POST",
            data: {
                id: id
            }, // Pass the sale ID as data
            dataType: "json",
            success: function(resp) {
                if (resp.status === 'success') {
                    if (resp.toast) {
                        eval(resp.toast); // Execute the toast message
                    }
                    // Use session storage to temporarily hold the message
                    sessionStorage.setItem('delete_message', resp.msg);
                    // Delay for 2 seconds before reloading the page
                    setTimeout(function() {
                        location.reload(); // Reload the page to reflect changes
                    }, ); // Adjust the time (in milliseconds) as needed
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

    // Display message from session storage after the page loads
    $(window).on('load', function() {
        var deleteMessage = sessionStorage.getItem('delete_message');
        if (deleteMessage) {
            alert_toast(deleteMessage, 'success'); // Show success message
            sessionStorage.removeItem('delete_message'); // Clear the message after displaying it
        }
    });
</script>

<script>
    function submitPrintForm() {
        const table = document.querySelector('#salesTable'); // Ensure this is the correct table ID or class

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
        form.action = window.location.origin + "/ajms/admin/sales/print_order.php?page=sales"; // Correct full path

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
            const productName = cells[1].textContent.trim(); // Product name (second column)
            const quantity = cells[2].textContent.trim(); // Quantity (third column)
            const price = cells[3].textContent.trim(); // Price (fourth column)
            const total = cells[4].textContent.trim(); // Total (fifth column)

            // Create hidden input fields for the required data (product, quantity, price, total)
            const inputProductName = document.createElement('input');
            inputProductName.type = 'hidden';
            inputProductName.name = `entries[${rowIndex}][product]`;
            inputProductName.value = productName;
            form.appendChild(inputProductName);

            const inputQuantity = document.createElement('input');
            inputQuantity.type = 'hidden';
            inputQuantity.name = `entries[${rowIndex}][quantity]`;
            inputQuantity.value = quantity;
            form.appendChild(inputQuantity);

            const inputPrice = document.createElement('input');
            inputPrice.type = 'hidden';
            inputPrice.name = `entries[${rowIndex}][price]`;
            inputPrice.value = price;
            form.appendChild(inputPrice);

            const inputTotal = document.createElement('input');
            inputTotal.type = 'hidden';
            inputTotal.name = `entries[${rowIndex}][total]`;
            inputTotal.value = total;
            form.appendChild(inputTotal);
        });

        // Append the form to the body and submit it
        document.body.appendChild(form);
        form.submit();
    }



    /*
    function printPage() {
        // Get the table and Action column index
        const table = document.getElementById('salesTable');
        const tableHeader = table.querySelector('thead tr');
        const actionColumnIndex = Array.from(tableHeader.cells).findIndex(cell => cell.textContent.trim() === 'Action');

        if (actionColumnIndex === -1) return; // If "Action" column is not found, exit

        // Temporarily hide Action column and dropdowns
        const rows = table.querySelectorAll('tr');
        rows.forEach(row => {
            const cells = row.children;
            if (cells[actionColumnIndex]) {
                cells[actionColumnIndex].style.display = 'none'; // Hide the Action column
            }
        });

        // Clone the table to avoid modifying the original table
        const tableClone = table.cloneNode(true);

        // Restore visibility of the Action column in the original table
        rows.forEach(row => {
            const cells = row.children;
            if (cells[actionColumnIndex]) {
                cells[actionColumnIndex].style.display = ''; // Restore visibility
            }
        });

        // Prepare print styles
        const styles = `
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            @media print {
                body { margin: 0; padding: 0; }
                table { margin-top: 20px; }
            }
        </style>
    `;

        // Create a new window for printing
        const printWindow = window.open('', '', 'height=1200,width=800');
        printWindow.document.write('<html><head><title>Print Table</title>' + styles + '</head><body>');
        printWindow.document.write('<h3>Sales Entries</h3>'); // Add a title to the print view
        printWindow.document.write(tableClone.outerHTML); // Add the cloned table to the print view
        printWindow.document.write('</body></html>');
        printWindow.document.close();

        // Handle print dialog and automatically close the window after a delay
        printWindow.focus();

        // Attach `onafterprint` to close the print window after a delay
        printWindow.onafterprint = function() {
            setTimeout(() => {
                printWindow.close(); // Close the print window after 2 seconds
            }, 2000); // Delay of 2 seconds (2000 milliseconds)
        };

        printWindow.print(); // Trigger the print dialog
    }
    */
    //-----------------------------------------------------------------------------------------------------

    // Function to handle the filtering by PO Number
    document.getElementById('filterForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent form from reloading the page

        const poNumber = document.getElementById('po_number_filter').value.trim().toUpperCase(); // Convert input to uppercase

        if (poNumber) {
            // Call the function to filter sales entries by PO number
            filterEntriesByPONumber(poNumber);
        } else {
            // If no PO number is provided, show all entries again
            resetFilter();
        }
    });

    // Function to filter sales entries by PO number
    function filterEntriesByPONumber(poNumber) {
        console.log('Filtering entries by PO Number:', poNumber);

        // Check if PO number is provided
        if (!poNumber) {
            alert_toast("Please provide a valid PO number.", 'warning');
            return;
        }

        // Send the PO number to the API endpoint
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=filter_sales_by_po_number", // API endpoint for filtering
            method: "POST",
            data: {
                po_number: poNumber
            },
            success: function(response) {
                try {
                    const result = JSON.parse(response);

                    if (result.status === "success") {
                        // Handle filtered data (for example, updating the table with filtered entries)
                        if (result.filteredEntries && result.filteredEntries.length > 0) {
                            updateTableWithFilteredEntries(result.filteredEntries);
                        } else {
                            // If no entries are found, display a message
                            alert_toast("No entries found for the given PO number.", 'info');
                            updateTableWithFilteredEntries([]); // Clear the table
                        }
                    } else {
                        // Handle unsuccessful response
                        alert_toast(result.msg || "Something went wrong.", 'error');
                        updateTableWithFilteredEntries([]); // Clear the table on error
                    }
                } catch (error) {
                    console.error('Error parsing response:', error);
                    alert_toast("An error occurred while processing the response.", 'error');
                    updateTableWithFilteredEntries([]); // Clear the table on error
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                alert_toast("An error occurred while filtering entries.", 'error');
                updateTableWithFilteredEntries([]); // Clear the table on error
            }
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

    // Function to update the table with filtered entries
    function updateTableWithFilteredEntries(entries) {
        const tbody = document.querySelector('#salesTable tbody');
        tbody.innerHTML = ''; // Clear current rows
        // Define the status map for badges
        const statusMap = {
            0: '<span class="badge badge-dark">NO STATUS</span>',
            1: '<span class="badge badge-success bg-gradient-success">APPROVED</span>',
            2: '<span class="badge badge-danger bg-gradient-danger">DENIED</span>',
        };

        if (entries.length > 0) {
            entries.forEach(entry => {
                // Get the username from userArr, defaulting to "N/A" if not found
                const username = (userArr && userArr[entry.user_id]) ? userArr[entry.user_id] : "N/A";
                // Get the badge HTML based on status
                const statusBadge = statusMap[entry.status] || statusMap[0];

                // Create a new row for each entry
                const row = document.createElement('tr');

                // Populate row data
                row.innerHTML = `
                <td class="text-center">${formatDate(entry.purchase_date)}</td>
                <td class=""><?= htmlspecialchars($product['name'], ENT_QUOTES) ?></td>
                <td class="text-right">${entry.quantity}</td>
                <td class="text-right">₱${entry.selling_price}</td>
                <td class="text-right">₱${entry.total_price}</td>
                <td class="text-center">${statusBadge}</td>
                <td>${entry.po_number || "N/A"}</td>
                <td>${username}</td> <!-- Display the username here -->
                <td class="text-center">
                    <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                        Action
                        <span class="sr-only">Toggle Dropdown</span>
                    </button>
                    <div class="dropdown-menu" role="menu">
                        <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="${entry.id}" ${entry.status == 1 ? 'style="pointer-events: none; color: #ccc; cursor: not-allowed;"' : ''}>
                            <span class="fa fa-trash text-danger"></span> Delete
                        </a>
                        <!-- Include Approve and Deny options for managers -->
                        ${entry.is_manager ? `
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item approve_data" href="javascript:void(0)" data-sales_code="${entry.sales_code}" data-status="1">
                                <span class="fa fa-check text-success"></span> Approve
                            </a>
                            <a class="dropdown-item deny_data" href="javascript:void(0)" data-sales_code="${entry.sales_code}" data-status="2">
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