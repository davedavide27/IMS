<style>
    .img-thumb-path {
        width: 100px;
        height: 80px;
        object-fit: scale-down;
        object-position: center center;
    }
</style>

<div class="card card-outline card-primary rounded-0 shadow">
    <div class="card-header">
        <h3 class="card-title">List of Stocks</h3>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <table class="table table-bordered table-hover table-striped">
                <colgroup>
                    <col width="5%">
                    <col width="25%">
                    <col width="20%">
                    <col width="15%"> <!-- Adjusted column widths -->
                </colgroup>
                <thead>
                    <tr class="bg-gradient-primary text-light">
                        <th>#</th>
                        <th>Date Created</th>
                        <th>Product</th>
                        <th>Available Stocks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        $i = 1;
                        // Updated SQL query to join stocks with products
                        $qry = $conn->query("
                            SELECT s.id, s.date_created, p.name, s.available_stocks 
                            FROM stocks s 
                            JOIN products p ON s.product_id = p.id 
                            WHERE s.available_stocks > 0
                            ORDER BY p.name ASC
                        ");
                        while ($row = $qry->fetch_assoc()):
                    ?>
                        <tr>
                            <td class="text-center"><?php echo $i++; ?></td>
                            <td><?php echo date("M d, Y", strtotime($row['date_created'])) ?></td>
                            <td><?php echo $row['name'] ?></td>
                            <td class="text-center"><?php echo $row['available_stocks'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    $(document).ready(function(){
        $('.table td, .table th').addClass('py-1 px-2 align-middle');
        $('.table').dataTable({
            columnDefs: [
                { orderable: false, targets: 3 } // Updated target for the action column
            ],
        });
    });
</script>
