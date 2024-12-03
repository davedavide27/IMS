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
        <!-- Title and Actions Dropdown -->
        <div style="display: flex; align-items: center; gap: 15px;">
            <h3 class="card-title">Sales Entries</h3>

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
            <?php endif; 
            ?>
        </div>

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
                    <col width="10%">
                    <col width="14%">
                    <col width="15%">
                    <col width="10%">
                    <col width="10%">
                    <col width="10%">
                    <col width="8%">
                    <col width="15%">
                    <col width="10%">
                </colgroup>
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" id="selectAll"><a> Select All</a>
                        </th>
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
                            $product_query = $conn->query("SELECT name FROM `products` WHERE id = '" . $conn->real_escape_string($row['product_id']) . "' ");
                            $product = $product_query ? $product_query->fetch_assoc() : array('name' => 'Unknown');
                            $total_price = $row['quantity'] * $row['selling_price'];
                    ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="selectItem" data-id="<?php echo $row['sales_code']; ?>">
                                    <input type="hidden" class="sales_code" value="<?php echo $row['sales_code']; ?>">
                                </td>
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
                                            <a class="dropdown-item approve_data" href="javascript:void(0)" data-status="1">
                                                <span class="fa fa-check text-success"></span> Approve
                                            </a>
                                            <a class="dropdown-item deny_data" href="javascript:void(0)" data-status="2">
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

        // Select All Checkbox Event
        $('#selectAll').change(function() {
            const isChecked = $(this).prop('checked');
            $(".selectItem").prop('checked', isChecked);
        });

        // Individual Checkbox Change Event
        $(".selectItem").change(function() {
            const isChecked = $(this).prop('checked');
            if (!isChecked) {
                $('#selectAll').prop('checked', false);
            } else if ($(".selectItem:checked").length === $(".selectItem").length) {
                $('#selectAll').prop('checked', true);
            }
        });

        // Approve or Deny Action
        $('.approve_data, .deny_data').click(function() {
            const status = $(this).data('status');
            const salesCodes = collectSelectedSalesCodes();

            if (salesCodes.length === 0) {
                alert_toast('No entries selected for approval/denial.', 'error');
                return;
            }

            fetch(_base_url_ + "classes/Master.php?f=update_sales_status", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        sales_codes: salesCodes,
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
    });

    // Function to Collect Selected Sales Codes
    function collectSelectedSalesCodes() {
        const selectedSalesCodes = [];
        $(".selectItem:checked").each(function() {
            const salesCode = $(this).data('id');
            if (salesCode) {
                selectedSalesCodes.push(salesCode);
            }
        });
        return selectedSalesCodes;
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
                    sessionStorage.setItem('delete_message', resp.msg); // Store the message in session
                    setTimeout(function() {
                        location.reload(); // Reload after 2 seconds
                    }, 2000); // Fixed timeout value
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
        let productFound = false; // Flag to check if any approved product is found

        rows.forEach((row, rowIndex) => {
            const cells = row.querySelectorAll('td');
            console.log('Processing row', rowIndex); // Log the row being processed

            // Assuming status is in the 6th column (index 5), PO number in 7th (index 6), and recorded by in 8th (index 7)
            const statusCell = cells[6]; // Adjusted index for status column
            const status = statusCell.textContent.trim().toUpperCase();
            console.log('Status found: ', status); // Log the status value


            // Check for approved status
            if (status == 'APPROVED') {
                const productName = cells[2].textContent.trim(); // Product name in column 3 (index 2)

                // Handle quantity extraction and cleanup (removing commas or non-numeric characters)
                const quantityStr = cells[3].textContent.trim().replace(/[^0-9.-]+/g, ""); // Clean quantity (remove commas or other symbols)
                const quantity = parseFloat(quantityStr) || 0; // Ensure quantity is numeric (defaults to 0 if NaN)

                const priceStr = cells[4].textContent.trim().replace(/[^0-9.-]+/g, ""); // Parse price as a number (remove currency symbol)
                const price = parseFloat(priceStr);

                const totalStr = cells[5].textContent.trim().replace(/[^0-9.-]+/g, ""); // Parse total as a number (remove currency symbol)
                const total = parseFloat(totalStr);

                console.log('Found APPROVED product:', cells[1].textContent.trim()); // Log when an APPROVED product is found

                // Set productFound flag to true since we've found an approved product
                productFound = true;

                // Create hidden input fields for the required data (product, quantity, selling price, total)
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

                const inputSellingPrice = document.createElement('input');
                inputSellingPrice.type = 'hidden';
                inputSellingPrice.name = `entries[${rowIndex}][price]`;
                inputSellingPrice.value = price;
                form.appendChild(inputSellingPrice);

                const inputTotal = document.createElement('input');
                inputTotal.type = 'hidden';
                inputTotal.name = `entries[${rowIndex}][total]`;
                inputTotal.value = total;
                form.appendChild(inputTotal);
            }
        });

        // If no approved product is found, log a message to the console
        if (!productFound) {
            alert_toast("No products with status 'APPROVED' found.", 'error');
        }

        // If an approved product is found, continue with the form submission
        if (productFound) {
            // Append the form to the body and submit it
            document.body.appendChild(form);
            form.submit();
        }
    }



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

                // Add checkbox to each row (for selecting the entry)
                const checkboxCell = `<td class="text-center"><input type="checkbox" class="entry-checkbox" data-id="${entry.id}"></td>`;

                // Populate row data, including the checkbox column
                row.innerHTML = `
                ${checkboxCell}
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
            tbody.innerHTML = `<tr><td colspan="9" class="text-center">No entries found for the given PO number.</td></tr>`;
        }
    }
</script>