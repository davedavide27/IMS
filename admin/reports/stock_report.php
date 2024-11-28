<?php
function format_num($number)
{
    $decimals = 0;
    $num_ex = explode('.', $number);
    $decimals = isset($num_ex[1]) ? strlen($num_ex[1]) : 0;
    return number_format($number, $decimals);
}

// Set default date values
$from = isset($_GET['from']) ? $_GET['from'] : date("Y-m-d H:i", strtotime(date('Y-m-d') . " -1 month"));
$to = isset($_GET['to']) ? $_GET['to'] : date("Y-m-d H:i", strtotime('+1 day'));
$product_id = isset($_GET['product_id']) ? $_GET['product_id'] : '';

// Filter to fetch all products (not limited by date)
$product_query = $conn->query("SELECT DISTINCT p.id, p.name, p.purchase_price, p.selling_price FROM products p ORDER BY p.name ASC");

$products = [];
while ($product_row = $product_query->fetch_assoc()) {
    $products[] = $product_row;
}

$limit = 10; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1; // Ensure page is at least 1
}
$offset = ($page - 1) * $limit;

$total_query = $conn->query(
    "SELECT COUNT(*) as total FROM inventory_entries ie 
    INNER JOIN products p ON ie.product_id = p.id 
    INNER JOIN stocks s ON ie.product_id = s.product_id 
    WHERE ie.entry_date BETWEEN '{$from}' AND '{$to}'" . 
    ($product_id ? " AND ie.product_id = {$product_id}" : "")
);

// Initialize variables for totals
$total_stock_entries = 0;
$total_available_stocks = 0;
$total_stocks_sold = 0;
$total_purchase_price = 0;
$total_selling_price = 0;

