<div class="card card-outline card-primary rounded-0 shadow">
    <div class="card-header">
        <h3 class="card-title">List of Products</h3>
        <div class="card-tools">
            <a href="javascript:void(0)" id="create_new" class="btn btn-flat btn-sm btn-primary">
                <span class="fas fa-plus"></span> Add New
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <table class="table table-bordered table-hover table-striped">
                <colgroup>
                    <col width="5%">
                    <col width="20%">
                    <col width="20%">
                    <col width="15%">
                    <col width="15%">
                    <col width="15%">
                    <col width="15%"> <!-- Added width for the Status column -->
                </colgroup>
                <thead>
                    <tr class="bg-gradient-primary text-light">
                        <th>#</th>
                        <th>Date Created</th>
                        <th>Product Name</th>
                        <th>Description</th>
                        <th>Purchase Price</th>
                        <th>Selling Price</th>
                        <th>Status</th> <!-- Added Status column -->
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        $i = 1;
                        $qry = $conn->query("SELECT * FROM `products` WHERE delete_flag = 0 OR delete_flag = 1 ORDER BY `name` ASC");
                        while($row = $qry->fetch_assoc()):
                    ?>
                        <tr>
                            <td class="text-center"><?php echo $i++; ?></td>
                            <td class="text-center"><?= date("M d, Y", strtotime($row['date_created'])) ?></td>
                            <td class="text-truncate"><?php echo $row['name'] ?></td>
                            <td class="text-truncate"><p class="m-0 truncate-1"><?php echo $row['description'] ?></p></td>
                            <td class="text-right"><?php echo number_format($row['purchase_price'], 2) ?></td>
                            <td class="text-right"><?php echo number_format($row['selling_price'], 2) ?></td>
                            <td class="text-center">
                                <?php 
                                    // Display status based on delete_flag
                                    echo $row['delete_flag'] == 0 
                                        ? '<span class="badge badge-primary bg-gradient-primary">Active</span>' 
                                        : '<span class="badge badge-danger bg-gradient-danger">Inactive</span>'; 
                                ?>
                            </td>
                            <td align="center">
                                <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                                    Action
                                    <span class="sr-only">Toggle Dropdown</span>
                                </button>
                                <div class="dropdown-menu" role="menu">
                                    <a class="dropdown-item view_data" href="javascript:void(0)" data-id ="<?php echo $row['id'] ?>">
                                        <span class="fa fa-eye text-dark"></span> View
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item edit_data" href="javascript:void(0)" data-id ="<?php echo $row['id'] ?>">
                                        <span class="fa fa-edit text-primary"></span> Edit
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>" data-name="<?php echo $row['name'] ?>">
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
