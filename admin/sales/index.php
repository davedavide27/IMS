<?php
function format_num($number)
{
    $decimals = 0;
    $num_ex = explode('.', $number);
    $decimals = isset($num_ex[1]) ? strlen($num_ex[1]) : 0;
    return number_format($number, $decimals);
}
?>
<style>
    th.p-0,
    td.p-0 {
        padding: 0 !important;
    }
</style>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">Sales Entries</h3>
        <div class="card-tools">
            <button class="btn btn-primary btn-flat btn-sm" id="create_new" type="button"><i class="fa fa-pen-square"></i> Add New Sales Entry</button>
        </div>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <table class="table table-hover table-striped table-bordered" id="salesTable">
                <colgroup>
                    <col width="15%"> <!-- Purchase Date -->
                    <col width="20%"> <!-- Product -->
                    <col width="10%"> <!-- Quantity -->
                    <col width="10%"> <!-- Price -->
                    <col width="10%"> <!-- Total -->
                    <col width="15%"> <!-- Recorded By -->
                    <col width="10%"> <!-- Action -->
                </colgroup>
                <thead>
                    <tr>
                        <th>Purchase Date</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total</th>
                        <th>Recorded By</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $swhere = "";
                    if ($_settings->userdata('type') != 1) {
                        $swhere = " WHERE user_id = '{$_settings->userdata('id')}' ";
                    }
                    $users = $conn->query("SELECT id, username FROM `users` WHERE id IN (SELECT `user_id` FROM `sales` {$swhere})");
                    $user_arr = array_column($users->fetch_all(MYSQLI_ASSOC), 'username', 'id');

                    // Fetch sales entries
                    $sales = $conn->query("SELECT s.id, s.purchase_date, s.product_id, s.quantity, s.selling_price, s.user_id FROM `sales` s {$swhere} ORDER BY date(s.purchase_date) ASC");

                    while ($row = $sales->fetch_assoc()):
                        // Fetch product details
                        $product = $conn->query("SELECT name FROM products WHERE id = '{$row['product_id']}'")->fetch_assoc();
                        $total_price = $row['quantity'] * $row['selling_price']; // Use selling_price instead of price
                    ?>
                        <tr>
                            <td class="text-center"><?= date("M d, Y", strtotime($row['purchase_date'])) ?></td>
                            <td class=""><?= htmlspecialchars($product['name'], ENT_QUOTES) ?></td>
                            <td class="text-right"><?= format_num($row['quantity']) ?></td>
                            <td class="text-right"><?= format_num($row['selling_price']) ?></td> <!-- Changed from price to selling_price -->
                            <td class="text-right"><?= format_num($total_price) ?></td>
                            <td><?= isset($user_arr[$row['user_id']]) ? $user_arr[$row['user_id']] : "N/A" ?></td>
                            <td class="text-center">
                                <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                                    Action
                                    <span class="sr-only">Toggle Dropdown</span>
                                </button>
                                <div class="dropdown-menu" role="menu">
                                    <!--
                                    <a class="dropdown-item view_data" href="javascript:void(0)" data-id="</*?php echo $row['id'] ?>">
                                        <span class="fa fa-eye text-dark"></span> View
                                    </a>
                                    
                                    <div class="dropdown-divider"></div>
                                    -->
                                    <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">
                                        <span class="fa fa-trash text-danger"></span> Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {
        // Open modal to create a new sales entry
        $('#create_new').click(function() {
            uni_modal("Add New Sales Entry", "sales/manage_sales.php", 'mid-large');
        });

        // Edit existing sales entry
        $(document).on('click', '.edit_data', function() {
            var id = $(this).data('id');
            uni_modal("Edit", "sales/edit_sales.php?id=" + id, 'mid-large');
        });

        // Confirm and delete the sales entry
        $(document).on('click', '.delete_data', function() {
            var id = $(this).data('id');
            _conf("Are you sure to delete this entry permanently?", "delete_sale", [id]);
        });

        // Initialize DataTable with specific settings
        $('#salesTable').dataTable({
            columnDefs: [{
                orderable: false,
                targets: [5, 6] // Modify as needed based on the table columns
            }]
        });
    });

    // Function to delete sales entry
    function delete_sale(id) {
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=delete_sale", // API for deleting sale
            method: "POST",
            data: {
                id: id
            }, // Pass the sale ID as data
            dataType: "json",
            success: function(resp) {
                if (resp.status === 'success') {
                    if (resp.toast) {
                        eval(resp.toast); // Execute the toast message
                    }
                    // Use session storage to temporarily hold the message
                    sessionStorage.setItem('delete_message', resp.msg); 
                    // Delay for 2 seconds before reloading the page
                    setTimeout(function() {
                        location.reload(); // Reload the page to reflect changes
                    },); // Adjust the time (in milliseconds) as needed
                } else {
                    alert_toast(resp.msg || "An error occurred while deleting the entry.", 'error');
                }
            },
            error: function(err) {
                console.error(err); // Log error in console for debugging
                alert_toast("An error occurred while deleting the entry.", 'error');
            }
        });
    }

    // Display message from session storage after the page loads
    $(window).on('load', function() {
        var deleteMessage = sessionStorage.getItem('delete_message');
        if (deleteMessage) {
            alert_toast(deleteMessage, 'success'); // Show success message
            sessionStorage.removeItem('delete_message'); // Clear the message after displaying it
        }
    });
</script>
