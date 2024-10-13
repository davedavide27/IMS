<?php
require_once('../../config.php');
if (isset($_GET['id'])) {
    $qry = $conn->query("SELECT * FROM `products` WHERE id = '{$_GET['id']}'");
    if ($qry->num_rows > 0) {
        $res = $qry->fetch_array();
        foreach ($res as $k => $v) {
            if (!is_numeric($k)) $$k = $v;
        }
    }
}
?>
<style>
    #uni_modal .modal-footer {
        display: none !important;
    }

    /* Style for wrapping long descriptions */
    .description {
        word-wrap: break-word; /* Break long words */
        overflow-wrap: break-word; /* For better compatibility */
        max-height: 300px; /* Set a maximum height for the description */
        overflow: hidden; /* Hide overflow if the content is too long */
    }
</style>

<div class="container-fluid">
    <dl>
        <!-- Product Name -->
        <dt class="text-muted">Product Name</dt>
        <dd class='pl-4 fs-4 fw-bold'><?= isset($name) ? $name : '' ?></dd>

        <!-- Description -->
        <dt class="text-muted">Description</dt>
        <dd class='pl-4 description'>
            <p class=""><small><?= isset($description) ? $description : '' ?></small></p>
        </dd>

        <!-- Purchase Price -->
        <dt class="text-muted">Purchase Price</dt>
        <dd class='pl-4'>
            <p class=""><?= isset($purchase_price) ? number_format($purchase_price, 2) : 'N/A' ?></p>
        </dd>

        <!-- Selling Price -->
        <dt class="text-muted">Selling Price</dt>
        <dd class='pl-4'>
            <p class=""><?= isset($selling_price) ? number_format($selling_price, 2) : 'N/A' ?></p>
        </dd>

        <!-- Status -->
        <dt class="text-muted">Status</dt>
        <dd class='pl-4 fs-4 fw-bold'>
            <?php
            $delete_flag = isset($delete_flag) ? $delete_flag : 0; // Correct status field
            switch ($delete_flag) {
                case 1:
                    echo '<span class="badge badge-danger bg-gradient-danger px-3 rounded-pill">Inactive</span>';
                    break;
                case 0:
                    echo '<span class="badge badge-primary bg-gradient-primary px-3 rounded-pill">Active</span>';
                    break;
                default:
                    echo '<span class="badge badge-default border px-3 rounded-pill">N/A</span>';
                    break;
            }
            ?>
        </dd>

        <!-- Date Created -->
        <dt class="text-muted">Date Created</dt>
        <dd class='pl-4'>
            <p class=""><?= isset($date_created) ? date("M d, Y g:i A", strtotime($date_created)) : 'N/A' ?></p>
        </dd>

        <!-- Date Updated -->
        <dt class="text-muted">Date Updated</dt>
        <dd class='pl-4'>
            <p class=""><?= isset($date_updated) ? date("M d, Y g:i A", strtotime($date_updated)) : 'N/A' ?></p>
        </dd>
    </dl>

    <!-- Close Button -->
    <div class="col-12 text-right">
        <button class="btn btn-flat btn-sm btn-dark" type="button" data-dismiss="modal">
            <i class="fa fa-times"></i> Close
        </button>
    </div>
</div>
