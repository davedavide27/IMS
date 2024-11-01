<?php
require_once('../../config.php');
if (isset($_GET['id'])) {
    $qry = $conn->query("SELECT ie.*, p.name as product_name, u.username as recorded_by, p.purchase_price FROM `inventory_entries` ie 
                         JOIN `products` p ON ie.product_id = p.id 
                         JOIN `users` u ON ie.user_id = u.id 
                         WHERE ie.id = '{$_GET['id']}'");
    if ($qry->num_rows > 0) {
        $res = $qry->fetch_array();
        foreach ($res as $k => $v) {
            if (!is_numeric($k)) $$k = $v;
        }
        $total_price = $quantity * $purchase_price; // Calculate total price
    }
}
?>
<style>
    #uni_modal .modal-footer {
        display: none !important;
    }

    .description {
        word-wrap: break-word;
        overflow-wrap: break-word;
        max-height: 300px;
        overflow: hidden;
    }
</style>

<div class="container-fluid">
    <dl>
        <!-- Entry Date -->
        <dt class="text-muted">Date</dt>
        <dd class='pl-4'>
            <p class=""><?= isset($entry_date) ? date("M d, Y", strtotime($entry_date)) : 'N/A' ?></p>
        </dd>

        <!-- Entry Code -->
        <dt class="text-muted">Entry Code</dt>
        <dd class='pl-4 fs-4 fw-bold'><?= isset($entry_code) ? $entry_code : '' ?></dd>

        <!-- Product -->
        <dt class="text-muted">Product</dt>
        <dd class='pl-4'>
            <p class=""><?= isset($product_name) ? $product_name : 'N/A' ?></p>
        </dd>

        <!-- Description -->
        <dt class="text-muted">Description</dt>
        <dd class='pl-4'>
            <p class=""><?= isset($description) ? $description : 'N/A' ?></p>
        </dd>

        <!-- Description -->
        <dt class="text-muted">Remarks</dt>
        <dd class='pl-4'>
            <p class=""><?= isset($remarks) ? $remarks : 'N/A' ?></p>
        </dd>

        <!-- Quantity -->
        <dt class="text-muted">Quantity</dt>
        <dd class='pl-4'>
            <p class=""><?= isset($quantity) ? number_format($quantity) : 'N/A' ?></p>
        </dd>

        <!-- Total Price -->
        <dt class="text-muted">Total Price</dt>
        <dd class='pl-4'>
            <p class=""><?= isset($total_price) ? number_format($total_price, 2) : 'N/A' ?></p>
        </dd>

        <!-- Recorded By -->
        <dt class="text-muted">Recorded By</dt>
        <dd class='pl-4'>
            <p class=""><?= isset($recorded_by) ? $recorded_by : 'N/A' ?></p>
        </dd>
    </dl>

    <!-- Close Button -->
    <div class="col-12 text-right">
        <button class="btn btn-flat btn-sm btn-dark" type="button" data-dismiss="modal">
            <i class="fa fa-times"></i> Close
        </button>
    </div>
</div>