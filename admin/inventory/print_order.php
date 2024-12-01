<?php
// Include the database connection (using your DBConnection class)
require_once('./../../config.php');

// Create an instance of the DBConnection class
$db = new DBConnection;
$conn = $db->conn; // MySQLi connection object

// Fetch the company info from the system_info_inventory table
$query = "SELECT * FROM system_info_inventory WHERE meta_field IN ('name', 'contact', 'email', 'company')"; // Modify query as needed
$result = $conn->query($query);

// Initialize variables
$company_name = '';
$company_address = '';
$city_zip = '';  // Note: Add logic to separate city, state, and zip if available in the meta_value
$phone = '';
$email = '';

// Check if the query was successful
if ($result) {
    // Loop through the result and populate variables
    while ($info = $result->fetch_assoc()) {
        switch ($info['meta_field']) {
            case 'name':
                $company_name = htmlspecialchars($info['meta_value']);
                break;
            case 'contact':
                $phone = htmlspecialchars($info['meta_value']);
                break;
            case 'email':
                $email = htmlspecialchars($info['meta_value']);
                break;
            case 'company':
                $company_address = htmlspecialchars($info['meta_value']);
                break;
        }
    }
} else {
    echo "Error fetching company info: " . $conn->error;
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order For Purchase Entries</title>
    <link rel="stylesheet" href="print_order.css">
    <style>
        .po-details .underline {
            border-bottom: 2px solid black;
            display: inline-block;
            width: 180px;
            /* Adjust this to your desired length */
            text-align: center;
            font-weight: bold;
            /* Makes the PO number bold */
            font-size: 14px;
            /* Increases the font size */
        }
    </style>
</head>

<body>
<div class="purchase-order">
    <header>
        <div class="logo">
            <h1><?php echo $company_address; ?></h1>
        </div>
        <div class="title">
            <h2>PURCHASE ORDER</h2>
        </div>
    </header>

    <section class="po-details">
        <!-- Add the "Purchase Order For Purchase Entries" text -->
        <p style="text-align: center; font-size: 18px; text-transform: uppercase;"><strong>Purchase Order For Purchase Entries</strong></p>
        <br>
        <!-- Display the PO number dynamically with an extended underline -->
        <p id="poNumberDisplay" style="text-align: left;">PO NO:
            <span class="underline">
                <?php
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    // Get the filtered PO number and display it
                    $po_number = isset($_POST['po_number']) ? strtoupper($_POST['po_number']) : '';
                    echo htmlspecialchars($po_number);
                } else {
                    echo ''; // Default text when PO number is not set
                }
                ?>
            </span>
        </p>
        <p style="text-align: left;">PO Date: <u>______________________</u></p>
    </section>

        <section class="address-section">
            <div class="to">
                <h3>To:</h3>
                <p>Company Name: ___________________</p>
                <p>Company Address: ___________________</p>
                <p>City, ST, ZIP Code: ___________________</p>
                <p>Attn: ___________________</p>
                <p>Phone: ________________</p>
                <p>Fax: __________________</p>
                <p>Email: ________________</p>
            </div>
        </section>
        <!--
        <section class="shipment-details">
            <table>
                <tr>
                    <th>FOB</th>
                    <th>Shipped Via</th>
                    <th>Payment Term</th>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            </table>
        </section>

        <section class="item-details">
            <table>
                <thead>
                    <tr>
                        <th>Item No</th>
                        <th>Description</th>
                        <th>Qty</th>
                        <th>Total</th>
                    </tr>
                </thead>
            -->
        <tbody>
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Get the filtered PO number and PO date
                $po_number = isset($_POST['po_number']) ? strtoupper($_POST['po_number']) : '';

                // Check if entries exist
                if (isset($_POST['entries']) && is_array($_POST['entries'])) {
                    echo "<table border='1' cellpadding='5' cellspacing='0'>";
                    echo "<thead>";
                    echo "<tr>";
                    echo "<th>No.</th>"; // Added No. column
                    echo "<th>Product Name</th>";
                    echo "<th>Description</th>";
                    echo "<th>Quantity</th>";
                    echo "<th>Total Price</th>";
                    echo "</tr>";
                    echo "</thead>";
                    echo "<tbody>";

                    // Initialize counter
                    $counter = 1;

                    foreach ($_POST['entries'] as $row) {
                        // Extract relevant data
                        $product_name = $row[2] ?? ''; // Product name is in index 2
                        $details = isset($row[3]) ? explode("\n", $row[3]) : [];
                        $description = $details[0] ?? ''; // First line is the description
                        $quantity = isset($details[1]) ? (int)trim($details[1]) : 0; // Second line is quantity
                        $total_price = isset($details[2]) ? trim($details[2]) : '₱0.00'; // Third line is total price

                        // Display the data with the counter
                        echo "<tr>";
                        echo "<td>" . $counter++ . "</td>"; // Display and increment the counter
                        echo "<td>" . htmlspecialchars($product_name) . "</td>";
                        echo "<td>" . htmlspecialchars($description) . "</td>";
                        echo "<td>" . htmlspecialchars($quantity) . "</td>";
                        echo "<td>" . htmlspecialchars($total_price) . "</td>";
                        echo "</tr>";
                    }

                    echo "</tbody>";
                    echo "</table>";
                } else {
                    echo "<h4>No data found for the given entries.</h4>";
                }
            } else {
                echo "<h4>Invalid request method.</h4>";
            }
            ?>

        </tbody>
        </table>
        </section>

        <section class="remarks">
            <p>Remarks:</p>
            <textarea rows="3"></textarea>
        </section>

        <section class="totals">
            <table>
                <tr>
                    <td>Subtotal:</td>
                    <td>__________________</td>
                </tr>
                <tr>
                    <td>Output vat (tax) 12%:</td>
                    <td>__________________</td>
                </tr>
                <tr>
                    <!--
                    <td>Freight:</td>
                    <td>__________________</td>
                </tr>
                -->
                <tr>
                    <td>Total:</td>
                    <td>__________________</td>
                </tr>
            </table>
        </section>

        <footer>
            <p><?php echo $company_address; ?></p>
            <p><?php echo $phone; ?> | <?php echo $email; ?></p>

        </footer>

        <div class="print-button">
            <button onclick="window.print()">Print</button>
        </div>
    </div>
</body>

</html>