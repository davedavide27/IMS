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

$limit = 10; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1; // Ensure page is at least 1
}
$offset = ($page - 1) * $limit;

// Count total records
$total_query = $conn->query("SELECT COUNT(*) as total FROM inventory_entries ie 
    INNER JOIN products p ON ie.product_id = p.id 
    INNER JOIN stocks s ON ie.product_id = s.product_id 
    WHERE ie.entry_date BETWEEN '{$from}' AND '{$to}'");
$total_row = $total_query->fetch_assoc();
$total_records = $total_row['total'];
$total_pages = ceil($total_records / $limit);

// Initialize variables for totals
$total_purchase = 0;
$total_selling = 0;
$total_sales = 0; // New variable for total sales

$inventory = $conn->query("SELECT ie.entry_date, p.id AS product_id, p.name as product_name, ie.description, 
p.purchase_price, p.selling_price, 
LEAST(ie.quantity, s.available_stocks) as quantity, 
(p.purchase_price * LEAST(ie.quantity, s.available_stocks)) as total_purchase,
(p.selling_price * LEAST(ie.quantity, s.available_stocks)) as total_selling 
FROM inventory_entries ie 
INNER JOIN products p ON ie.product_id = p.id 
INNER JOIN stocks s ON ie.product_id = s.product_id 
WHERE ie.entry_date BETWEEN '{$from}' AND '{$to}'
LIMIT $limit OFFSET $offset");

// Fetch sales data based on the date range and pagination
$sales_query = $conn->query("SELECT s.purchase_date, p.id AS product_id, p.name as product_name, 
s.quantity, s.selling_price, 
(s.selling_price * s.quantity) as total_sales,
stk.available_stocks
FROM sales s 
INNER JOIN products p ON s.product_id = p.id 
INNER JOIN stocks stk ON s.product_id = stk.product_id 
WHERE s.purchase_date BETWEEN '{$from}' AND '{$to}' 
LIMIT $limit OFFSET $offset");


// Initialize variables for totals
$total_quantity_sold = 0;
$total_sales = 0;
$sales_items = [];

// Fetch data and calculate totals
while ($row = $sales_query->fetch_assoc()) {
    $total_quantity_sold += $row['quantity'];
    $total_sales += $row['total_sales'];
    $sales_items[] = $row;
}

$sales_items_json = json_encode($sales_items);
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
    <div class="card-header">
        <h3 class="card-title">Total Sales Reports</h3>
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
                        <button class="btn btn-default bg-gradient-navy btn-flat btn-sm" type="submit">
                            <i class="fa fa-filter"></i> Filter
                        </button>
                        <button class="btn btn-default border btn-flat btn-sm" id="printButton" type="button">
                            <i class="fa fa-print"></i> Print
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <div class="container-fluid" id="outprint">
            <h3 class="text-center"><b><?= $_settings->info('name') ?></b></h3>
            <h4 class="text-center"><b>Total Sales Reports</b></h4>
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
                        <th class="text-center">Total Stocks Sold</th>
                        <th class="text-center">Selling Price</th>
                        <th class="text-center">Remaining Stocks</th> <!-- New column -->
                        <th class="text-center">Sales</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sales_items as $row): ?>
                        <tr>
                            <td><?= date("M d, Y", strtotime($row['purchase_date'])) ?></td>
                            <td><?= $row['product_name'] ?></td>
                            <td class="text-right"><?= format_num($row['quantity']) ?></td>
                            <td class="text-right"><?= format_num($row['selling_price']) ?></td>
                            <td class="text-right"><?= format_num($row['available_stocks']) ?></td>
                            <td class="text-right"><?= format_num($row['total_sales']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="5" class="text-center">Total Sales:</th>
                        <th class="text-right"><?= format_num($total_sales) ?></th>

                    </tr>
                </tfoot>
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
            location.href = "./?page=reports/sales&" + $(this).serialize();
        });

        $('#printButton').click(function() {
            start_loader();

            var _h = $('head').clone();
            var _p = $('#outprint').clone();

            // Remove DataTable controls (pagination, search, etc.)
            _p.find('.dataTables_wrapper').find('.dataTables_filter, .dataTables_info, .dataTables_paginate, .dataTables_length').remove();

            // Create a new element for printing
            var el = $('<div>');
            _h.find('title').text('Sales Report - Print View');
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
    });
</script>