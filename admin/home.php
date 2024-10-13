<style>
    #banner-img {
        width: 100%;
        height: 40vh;
        object-fit: cover;
        object-position: center center;
    }
</style>
<h1>Welcome to <?php echo $_settings->info('name') ?> </h1>
<hr class="border-border bg-primary">
<div class="row">
    <div class="col-12 col-sm-12 col-md-6 col-lg-4">
        <div class="info-box bg-gradient-light shadow">
            <span class="info-box-icon bg-gradient-navy elevation-1"><i class="fas fa-box"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">Products</span>
                <span class="info-box-number text-right">
                    <?php
                    echo $conn->query("SELECT * FROM `products` WHERE delete_flag = 0")->num_rows;
                    ?>
                </span>
            </div>
            <!-- /.info-box-content -->
        </div>
        <!-- /.info-box -->
    </div>
    <div class="col-12 col-sm-12 col-md-6 col-lg-4">
    <div class="info-box bg-gradient-light shadow">
        <span class="info-box-icon bg-gradient-primary elevation-1"><i class="fas fa-th-list"></i></span>

        <div class="info-box-content">
            <span class="info-box-text">Total Sales</span>
            <span class="info-box-number text-right">
                <?php
                // Fetch the total sum of prices from the sales table
                $result = $conn->query("SELECT SUM(total_price) AS total_sales FROM sales");
                $total_sales = $result->fetch_assoc()['total_sales']; // Get the total_sales value
                echo '₱ ' . number_format($total_sales, 2);
                ?>
            </span>
        </div>
    <!-- /.info-box-content -->
</div>
<!-- /.info-box -->
</div>
<div class="col-12 col-sm-12 col-md-6 col-lg-4">
    <div class="info-box bg-gradient-light shadow">
        <span class="info-box-icon bg-gradient-info elevation-1"><i class="fas fa-clipboard-list"></i></span>

        <div class="info-box-content">
            <span class="info-box-text">Inventory Entries</span>
            <span class="info-box-number text-right">
                <?php
                echo $conn->query("SELECT * FROM `inventory_entries`")->num_rows;
                ?>
            </span>
        </div>
        <!-- /.info-box-content -->
    </div>
    <!-- /.info-box -->
</div>
</div>
<hr class="border-border bg-primary">
<div class="row">
    <div class="col-md-12">
        <img src="<?= validate_image($_settings->info('cover')) ?>" alt="Website Page" id="banner-img" class="w-100">
    </div>
</div>