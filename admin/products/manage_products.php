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

<div class="container-fluid">
    <form action="" id="product-form">
        <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
        <input type="hidden" name="delete_flag" id="delete_flag" value="<?php echo isset($delete_flag) ? $delete_flag : 0; ?>">

        <!-- Name -->
        <div class="form-group">
            <label for="name" class="control-label">Product Name</label>
            <input type="text" name="name" id="name" class="form-control form-control-border" placeholder="Enter Product Name" value="<?php echo isset($name) ? $name : '' ?>" required>
        </div>

        <!-- Description -->
        <div class="form-group">
            <label for="description" class="control-label">Description</label>
            <textarea rows="3" name="description" id="description" class="form-control form-control-sm rounded-0" required><?php echo isset($description) ? $description : '' ?></textarea>
        </div>

        <!-- Purchase Price -->
        <div class="form-group">
            <label for="purchase_price" class="control-label">Purchase Price</label>
            <input type="number" step="0.01" name="purchase_price" id="purchase_price" class="form-control form-control-border" placeholder="Enter Purchase Price" value="<?php echo isset($purchase_price) ? $purchase_price : '' ?>" required>
        </div>

        <!-- Selling Price -->
        <div class="form-group">
            <label for="selling_price" class="control-label">Selling Price</label>
            <input type="number" step="0.01" name="selling_price" id="selling_price" class="form-control form-control-border" placeholder="Enter Selling Price" value="<?php echo isset($selling_price) ? $selling_price : '' ?>" required>
        </div>

        <!-- Status Buttons -->
        <div class="form-group">
            <label for="status" class="control-label">Status</label>
            <br>
            <div class="btn-group">
                <button type="button" class="btn btn-sm <?= isset($delete_flag) && $delete_flag == 0 ? 'btn-primary' : 'btn-light'; ?>" id="active-btn">Active</button>
                <button type="button" class="btn btn-sm <?= isset($delete_flag) && $delete_flag == 1 ? 'btn-danger' : 'btn-light'; ?>" id="inactive-btn">Inactive</button>
            </div>
        </div>

        <button class="btn btn-default bg-gradient-navy btn-flat btn-sm" id="save_entry" type="button"><i class="fa fa-save"></i> Save Entry</button>
    </form>
</div>

<script>
    $(function() {
        // Highlight selected status button
        var deleteFlag = $('#delete_flag').val();

        $('#active-btn').click(function() {
            $('#delete_flag').val(0);
            $('#active-btn').removeClass('btn-light').addClass('btn-primary');
            $('#inactive-btn').removeClass('btn-danger').addClass('btn-light');
        });

        $('#inactive-btn').click(function() {
            $('#delete_flag').val(1);
            $('#inactive-btn').removeClass('btn-light').addClass('btn-danger');
            $('#active-btn').removeClass('btn-primary').addClass('btn-light');
        });

        // Save form
        $('#save_entry').click(function() {
            $('.pop-msg').remove();
            var _this = $('#product-form');
            var el = $('<div>');
            el.addClass("pop-msg alert").hide();
            start_loader();
            $.ajax({
                url: _base_url_ + "classes/Master.php?f=save_product",
                data: new FormData(_this[0]),
                cache: false,
                contentType: false,
                processData: false,
                method: 'POST',
                dataType: 'json',
                error: err => {
                    console.log(err);
                    alert_toast("An error occurred.", 'error');
                    end_loader();
                },
                success: function(resp) {
                    if (typeof resp === 'object' && resp.status === 'success') {
                        alert_toast(resp.msg, 'success');  // Display the success message from the response
                        setTimeout(function() {
                            location.reload();  // Reload the page after a short delay
                        }, 2000);  // Delay of 2 seconds before reloading the page
                    } else {
                        el.addClass("alert-danger").text(resp.msg || "An error occurred due to an unknown reason.");
                        _this.prepend(el);
                        el.show('slow');
                        $('html,body,.modal').animate({ scrollTop: 0 }, 'fast');
                    }
                    end_loader();
                }
            });
        });
    });
</script>
