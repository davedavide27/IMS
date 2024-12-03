<?php
// Include the database connection (using your DBConnection class)
require_once('./../../config.php');

// Fetch the company info from the system_info_inventory table
$query = "SELECT * FROM system_info_inventory WHERE meta_field IN ('name', 'contact', 'email', 'company', 'logo')"; // Include 'logo'
$result = $conn->query($query);

// Initialize variables
$company_name = '';
$company_address = '';
$city_zip = '';  // Note: Add logic to separate city, state, and zip if available in the meta_value
$phone = '';
$email = '';
$logo_path = '';

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
            case 'logo':
                $logo_path = htmlspecialchars($info['meta_value']);
                break;
        }
    }
} else {
    echo "Error fetching company info: " . $conn->error;
}

// Validate and create the full logo URL
$full_logo_url = (!empty($logo_path) && file_exists(base_app . $logo_path))
    ? base_url . $logo_path
    : base_url . 'no-image-available.png';
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order For Sales</title>
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
                <img src="<?php echo $full_logo_url; ?>"
                    alt="Company Logo"
                    class="brand-image"
                    style="
            border-radius: 50%; 
            background-color: transparent; 
            width: 6.5rem; 
            height: 6.5rem; 
            object-fit: cover; 
            object-position: center center;">
            </div>

            <div class="title">
                <h2>PURCHASE ORDER</h2>
            </div>
        </header>

        <section class="po-details">
            <!-- Add the "Purchase Order For Sales" text -->
            <p style="text-align: center; font-size: 18px; text-transform: uppercase;"><strong>Purchase Order For Sales</strong></p>
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
    //echo '<pre>';
   // print_r($_POST); // Inspect POST data
    //echo '</pre>';

    $subtotal = 0;

    if (isset($_POST['entries']) && is_array($_POST['entries'])) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<thead>";
        echo "<tr>";
        echo "<th>No.</th>";
        echo "<th>Product Name</th>";
        echo "<th>Quantity</th>";
        echo "<th>Price</th>";
        echo "<th>Total</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";

        $counter = 1;

        foreach ($_POST['entries'] as $row) {
            $product_name = isset($row['product']) ? htmlspecialchars($row['product']) : '';
            $quantity = isset($row['quantity']) ? (float)$row['quantity'] : 0;
            $price = isset($row['price']) ? (float)$row['price'] : 0;
            $total = $quantity * $price;

            $subtotal += $total;

            // Format the quantity with commas
            $formatted_quantity = number_format($quantity);

            echo "<tr>";
            echo "<td>" . $counter++ . "</td>";
            echo "<td>" . $product_name . "</td>";
            echo "<td>" . $formatted_quantity . "</td>"; // Display formatted quantity
            echo "<td>₱" . number_format($price, 2) . "</td>";
            echo "<td>₱" . number_format($total, 2) . "</td>";
            echo "</tr>";
        }

        echo "</tbody>";
        echo "</table>";

        $vat = $subtotal * 0.12;
        $grand_total = $subtotal + $vat;

        //echo "<h3>Subtotal: ₱" . number_format($subtotal, 2) . "</h3>";
        //echo "<h3>VAT (12%): ₱" . number_format($vat, 2) . "</h3>";
        //echo "<h3>Grand Total: ₱" . number_format($grand_total, 2) . "</h3>";
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
            <td>₱<?php echo number_format($subtotal, 2); ?></td>
        </tr>
        <tr>
            <td>Output VAT (Tax) 12%:</td>
            <td>₱<?php echo number_format($vat, 2); ?></td>
        </tr>
        <tr>
            <td>Total:</td>
            <td>₱<?php echo number_format($grand_total, 2); ?></td>
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