$stock_reports_result = $conn->query("SELECT sr.id, sr.report_datetime, sr.product_id, p.name as product_name, 
    sr.stock_entries, sr.available_stocks, sr.stocks_sold, sr.status, sr.entry_type,
    p.purchase_price, p.selling_price
FROM stock_reports sr 
INNER JOIN products p ON sr.product_id = p.id 
WHERE sr.report_datetime BETWEEN '{$from}' AND '{$to}'" . 
    ($product_id ? " AND sr.product_id = '{$product_id}'" : "") . "
ORDER BY sr.report_datetime ASC");


// Initialize variables for stock entries
$stock_entries = [];
while ($row = $stock_reports_result->fetch_assoc()) {
    $total_stock_entries += $row['stock_entries'];
    $total_available_stocks += $row['available_stocks'];
    $total_stocks_sold += $row['stocks_sold'];

    // Calculate total purchase and selling price based on stock entries
    if ($row['entry_type'] == 0) { // Inventory entry
        $total_purchase_price += $row['stock_entries'] * $row['purchase_price'];
    } elseif ($row['entry_type'] == 1) { // Sales entry
        $total_selling_price += $row['stocks_sold'] * $row['selling_price'];
    }

    $stock_entries[] = $row;
}

// Function to fetch product details by product_id
function get_product_by_id($product_id)
{
    global $conn; // Ensure the connection is accessible inside the function
    $sql = "SELECT purchase_price, selling_price FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

$stock_entries_json = json_encode($stock_entries);
?>



<style>
    @media print {
        .container {
            width: 100%;
        }

        .row {
            display: flex;
            flex-direction: column;
        }

        .col-md-4 {
            width: 100%;
            margin-bottom: 10px;
        }

        .bg-light {
            border: 1px solid #000;
            padding: 10px;
            background-color: #f8f9fa;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 5px;
        }

        /* Styles for Status and Entry Type without box/badge effect */
        .status-available {
            color: #007bff;
            /* Primary blue color */
            font-weight: bold;
        }

        .status-deleted {
            color: #dc3545;
            /* Danger red color */
            font-weight: bold;
        }

        .status-unknown {
            color: #ffc107;
            /* Warning yellow color */
            font-weight: bold;
        }

        .type-inventory {
            color: #007bff;
            /* Primary blue color */
            font-weight: bold;
        }

        .type-sales {
            color: #dc3545;
            /* Danger red color */
            font-weight: bold;
        }

        .type-unknown {
            color: #ffc107;
            /* Warning yellow color */
            font-weight: bold;
        }
    }

    th.p-0,
    td.p-0 {
        padding: 0 !important;
    }
</style>



<div class="card card-outline card-primary">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
    <div class="card-header">
        <h3 class="card-title">Stock Reports</h3>
    </div>
    <div class="card-body">
        <div class="callout border-primary shadow rounded-0">
            <h4 class="text-muted">Filter Date</h4>
            <form action="" id="filter">
                <div class="row align-items-end">
                    <div class="col-md-4 form-group">
                        <label for="from" class="control-label">Date From</label>
                        <input type="datetime-local" id="from" name="from" value="<?= date("Y-m-d\TH:i", strtotime($from)) ?>" class="form-control form-control-sm rounded-0">
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="to" class="control-label">Date To</label>
                        <input type="datetime-local" id="to" name="to" value="<?= date("Y-m-d\TH:i", strtotime($to)) ?>" class="form-control form-control-sm rounded-0">
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="product_id" class="control-label">Product</label>
                        <select id="product_id" name="product_id" class="form-control form-control-sm rounded-0 select2" style="width: 40%;">
                            <option value="" disabled <?= !$product_id ? 'selected' : '' ?>>All Products</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?= $product['id'] ?>" <?= ($product_id && $product['id'] == $product_id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($product['name'], ENT_QUOTES) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <button class="btn btn-default bg-gradient-navy btn-flat btn-sm" type="submit">
                            <i class="fa fa-filter"></i> Filter
                        </button>
                        <button class="btn btn-default border btn-flat btn-sm" id="printButton" type="button">
                            <i class="fa fa-print"></i> Print
                        </button>
                        <button class="btn btn-success border btn-flat btn-sm" id="exportExcelButton" type="button">
                            <i class="fa fa-file-excel"></i> Export to Excel
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <div class="container-fluid" id="outprint">
            <h3 class="text-center"><b><?= $_settings->info('name') ?></b></h3>
            <h4 class="text-center"><b>Total Stock Reports</b></h4>
            <?php if ($from == $to): ?>
                <p class="m-0 text-center"><?= date("M d, Y", strtotime($from)) ?></p>
            <?php else: ?>
                <p class="m-0 text-center"><?= date("M d, Y", strtotime($from)) . ' - ' . date("M d, Y", strtotime($to)) ?></p>
            <?php endif; ?>
            <hr>

            <table class="table table-hover table-bordered" id="reportsTable">
                <thead>
                    <tr>
                        <th class="text-center">Entry No.</th>
                        <th class="text-center">Date</th>
                        <th class="text-center">Product</th>
                        <th class="text-center">Stock Entries</th>
                        <th class="text-center">Available Stocks</th>
                        <th class="text-center">Stocks Sold</th>
                        <th class="text-center">Purchase Price Total</th>
                        <th class="text-center">Selling Price Total</th>
                        <th class="text-center">Entry Status</th>
                        <th class="text-center">Entry Type</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $entry_no = 1; // Starting entry number
                    foreach ($stock_entries as $entry): ?>
                        <tr>
                            <td class="text-center"><?= $entry_no++ ?></td>
                            <td class="text-center"><?= date("M d, Y h:i A", strtotime($entry['report_datetime'])) ?></td>
                            <td class="text-center"><?= htmlspecialchars($entry['product_name'], ENT_QUOTES) ?></td>
                            <td class="text-center"><?= format_num($entry['stock_entries']) ?></td>
                            <td class="text-center"><?= format_num($entry['available_stocks']) ?></td>
                            <td class="text-center"><?= format_num($entry['stocks_sold']) ?></td>
                            <td class="text-center">₱<?= format_num($entry['stock_entries'] * $entry['purchase_price']) ?></td>
                            <td class="text-center">₱<?= format_num($entry['stocks_sold'] * $entry['selling_price']) ?></td>
                            <td class="text-center">
                                <?php
                                if (isset($entry['status'])) {
                                    echo $entry['status'] == 1
                                        ? '<span class="badge badge-primary bg-gradient-primary">Available</span>'
                                        : '<span class="badge badge-danger bg-gradient-danger">Deleted</span>';
                                } else {
                                    echo '<span class="badge badge-warning">Unknown Status</span>';
                                }
                                ?>
                            </td>
                            <td class="text-center">
                                <?php
                                if (isset($entry['entry_type'])) {
                                    echo $entry['entry_type'] == 1
                                        ? '<span class="badge badge-primary bg-gradient-primary">Inventory</span>'
                                        : '<span class="badge badge-danger bg-gradient-danger">Sales</span>';
                                } else {
                                    echo '<span class="badge badge-warning">Unknown Type</span>';
                                }
                                ?>
                            </td>
                        </tr>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>



            <script>
                $(document).ready(function() {
                    $('#reportsTable').DataTable({
                        paging: true, // Ensure pagination is enabled
                        pageLength: 10, // Number of rows per page
                        lengthChange: true, // Allow the user to change the number of rows per page
                        lengthMenu: [10, 25, 50, 100], // Option to change the number of rows shown per page
                        columnDefs: [{
                            orderable: false,
                            targets: [3]
                        }],
                        search: {
                            caseInsensitive: true
                        }
                    });


                    $('#filter').submit(function(e) {
                        e.preventDefault();
                        location.href = "./?page=reports/stock_report&" + $(this).serialize();
                    });

                    // Initialize Select2 for the product dropdown with search capability
                    $('#product_id').select2({
                        placeholder: "All Products",
                        allowClear: true
                    });

                    $('#printButton').click(function() {
                        start_loader();

                        var _h = $('head').clone();
                        var _p = $('#outprint').clone();

                        // Remove DataTable controls (pagination, search, etc.)
                        _p.find('.dataTables_wrapper').find('.dataTables_filter, .dataTables_info, .dataTables_paginate, .dataTables_length').remove();

                        // Create a new element for printing
                        var el = $('<div>');
                        _h.find('title').text('Stock Report - Print View');
                        _h.append('<style>html,body{ min-height: unset !important;}</style>');
                        el.append(_h);
                        el.append(_p);

                        var nw = window.open("", "_blank", "width=900,height=700,top=50,left=250");
                        nw.document.write(el.html());
                        nw.document.close();

                        setTimeout(() => {
                            nw.print();
                            setTimeout(() => {
                                nw.close();
                                end_loader();
                            }, 1000);
                        }, 2000);
                    });

                    $('#exportExcelButton').click(function() {
                        exportTableToExcel('reportsTable', 'Stock_Reports');
                    });

                    function exportTableToExcel(tableID, filename = '') {
                        // Get the table element
                        var table = document.getElementById(tableID);
                        var data = [];

                        // Add the header row
                        var headerRow = [];
                        var headers = table.rows[0].cells; // Assuming the first row is the header
                        for (var i = 0; i < headers.length; i++) {
                            headerRow.push(headers[i].innerText);
                        }
                        data.push(headerRow); // Add header row to the data array

                        // Create an array to store max widths for each column
                        var maxColumnWidths = headerRow.map(header => header.length + 5); // +5 for padding

                        // Loop through each row in the table (starting from the second row)
                        for (var i = 1; i < table.rows.length; i++) {
                            var rowData = [];
                            var row = table.rows[i];
                            // Loop through each cell in the row
                            for (var j = 0; j < row.cells.length; j++) {
                                var cellText = row.cells[j].innerText;
                                rowData.push(cellText); // Add each cell data to the row data

                                // Update max width for the column if the current cell text is longer
                                maxColumnWidths[j] = Math.max(maxColumnWidths[j], cellText.length + 5); // +5 for padding
                            }
                            data.push(rowData); // Add each row to the data array
                        }

                        // Create a new workbook and add the data as a worksheet
                        var wb = XLSX.utils.book_new();
                        var ws = XLSX.utils.aoa_to_sheet(data);

                        // Set column widths based on the maximum content width
                        var columnWidths = maxColumnWidths.map(width => ({
                            wch: width
                        }));
                        ws['!cols'] = columnWidths;

                        XLSX.utils.book_append_sheet(wb, ws, "Sheet1");

                        // Specify the filename
                        filename = filename ? filename + '.xlsx' : 'excel_data.xlsx';

                        // Write the workbook and trigger download
                        XLSX.writeFile(wb, filename);
                    }
                });
            </script>