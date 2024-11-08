<?php

// Date range for filtering
$from = isset($_GET['from']) ? $_GET['from'] : date("Y-m-d", strtotime(date('Y-m-d') . " -1 month"));
$to = isset($_GET['to']) ? $_GET['to'] : date("Y-m-d");


// Filter to fetch all products (not limited by date)
$product_query = $conn->query("SELECT DISTINCT p.id, p.name FROM products p
    ORDER BY p.name ASC");

$products = [];
while ($product_row = $product_query->fetch_assoc()) {
    $products[] = $product_row;
}

// Format number function
function format_num($number)
{
    $decimals = 0;
    $num_ex = explode('.', $number);
    $decimals = isset($num_ex[1]) ? strlen($num_ex[1]) : 0;
    return number_format($number, $decimals);
}

// Pagination variables
$limit = 10; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1; // Ensure page is at least 1
}
$offset = ($page - 1) * $limit;

// Initialize variables for totals
$total_purchase = 0;
$total_selling = 0;

// Filter by product if a specific product is selected
$product_filter = "";
if (isset($_GET['product_id']) && !empty($_GET['product_id'])) {
    $product_id = $_GET['product_id'];
    $product_filter = " AND ie.product_id = '{$product_id}'";
}

// Count total records for pagination
$total_query = $conn->query("SELECT COUNT(*) as total FROM inventory_entries ie 
    INNER JOIN products p ON ie.product_id = p.id 
    INNER JOIN stocks s ON ie.product_id = s.product_id 
    WHERE ie.entry_date BETWEEN '{$from}' AND '{$to}' {$product_filter}");

$total_row = $total_query->fetch_assoc();
$total_records = $total_row['total'];
$total_pages = ceil($total_records / $limit);

// Main inventory query with date and product filters
$inventory = $conn->query("SELECT ie.entry_date, p.id AS product_id, p.name as product_name, ie.description, 
    p.purchase_price, p.selling_price, 
    LEAST(ie.quantity, s.available_stocks) as quantity, 
    (p.purchase_price * LEAST(ie.quantity, s.available_stocks)) as total_purchase,
    (p.selling_price * LEAST(ie.quantity, s.available_stocks)) as total_selling 
FROM inventory_entries ie 
INNER JOIN products p ON ie.product_id = p.id 
INNER JOIN stocks s ON ie.product_id = s.product_id 
WHERE ie.entry_date BETWEEN '{$from}' AND '{$to}' {$product_filter}
LIMIT $limit OFFSET $offset");

// Fetch data and calculate totals
$inventory_items = [];
while ($row = $inventory->fetch_assoc()) {
    $total_purchase += $row['total_purchase'];
    $total_selling += $row['total_selling'];
    $inventory_items[] = $row; // Store each row
}
$inventory_items_json = json_encode($inventory_items);
?>


<style>
    @media print {
        .container {
            width: 100%;
        }

        .row {
            display: flex;
            flex-direction: column;
            /* Stack the columns vertically */
        }

        .col-md-4 {
            width: 100%;
            /* Full width for each column */
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
    }



    th.p-0,
    td.p-0 {
        padding: 0 !important;
    }
</style>

<div class="card card-outline card-primary">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <div class="card-header">
        <h3 class="card-title">Total Purchase Reports</h3>
    </div>
    <div class="card-body">
        <div class="callout border-primary shadow rounded-0">
            <h4 class="text-muted">Filter Date</h4>
            <form action="" id="filter">
                <div class="row align-items-end">
                    <div class="col-md-4 form-group">
                        <label for="from" class="control-label">Date From</label>
                        <input type="date" id="from" name="from" value="<?= $from ?>" class="form-control form-control-sm rounded-0">
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="to" class="control-label">Date To</label>
                        <input type="date" id="to" name="to" value="<?= $to ?>" class="form-control form-control-sm rounded-0">
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="product_id" class="control-label">Product</label>
                        <select id="product_id" name="product_id" class="form-control form-control-sm rounded-0 select2" style="width: 40%;">
                            <option value="" disabled selected>All Products</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?= $product['id'] ?>" <?= (isset($product_id) && $product['id'] == $product_id) ? 'selected' : '' ?>>
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
            <h4 class="text-center"><b>Total Purchase Reports</b></h4>
            <?php if ($from == $to): ?>
                <p class="m-0 text-center"><?= date("M d, Y", strtotime($from)) ?></p>
            <?php else: ?>
                <p class="m-0 text-center"><?= date("M d, Y", strtotime($from)) . ' - ' . date("M d, Y", strtotime($to)) ?></p>
            <?php endif; ?>
            <hr>
            <div class="container mt-3" style="max-width: 80%;">
                <div class="row">
                    <div class="col-md-4">
                        <div class="bg-light p-2 rounded shadow">
                            <h5>Remaining Purchase</h5>
                            <p class="text-right"><?= format_num($total_purchase) ?></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-light p-2 rounded shadow">
                            <h5>Remaining Sales</h5>
                            <p class="text-right"><?= format_num($total_selling) ?></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-light p-2 rounded shadow">
                            <h5>Remaining Profit</h5>
                            <p class="text-right"><?= format_num($total_selling - $total_purchase) ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <br>
            <table class="table table-hover table-bordered" id="reportsTable">
                <thead>
                    <tr>
                        <th class="text-center">Date</th>
                        <th class="text-center">Product</th>
                        <th class="text-center">Description</th>
                        <th class="text-center">Purchase Price</th>
                        <th class="text-center">Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($inventory_items as $row): // Fetching from array instead of database
                    ?>
                        <tr>
                            <td><?= date("M d, Y", strtotime($row['entry_date'])) ?></td>
                            <td><?= $row['product_name'] ?></td>
                            <td><?= $row['description'] ?></td>
                            <td class="text-right"><?= format_num($row['purchase_price']) ?></td>
                            <td class="text-right"><?= format_num($row['quantity']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#reportsTable').DataTable({
            columnDefs: [{
                orderable: false,
                targets: [3, 4] // Make specific columns non-orderable
            }],
            search: {
                caseInsensitive: true // Case-insensitive search
            }
        });

        $('#filter').submit(function(e) {
            e.preventDefault();
            location.href = "./?page=reports/purchases&" + $(this).serialize();
        });
        $(document).ready(function() {
            // Initialize Select2 for the product dropdown with search capability
            $('#product_id').select2({
                placeholder: "All Products",
                allowClear: true
            });
        });


        $('#printButton').click(function() {
            start_loader();

            var _h = $('head').clone();
            var _p = $('#outprint').clone();

            // Remove DataTable controls (pagination, search, etc.)
            _p.find('.dataTables_wrapper').find('.dataTables_filter, .dataTables_info, .dataTables_paginate, .dataTables_length').remove();

            // Create a new element for printing
            var el = $('<div>');
            _h.find('title').text('Purchase Report - Print View');
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
            exportTableToExcel('reportsTable', 'Purchase_Report');
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