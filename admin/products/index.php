<div class="card card-outline card-primary rounded-0 shadow">
    <div class="card-header">
        <h3 class="card-title">List of Products</h3>
        <div class="card-tools" style="display: flex; justify-content: flex-end; gap: 10px;">
            <!-- Actions Dropdown -->
            <?php if ($user_type == '3'): // Only display for user type 3 (manager) ?>
                <div class="dropdown">
                    <button class="btn btn-primary btn-flat btn-sm dropdown-toggle" type="button" id="actionsDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-check-circle"></i> Actions
                    </button>
                    <div class="dropdown-menu" aria-labelledby="actionsDropdown">
                        <button class="dropdown-item approve_data" type="button" data-remarks="1">
                            <i class="fa fa-check" style="color: green;"></i> Approve
                        </button>
                        <button class="dropdown-item deny_data" type="button" data-remarks="0">
                            <i class="fa fa-times" style="color: red;"></i> Pending
                        </button>
                    </div>
                </div>
            <?php endif; ?>
            <a href="javascript:void(0)" id="create_new" class="btn btn-flat btn-sm btn-primary">
                <span class="fas fa-plus"></span> Add New
            </a>
        </div>
    </div>

    <div class="card-body">
        <div class="container-fluid">
            <table class="table table-bordered table-hover table-striped">
                <colgroup>
                    <col width="10%">
                    <col width="15%">
                    <col width="10%">
                    <col width="10%">
                    <col width="10%">
                    <col width="10%">
                    <col width="10%">
                    <col width="10%">
                    <col width="10%"> <!-- New column for delete_flag -->
                </colgroup>
                <thead>
                    <tr class="bg-gradient-primary text-light">
                        <th><input type="checkbox" id="selectAll"> Select All</th>
                        <th>Date Created</th>
                        <th>Product Name</th>
                        <th>Description</th>
                        <th>Purchase Price</th>
                        <th>Selling Price</th>
                        <th>Status</th> <!-- New header for Status -->
                        <th>Remarks</th> <!-- Display remarks -->
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        $qry = $conn->query("SELECT * FROM `products` WHERE delete_flag IN (0, 1) ORDER BY `name` ASC");
                        while ($row = $qry->fetch_assoc()):
                    ?>
                        <tr id="row-<?php echo $row['id']; ?>">
                            <td class="text-center">
                                <input type="checkbox" class="selectItem" data-id="<?php echo $row['id']; ?>" data-remarks="<?php echo $row['remarks']; ?>">
                            </td>
                            <td class="text-center"><?= date("M d, Y", strtotime($row['date_created'])); ?></td>
                            <td class="text-truncate"><?php echo $row['name']; ?></td>
                            <td class="text-truncate"><p class="m-0 truncate-1"><?php echo $row['description']; ?></p></td>
                            <td class="text-right"><?php echo number_format($row['purchase_price'], 2); ?></td>
                            <td class="text-right"><?php echo number_format($row['selling_price'], 2); ?></td>
                            <td class="text-center">
                                <?php 
                                    // Display status based on delete_flag
                                    echo $row['delete_flag'] == 0 
                                        ? '<span class="badge badge-primary bg-gradient-primary">Active</span>' 
                                        : '<span class="badge badge-danger bg-gradient-danger">Inactive</span>'; 
                                ?>
                            </td>
                            <td class="text-center">
                                <?php 
                                    // Display remarks (Approved or Pending)
                                    echo $row['remarks'] == 1 
                                        ? '<span class="badge badge-success">Approved</span>' 
                                        : '<span class="badge badge-warning">Pending</span>';
                                ?>
                            </td> <!-- Display remarks based on value -->
                            <td align="center">
                                <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                                    Action
                                    <span class="sr-only">Toggle Dropdown</span>
                                </button>
                                <div class="dropdown-menu" role="menu">
                                    <a class="dropdown-item view_data" href="javascript:void(0)" data-id="<?php echo $row['id']; ?>">
                                        <span class="fa fa-eye text-dark"></span> View
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item edit_data" href="javascript:void(0)" data-id="<?php echo $row['id']; ?>">
                                        <span class="fa fa-edit text-primary"></span> Edit
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="<?php echo $row['id']; ?>" data-name="<?php echo $row['name']; ?>">
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
    // Select/Deselect all items
    $('#selectAll').click(function() {
        var isChecked = $(this).prop('checked');
        $('.selectItem').prop('checked', isChecked);
    });

    // Handle actions for Pending or Approved remarks
    $('button.approve_data, button.deny_data').click(function() {
        var remarks = $(this).data('remarks'); // Get remarks (0 for Pending, 1 for Approved)
        var selectedItems = [];
        $('.selectItem:checked').each(function() {
            selectedItems.push($(this).data('id'));
        });

        if (selectedItems.length > 0) {
            // Send the selected product IDs and remarks to PHP
            $.ajax({
                url: _base_url_ + "classes/Master.php?f=update_product_remarks",
                method: 'POST',
                data: JSON.stringify({ product_ids: selectedItems, remarks: remarks }),
                contentType: 'application/json',
                success: function(response) {
                    if (response.status === 'success') {
                        alert_toast(response.msg, 'success'); // Success message with toast
                        setTimeout(function() {
                            location.reload(); // Reload page after update
                        }, 2000); // Delay to allow toast to disappear before reload
                    } else if (response.status === 'warning') {
                        alert_toast(response.msg, 'warning'); // Warning message with toast
                    } else {
                        alert_toast(response.msg, 'danger'); // Error message with toast
                    }
                },
                error: function(xhr, status, error) {
                    // Handle request failure
                    alert_toast("An error occurred while processing your request. Please try again.", 'danger');
                }
            });
        } else {
            alert_toast("Please select at least one product.", 'warning'); // Warning message with toast
        }
    });
});
</script>






