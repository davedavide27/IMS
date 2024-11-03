<?php
function format_num($number)
{
    $decimals = 0;
    $num_ex = explode('.', $number);
    $decimals = isset($num_ex[1]) ? strlen($num_ex[1]) : 0;
    return number_format($number, $decimals);
}

$from = isset($_GET['from']) ? $_GET['from'] : date("Y-m-d", strtotime(date('Y-m-d') . " -1 month"));
$to = isset($_GET['to']) ? $_GET['to'] : date("Y-m-d");
$product_id = isset($_GET['product_id']) ? $_GET['product_id'] : '';

// Filter to fetch all products (not limited by date)
$product_query = $conn->query("SELECT DISTINCT p.id, p.name FROM products p ORDER BY p.name ASC");

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

// Count total records using the correct column name and product filter
$total_query = $conn->query("SELECT COUNT(*) as total FROM stock_reports sr 
    WHERE sr.report_date BETWEEN '{$from}' AND '{$to}'" . ($product_id ? " AND sr.product_id = '{$product_id}'" : ""));
$total_row = $total_query->fetch_assoc();
$total_records = $total_row['total'];
$total_pages = ceil($total_records / $limit);

// Initialize variables for totals
$total_stock_entries = 0;
$total_available_stocks = 0;
$total_stocks_sold = 0;

// Select stock reports using the correct column name and product filter
$stock_reports = $conn->query("SELECT sr.report_date, p.name as product_name, 
    sr.stock_entries, sr.available_stocks, sr.stocks_sold 
FROM stock_reports sr 
INNER JOIN products p ON sr.product_id = p.id 
WHERE sr.report_date BETWEEN '{$from}' AND '{$to}'" . ($product_id ? " AND sr.product_id = '{$product_id}'" : "") . "
LIMIT $limit OFFSET $offset");

// Initialize variables for totals
$stock_entries = [];
while ($row = $stock_reports->fetch_assoc()) {
    $total_stock_entries += $row['stock_entries'];
    $total_available_stocks += $row['available_stocks'];
    $total_stocks_sold += $row['stocks_sold'];
    $stock_entries[] = $row;
}

$stock_entries_json = json_encode($stock_entries);
?>

<style>
    /* ... existing styles ... */
</style>

<div class="card card-outline card-primary">
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
                        <input type="date" id="from" name="from" value="<?= $from ?>" class="form-control form-control-sm rounded-0">
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="to" class="control-label">Date To</label>
                        <input type="date" id="to" name="to" value="<?= $to ?>" class="form-control form-control-sm rounded-0">
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
                        <button class="btn btn-primary border btn-flat btn-sm" id="exportExcelButton" type="button">
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
                        <th class="text-center">Date</th>
                        <th class="text-center">Product</th>
                        <th class="text-center">Stock Entries</th>
                        <th class="text-center">Available Stocks</th>
                        <th class="text-center">Stocks Sold</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stock_entries as $row): ?>
                        <tr>
                            <td><?= date("M d, Y", strtotime($row['report_date'])) ?></td>
                            <td><?= $row['product_name'] ?></td>
                            <td class="text-right"><?= format_num($row['stock_entries']) ?></td>
                            <td class="text-right"><?= format_num($row['available_stocks']) ?></td>
                            <td class="text-right"><?= format_num($row['stocks_sold']) ?></td>
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
                targets: [3] // Make specific columns non-orderable
            }],
            search: {
                caseInsensitive: true // Case-insensitive search
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
            exportTableToExcel('reportsTable', 'Stock_Report');
        });

        function exportTableToExcel(tableID, filename = '') {
            var dataType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            var tableSelect = document.getElementById(tableID);
            var tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');

            // Create a link element
            var downloadLink = document.createElement("a");
            document.body.appendChild(downloadLink);
            downloadLink.href = 'data:' + dataType + ', ' + tableHTML;
            downloadLink.download = filename ? filename +'s' + '.xlsx' : 'excel_data.xlsx';
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }
    });
</script>
