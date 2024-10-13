
<!--
<div class="container-fluid">
    <form action="" id="update-form">
        <input type="hidden" name="id" value="<?= isset($_GET['id']) ? $_GET['id'] : '' ?>">
            <div class="form-group">
                <small class="text-muted ">Status</small>
                <select name="status" id="status" class="form-control form-control-sm form-control-border" required>
                    <option value="0" <?= isset($status) && $status == 0 ? "selected" : "" ?>>Pending</option>
                    <option value="1" <?= isset($status) && $status == 1 ? "selected" : "" ?>>Confirmed</option>
                    <option value="2" <?= isset($status) && $status == 2 ? "selected" : "" ?>>Cancelled</option>
                </select>
            </div>
    </form>
</div>
<script>
    $(function(){
        $('#update-form').submit(function(e){
            e.preventDefault();
            var _this = $("#entry-form");
            $('.pop-msg').remove(); // Remove any existing messages
            start_loader();

            $.ajax({
                url: _base_url_ + "classes/Master.php?f=update_appointment_status",
                data: new FormData($(this)[0]),
                cache: false,
                contentType: false,
                processData: false,
                method: 'POST',
                dataType: 'json',
                error: function(err) {
                    console.log(err);
                    alert_toast("An error occurred.", 'error');
                    end_loader();
                },
                success: function(resp) {
                    if (resp.status == 'success') {
                        alert_toast("Appointment status updated successfully.", 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 2000); // Optional delay before reload
                    } else if (resp.msg) {
                        alert_toast(resp.msg, 'error'); // Show specific error message
                    } else {
                        alert_toast("An error occurred due to an unknown reason.", 'error');
                    }
                    end_loader();
                }
            });
        });
    });
</script>
-->