<style>
    .text-truncate {
        overflow: hidden;        /* Prevent overflow */
        white-space: nowrap;     /* Prevent wrapping to the next line */
        text-overflow: ellipsis; /* Add ellipsis (…) at the end */
    }

    .truncate-1 {
        max-width: 150px; /* Adjust this value based on your layout */
    }

    .table td, .table th {
        vertical-align: middle; /* Ensure vertical alignment */
    }
</style>

<script>
    $(document).ready(function(){
        $('#create_new').click(function(){
            uni_modal("Add New Product", "products/manage_products.php");
        });
        
        $('.edit_data').click(function(){
            uni_modal("Update Product Details", "products/edit_products.php?id=" + $(this).attr('data-id'));
        });
        
        $('.delete_data').click(function(){
            _conf("Are you sure to delete '<b>" + $(this).attr('data-name') + "</b>' from Product List permanently?", "delete_product", [$(this).attr('data-id')]);
        });
        
        $('.view_data').click(function(){
            uni_modal("Product Details", "products/view_products.php?id=" + $(this).attr('data-id'));
        });
        
        $('.table td, .table th').addClass('py-1 px-2 align-middle');
        
        $('.table').dataTable({
            columnDefs: [
                { orderable: false, targets: 7 } // Updated target to include the Status column
            ],
        });
    });

    function delete_product($id){
        start_loader();
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=delete_product",
            method: "POST",
            data: { id: $id },
            dataType: "json",
            error: err => {
                console.log(err);
                alert_toast("An error occurred while trying to delete the product.", 'error');
                end_loader();
            },
            success: function(resp) {
                if (typeof resp === 'object' && resp.status === 'success') {
                    alert_toast(resp.msg, 'success');  // Display the success message from the response
                    // Set a delay for page reload
                    setTimeout(function() {
                        location.reload();  // Reload the page after a short delay
                    }, 2000);  // Delay of 2 seconds before reloading the page
                } else {
                    alert_toast(resp.msg || "An error occurred due to an unknown reason.", 'error');  // Display error message if available
                    end_loader();
                }
            }
        });
    }
</script>


