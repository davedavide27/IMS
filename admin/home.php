<?php
function format_num($number)
{
    $decimals = 0;
    $num_ex = explode('.', $number);
    $decimals = isset($num_ex[1]) ? strlen($num_ex[1]) : 0;
    return number_format($number, $decimals);
}

$from = isset($_POST['start_date']) ? $_POST['start_date'] : date("Y-m-d", strtotime(date('Y-m-d') . " -1 month"));
$to = isset($_POST['end_date']) ? $_POST['end_date'] : date("Y-m-d");

// Fetch total sales within date range using prepared statements
$sales_stmt = $conn->prepare("SELECT SUM(total_price) AS total_sales FROM sales WHERE purchase_date BETWEEN ? AND ?");
$sales_stmt->bind_param("ss", $from, $to);
$sales_stmt->execute();
$sales_result = $sales_stmt->get_result();
$total_sales = $sales_result->fetch_assoc()['total_sales'] ?? 0;

// Fetch total purchases within date range using prepared statements
$purchase_stmt = $conn->prepare("SELECT SUM(purchase_price * quantity) AS total_purchase FROM inventory_entries JOIN products ON inventory_entries.product_id = products.id WHERE entry_date BETWEEN ? AND ?");
$purchase_stmt->bind_param("ss", $from, $to);
$purchase_stmt->execute();
$purchase_result = $purchase_stmt->get_result();
$total_purchase = $purchase_result->fetch_assoc()['total_purchase'] ?? 0;

// Calculate profit based on the date range
$profit = ($total_sales ?? 0) - ($total_purchase ?? 0);
?>


<!-- Date Range Filter Form -->


<div class="card-body">
    <div class="callout border-primary shadow rounded-0">
        <h4 class="text-muted">Filter Date</h4>
        <form action="" method="post" id="filter">
            <div class="row align-items-end">
                <div class="col-md-4 form-group">
                    <label for="from" class="control-label">Date From</label>
                    <input type="date" id="from" name="start_date" value="<?= htmlspecialchars($from) ?>" class="form-control form-control-sm rounded-0">
                </div>
                <div class="col-md-4 form-group">
                    <label for="to" class="control-label">Date To</label>
                    <input type="date" id="to" name="end_date" value="<?= htmlspecialchars($to) ?>" class="form-control form-control-sm rounded-0">
                </div>
                <div class="col-md-4 form-group">
                    <button class="btn btn-default bg-gradient-navy btn-flat btn-sm" type="submit"><i class="fa fa-filter"></i> Filter</button>
                </div>
            </div>
        </form>
    </div>
</div>
</div>

<!-- CSS for banner -->
<style>
    #banner-img {
        width: 100%;
        height: 40vh;
        object-fit: cover;
        object-position: center center;
    }
</style>

<!-- Header and Information Boxes -->
<h2>Welcome to <?php echo $_settings->info('name') ?> </h2>
<hr class="border-border bg-primary">
<div class="row">
    <div class="col-12 col-sm-12 col-md-6 col-lg-4">
        <div class="info-box bg-gradient-light shadow">
            <span class="info-box-icon bg-gradient-navy elevation-1"><i class="fas fa-warehouse"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Products</span>
                <span class="info-box-number text-right">
                    <?php echo $conn->query("SELECT * FROM `products` WHERE delete_flag = 0")->num_rows; ?>
                </span>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-12 col-md-6 col-lg-4">
        <div class="info-box bg-gradient-light shadow">
            <span class="info-box-icon bg-gradient-primary elevation-1"><i class="fas fa-file-invoice-dollar"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Sales</span>
                <span class="info-box-number text-right">
                    <?php echo '₱ ' . format_num($total_sales); ?>
                </span>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-12 col-md-6 col-lg-4">
        <div class="info-box bg-gradient-light shadow">
            <span class="info-box-icon bg-gradient-info elevation-1"><i class="fas fa-shopping-cart"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Inventory Entries</span>
                <span class="info-box-number text-right">
                    <?php echo $conn->query("SELECT * FROM `inventory_entries`")->num_rows; ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- New row for Total Purchases and Profit -->
<div class="row mt-3">
    <div class="col-12 col-sm-12 col-md-6 col-lg-4">
        <div class="info-box bg-gradient-light shadow">
            <span class="info-box-icon bg-gradient-success elevation-1"><i class="fas fa-shopping-cart"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Purchases</span>
                <span class="info-box-number text-right">
                    <?php echo '₱ ' . format_num($total_purchase); ?>
                </span>
            </div>
        </div>
    </div>
    <!-- Profit Box -->
    <div class="col-12 col-sm-12 col-md-6 col-lg-4">
        <div class="info-box bg-gradient-light shadow">
            <span class="info-box-icon bg-gradient-warning elevation-1"><i class="fas fa-coins"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Profit</span>
                <span class="info-box-number text-right">
                    <?php
                    if ($profit < 0) {
                        echo "No profit";
                    } else {
                        echo '₱ ' . format_num($profit);
                    }
                    ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Profit Box -->
    <div class="col-12 col-sm-12 col-md-6 col-lg-4">
        <div class="info-box bg-gradient-light shadow">
            <span class="info-box-icon bg-gradient-danger elevation-1"><i class="fas fa-money-bill-wave"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Deficit</span>
                <span class="info-box-number text-right">
                    <?php
                    if ($profit < 0) {
                        echo '₱ ' . format_num($profit);
                    } else {
                        echo "No Deficit";
                    }
                    ?>
                </span>
            </div>
        </div>
    </div>
</div>

<hr class="border-border bg-primary">
<div class="row">
    <div class="col-md-12">
        <img src="<?= validate_image($_settings->info('cover')) ?>" alt="Website Page" id="banner-img" class="w-100">
    </div>
</div>