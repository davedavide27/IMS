<?php
require_once('../config.php');

class Master extends DBConnection
{
	private $settings;

	public function __construct()
	{
		global $_settings;
		$this->settings = $_settings;
		parent::__construct();
	}

	public function __destruct()
	{
		parent::__destruct();
	}

	function capture_err()
	{
		if (!$this->conn->error)
			return false;
		else {
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
			return json_encode($resp);
			exit;
		}
	}
	public function save_inventory_entry()
	{
		// Check if session is already started
		if (session_status() === PHP_SESSION_NONE) {
			session_start();
		}

		$resp = ['status' => 'failed', 'msg' => 'An unexpected error occurred.'];

		// Get input data
		$product_id = isset($_POST['product_id']) ? $this->conn->real_escape_string($_POST['product_id']) : null;
		$quantity = isset($_POST['quantity']) ? (float)$this->conn->real_escape_string($_POST['quantity']) : null;
		$entry_code = isset($_POST['entry_code']) ? $this->conn->real_escape_string($_POST['entry_code']) : null;
		$entry_date = isset($_POST['entry_date']) ? $this->conn->real_escape_string($_POST['entry_date']) : null;
		$description = isset($_POST['description']) ? $this->conn->real_escape_string($_POST['description']) : null;
		$gl_code = isset($_POST['gl_code']) ? $this->conn->real_escape_string($_POST['gl_code']) : null;
		$remarks = isset($_POST['remarks']) ? $this->conn->real_escape_string($_POST['remarks']) : null;

		if (empty($product_id) || is_null($quantity) || empty($entry_date) || empty($entry_code)) {
			$resp['msg'] = "All fields are required.";
			return json_encode($resp);
		}

		if (!isset($_SESSION['userdata']['id'])) {
			$resp['msg'] = "User is not logged in.";
			return json_encode($resp);
		}

		$user_id = $this->conn->real_escape_string($_SESSION['userdata']['id']);

		// Check stock availability
		$stockCheckSql = "SELECT * FROM `stocks` WHERE `product_id` = '$product_id'";
		$stockCheckResult = $this->conn->query($stockCheckSql);

		$stockExists = false;
		$newQuantity = $quantity;

		if ($stockCheckResult->num_rows > 0) {
			$stockRow = $stockCheckResult->fetch_assoc();
			$stockExists = true;
			$newQuantity += $stockRow['available_stocks'];
		}

		// Prepare data for inventory entry
		$data = [
			'entry_code' => $entry_code,
			'entry_date' => $entry_date,
			'description' => $description,
			'quantity' => $quantity,
			'user_id' => $user_id,
			'product_id' => $product_id,
			'remarks' => $remarks,
			'gl_code' => $gl_code
		];

		$fields = implode(", ", array_keys($data));
		$values = implode(", ", array_map(function ($value) {
			return is_null($value) ? 'NULL' : "'$value'";
		}, array_values($data)));

		// Insert into inventory_entries
		$sql = "INSERT INTO `inventory_entries` ($fields) VALUES ($values)";

		if ($this->conn->query($sql)) {
			// Get the last inserted ID from inventory_entries
			$inventory_entry_id = $this->conn->insert_id;

			// Update or insert into stocks
			if ($stockExists) {
				$updateStockSql = "UPDATE `stocks` SET `available_stocks` = '$newQuantity', `date_updated` = CURRENT_TIMESTAMP WHERE `product_id` = '$product_id'";
				$this->conn->query($updateStockSql);
			} else {
				$insertStockSql = "INSERT INTO `stocks` (`product_id`, `available_stocks`, `date_created`) VALUES ('$product_id', '$quantity', CURRENT_TIMESTAMP)";
				$this->conn->query($insertStockSql);
			}

			// Insert into stock_reports with inventory_entry_id
			$reportSql = "INSERT INTO `stock_reports` (`id`, `report_datetime`, `product_id`, `stock_entries`, `available_stocks`)
						  VALUES ('$inventory_entry_id', CURRENT_TIMESTAMP, '$product_id', '$quantity', '$newQuantity')";
			$this->conn->query($reportSql);

			$resp['status'] = 'success';
			$resp['msg'] = "New entry has been successfully added, and stocks updated.";
			$this->settings->set_flashdata('success', $resp['msg']);
		} else {
			$resp['msg'] = "An error occurred during the operation.";
			$resp['err'] = "Error: " . $this->conn->error;
		}

		return json_encode($resp);
	}




	public function check_product_exist($product_id)
	{
		// Sanitize the input to prevent SQL injection
		$product_id = $this->conn->real_escape_string($product_id);

		// SQL query to check if the product exists in the stocks table
		$sql = "SELECT * FROM `stocks` WHERE `product_id` = '$product_id'";
		$result = $this->conn->query($sql);

		// Check if the query was successful and return the result
		if ($result) {
			// Return true if the product exists, false otherwise
			return $result->num_rows > 0;
		} else {
			// If there's an error with the query, you can log it or handle it accordingly
			error_log("Error in check_product_exist: " . $this->conn->error);
			return false; // Assuming the product does not exist if the query fails
		}
	}









	public function delete_inventory_entry()
	{
		// Check if session is already started
		if (session_status() === PHP_SESSION_NONE) {
			session_start(); // Start session only if it's not already active
		}

		// Initialize response
		$resp = ['status' => 'failed', 'msg' => 'An unexpected error occurred.'];

		// Get the inventory entry code from the POST request
		$entry_code = isset($_POST['entry_code']) ? $_POST['entry_code'] : null;

		// Ensure the entry code is provided
		if (is_null($entry_code)) {
			$resp['msg'] = "Invalid entry code.";
			return json_encode($resp);
		}

		// Prepare SQL query to fetch product_id and quantity before deletion
		$entry_code = $this->conn->real_escape_string($entry_code); // Prevent SQL injection
		$fetch_sql = "SELECT `id`, `product_id`, `quantity` FROM `inventory_entries` WHERE `entry_code` = '$entry_code'";
		$fetch_result = $this->conn->query($fetch_sql);

		if ($fetch_result->num_rows > 0) {
			$entry_data = $fetch_result->fetch_assoc();
			$inventory_entry_id = $entry_data['id'];
			$product_id = $entry_data['product_id'];
			$quantity = $entry_data['quantity'];

			// Prepare SQL query to delete the entry
			$sql = "DELETE FROM `inventory_entries` WHERE `entry_code` = '$entry_code'";

			// Execute the query
			if ($this->conn->query($sql)) {
				if ($this->conn->affected_rows > 0) {
					// Update the stocks table by deducting the deleted quantity
					$update_stock_sql = "UPDATE `stocks` SET `available_stocks` = `available_stocks` - $quantity WHERE `product_id` = '$product_id'";
					if ($this->conn->query($update_stock_sql)) {
						// Set the status in stock_reports to '0' (Deleted)
						$update_report_status_sql = "UPDATE `stock_reports` SET `status` = 0 WHERE `id` = '$inventory_entry_id'";
						$this->conn->query($update_report_status_sql);

						$resp['status'] = 'success';
						$resp['msg'] = "Inventory entry with entry code '{$entry_code}' has been successfully deleted, stocks updated, and report status set to 'Deleted'.";
					} else {
						$resp['msg'] = "Entry deleted, but an error occurred while updating the stocks.";
						$resp['err'] = "Error: " . $this->conn->error;
					}
				} else {
					$resp['msg'] = "No inventory entry found with entry code '{$entry_code}'.";
				}
			} else {
				$resp['msg'] = "An error occurred during the operation.";
				$resp['err'] = "Error: " . $this->conn->error; // Log the SQL error for debugging
			}
		} else {
			$resp['msg'] = "No inventory entry found for the provided entry code.";
		}

		return json_encode($resp);
	}



	public function get_inventory_entry($entry_code): array
	{
		// Initialize response
		$resp = ['status' => 'failed', 'message' => 'An unexpected error occurred.', 'data' => null];

		// Check if entry_code is provided
		if (empty($entry_code)) {
			$resp['message'] = "Entry code is required.";
			return $resp;
		}

		// Sanitize entry code
		$entry_code = $this->conn->real_escape_string($entry_code);

		// Updated SQL query to fetch product details, including product name
		$sql = "
    SELECT ie.*, ii.product_id, ii.quantity, ii.price, pi.name AS product_name 
    FROM `inventory_entries` ie
    LEFT JOIN `inventory_items` ii ON ie.id = ii.inventory_id 
    LEFT JOIN `products` pi ON ii.product_id = pi.id 
    WHERE ie.`entry_code` = '$entry_code'
";

		$result = $this->conn->query($sql);

		if ($result && $result->num_rows > 0) {
			$resp['status'] = 'success';
			$resp['message'] = "Inventory entry retrieved successfully.";
			$resp['data'] = $result->fetch_assoc(); // Fetch entry details, including product info
		} else {
			$resp['message'] = "Inventory entry not found.";
		}

		return $resp;
	}

	public function edit_entry()
	{
		// Check if session is already started
		if (session_status() === PHP_SESSION_NONE) {
			session_start(); // Start session only if it's not already active
		}

		// Initialize response
		$resp = ['status' => 'failed', 'msg' => 'An unexpected error occurred.'];

		// Get input data
		$entry_date = isset($_POST['entry_date']) ? $this->conn->real_escape_string($_POST['entry_date']) : null;
		$description = isset($_POST['description']) ? $this->conn->real_escape_string($_POST['description']) : null;
		$product_id = isset($_POST['product_id']) ? $this->conn->real_escape_string($_POST['product_id']) : null;
		$quantity = isset($_POST['quantity']) ? (float)$this->conn->real_escape_string($_POST['quantity']) : null;
		$gl_code = isset($_POST['gl_code']) ? $this->conn->real_escape_string($_POST['gl_code']) : null;
		$remarks = isset($_POST['remarks']) ? $this->conn->real_escape_string($_POST['remarks']) : null;

		// Ensure all required fields are provided
		if (empty($entry_date) || empty($description) || empty($product_id) || is_null($quantity)) {
			$resp['msg'] = "All fields are required.";
			return json_encode($resp);
		}

		// Use the entry code from the POST request (it will be passed from the frontend)
		$entry_code = isset($_POST['entry_code']) ? $this->conn->real_escape_string($_POST['entry_code']) : null;

		// Fetch the original quantity of the entry before updating
		$qry = $this->conn->query("SELECT quantity FROM `inventory_entries` WHERE `entry_code` = '{$entry_code}' LIMIT 1");
		if ($qry->num_rows > 0) {
			$old_entry = $qry->fetch_assoc();
			$old_quantity = (float)$old_entry['quantity'];
		} else {
			$resp['msg'] = "Entry not found.";
			return json_encode($resp);
		}

		// Calculate stock difference
		$stock_difference = $quantity - $old_quantity;

		// Update the available stock in the `stocks` table
		// Assuming `stocks` table contains `available_stocks` and is linked by `product_id`
		$update_stock_stmt = $this->conn->prepare("UPDATE `stocks` SET available_stocks = available_stocks + ? WHERE `product_id` = ?");
		if (!$update_stock_stmt) {
			$resp['msg'] = "Stock update prepare statement failed: " . $this->conn->error;
			return json_encode($resp);
		}

		// Bind parameters to the stock update statement
		$update_stock_stmt->bind_param("di", $stock_difference, $product_id); // d for double (quantity), i for int (product_id)

		// Execute stock update
		$stock_update_success = $update_stock_stmt->execute();
		if (!$stock_update_success) {
			$resp['msg'] = "Stock update execution failed: " . $update_stock_stmt->error;
			return json_encode($resp);
		}

		// Prepare SQL to update `inventory_entries` table
		$update_entry_stmt = $this->conn->prepare("UPDATE `inventory_entries` SET 
			`entry_date` = ?, 
			`description` = ?, 
			`remarks` = ?,
			`product_id` = ?, 
			`quantity` = ?, 
			`gl_code` = ?,
			`date_updated` = CURRENT_TIMESTAMP 
			WHERE `entry_code` = ?");

		if (!$update_entry_stmt) {
			$resp['msg'] = "Database prepare statement failed: " . $this->conn->error;
			return json_encode($resp);
		}

		// Bind parameters to the prepared statement
		$update_entry_stmt->bind_param("sssissi", $entry_date, $description, $remarks, $product_id, $quantity, $gl_code, $entry_code);

		// Execute the update for `inventory_entries`
		$update_entry_success = $update_entry_stmt->execute();
		if (!$update_entry_success) {
			$resp['msg'] = "Update execution failed: " . $update_entry_stmt->error;
			return json_encode($resp);
		}

		// Success response with a success message
		$resp['status'] = 'success';
		$resp['msg'] = 'Entry updated successfully and stock adjusted.';
		return json_encode($resp);
	}



	public function save_product()
	{

		global $conn;

		// Sanitize and validate input data
		$name = $conn->real_escape_string(trim($_POST['name']));
		$description = $conn->real_escape_string(trim($_POST['description']));
		$purchase_price = $conn->real_escape_string(trim($_POST['purchase_price']));
		$selling_price = $conn->real_escape_string(trim($_POST['selling_price']));
		$delete_flag = isset($_POST['delete_flag']) ? (int)$_POST['delete_flag'] : 0;

		// Check for required fields
		if (empty($name) || empty($description) || empty($purchase_price) || empty($selling_price)) {
			$_SESSION['message'] = "All fields are required.";
			$_SESSION['status'] = 'error';
			echo json_encode(["status" => "error", "msg" => $_SESSION['message']]);
			exit;
		}

		// Insert the new product into the database
		$sql = "INSERT INTO `products` (`name`, `description`, `purchase_price`, `selling_price`, `delete_flag`) VALUES ('$name', '$description', '$purchase_price', '$selling_price', '$delete_flag')";
		if ($conn->query($sql) === TRUE) {
			// Success response with a success message
			$resp['status'] = 'success';
			$resp['msg'] = 'Product Inserted successfully.';
			return json_encode($resp);
		} else {
			$_SESSION['message'] = "Error: " . $conn->error;
			$_SESSION['status'] = 'error';
			echo json_encode(["status" => "error", "msg" => $_SESSION['message']]);
		}

		exit; // End the script
	}

	public function edit_product()
	{
		global $conn;

		// Sanitize and retrieve input data
		$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
		$name = isset($_POST['name']) ? trim($_POST['name']) : '';
		$description = isset($_POST['description']) ? trim($_POST['description']) : '';
		$purchase_price = isset($_POST['purchase_price']) ? floatval($_POST['purchase_price']) : 0;
		$selling_price = isset($_POST['selling_price']) ? floatval($_POST['selling_price']) : 0;
		$delete_flag = isset($_POST['delete_flag']) ? intval($_POST['delete_flag']) : 0;

		// Validate inputs
		if (empty($name)) {
			return json_encode(['status' => 'error', 'msg' => 'Product name is required.']);
		}

		if ($purchase_price < 0) {
			return json_encode(['status' => 'error', 'msg' => 'Purchase price must be a positive number.']);
		}

		if ($selling_price < 0) {
			return json_encode(['status' => 'error', 'msg' => 'Selling price must be a positive number.']);
		}

		// Check if the product exists
		if ($id > 0) {
			// Prepare the update query
			$qry = $conn->prepare("UPDATE `products` SET 
									`name` = ?, 
									`description` = ?, 
									`purchase_price` = ?, 
									`selling_price` = ?, 
									`delete_flag` = ?, 
									`date_updated` = NOW() 
									WHERE `id` = ?");
			$qry->bind_param("ssddii", $name, $description, $purchase_price, $selling_price, $delete_flag, $id);

			// Execute and check if the query was successful
			if ($qry->execute()) {
				return json_encode(['status' => 'success', 'msg' => 'Product updated successfully.']);
			} else {
				return json_encode(['status' => 'error', 'msg' => 'An error occurred while updating the product.']);
			}
		} else {
			return json_encode(['status' => 'error', 'msg' => 'Invalid product ID.']);
		}
	}



	public function delete_product()
	{
		// Check if session is already started
		if (session_status() === PHP_SESSION_NONE) {
			session_start(); // Start session only if it's not already active
		}

		// Initialize response
		$resp = ['status' => 'failed', 'msg' => 'An unexpected error occurred.'];

		// Get the product ID from the POST request
		$product_id = isset($_POST['id']) ? $_POST['id'] : null;

		// Ensure the product ID is provided
		if (is_null($product_id)) {
			$resp['msg'] = "Invalid product ID.";
			return json_encode($resp);
		}

		// Prepare SQL query to delete the product
		$product_id = $this->conn->real_escape_string($product_id); // Prevent SQL injection
		$sql = "DELETE FROM `products` WHERE `id` = '$product_id'";

		// Execute the query
		if ($this->conn->query($sql)) {
			if ($this->conn->affected_rows > 0) { // Check if a row was deleted
				$resp['status'] = 'success';
				$resp['msg'] = "Product with ID '{$product_id}' has been successfully deleted.";
			} else {
				$resp['msg'] = "No product found with ID '{$product_id}'.";
			}
		} else {
			$resp['msg'] = "An error occurred during the operation.";
			$resp['err'] = "Error: " . $this->conn->error; // Log the SQL error for debugging
		}

		return json_encode($resp);
	}


	function delete_inventory_item()
	{
		extract($_POST);
		$del = $this->conn->query("UPDATE `inventory_items` SET delete_flag = 1 WHERE id = '{$id}'");

		if ($del) {
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success', "Inventory Item has been deleted successfully.");
		} else {
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}

		return json_encode($resp);
	}



	public function sales_entry()
	{
		// Start session if not already started
		if (session_status() === PHP_SESSION_NONE) {
			session_start();
		}

		// Initialize response
		$resp = ['status' => 'failed', 'msg' => 'An unexpected error occurred.'];

		// Check if the request method is POST
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			$resp['msg'] = "Invalid request method.";
			echo json_encode($resp);
			exit;
		}

		// Get input data
		$sales_code = isset($_POST['sales_code']) ? $this->conn->real_escape_string($_POST['sales_code']) : null;
		$po_number = isset($_POST['po_number']) ? $this->conn->real_escape_string($_POST['po_number']) : null;
		$purchase_date = isset($_POST['purchase_date']) ? $this->conn->real_escape_string($_POST['purchase_date']) : null;
		$product_id = isset($_POST['product_id']) ? $this->conn->real_escape_string($_POST['product_id']) : null;
		$quantity = isset($_POST['quantity']) ? (float)$this->conn->real_escape_string($_POST['quantity']) : null;
		$gl_code = isset($_POST['gl_code']) ? $this->conn->real_escape_string($_POST['gl_code']) : null;
		$selling_price = isset($_POST['selling_price']) ? (float)$this->conn->real_escape_string($_POST['selling_price']) : null;
		$total_price = isset($_POST['total_price']) ? (float)$this->conn->real_escape_string($_POST['total_price']) : null;

		// Ensure all required fields are provided
		if (empty($sales_code) || empty($gl_code)  || empty($po_number) || empty($purchase_date) || empty($product_id) || is_null($quantity) || is_null($selling_price) || is_null($total_price)) {
			$resp['msg'] = "All fields are required.";
			echo json_encode($resp);
			exit;
		}

		// Check if user ID is set in the session
		if (!isset($_SESSION['userdata']['id'])) {
			$resp['msg'] = "User is not logged in.";
			echo json_encode($resp);
			exit;
		}

		// Get user ID from session safely
		$user_id = $this->conn->real_escape_string($_SESSION['userdata']['id']);

		// Check for existing sale to prevent duplicates based on sales_code
		$checkSql = "SELECT COUNT(*) as count FROM `sales` WHERE `sales_code` = '$sales_code'";
		$checkResult = $this->conn->query($checkSql);

		if ($checkResult === false) {
			$resp['msg'] = "Database error: " . $this->conn->error;
			echo json_encode($resp);
			exit;
		}

		$row = $checkResult->fetch_assoc();
		if ($row['count'] > 0) {
			$resp['msg'] = "Sale with code '$sales_code' already exists.";
			echo json_encode($resp);
			exit;
		}

		// Prepare data for insertion into sales table
		$data = [
			'sales_code' => $sales_code,
			'po_number' => $po_number,
			'purchase_date' => $purchase_date,
			'user_id' => $user_id,
			'product_id' => $product_id,
			'quantity' => $quantity,
			'gl_code' => $gl_code,
			'selling_price' => $selling_price,
			'total_price' => $total_price
		];

		// Prepare the SQL statement
		$fields = implode(", ", array_keys($data));
		$values = implode(", ", array_map(function ($value) {
			return is_null($value) ? 'NULL' : "'$value'";
		}, array_values($data)));

		$sql = "INSERT INTO `sales` ($fields) VALUES ($values)";

		if ($this->conn->query($sql)) {
			// Get the last inserted ID for the sales entry
			$id = $this->conn->insert_id;

			// After successful insert, update the stocks table
			// Check if stock entry exists for this product
			$stockCheckSql = "SELECT * FROM `stocks` WHERE `product_id` = '$product_id'";
			$stockCheckResult = $this->conn->query($stockCheckSql);

			if ($stockCheckResult === false) {
				$resp['msg'] = "Database error: " . $this->conn->error;
				echo json_encode($resp);
				exit;
			}

			if ($stockCheckResult->num_rows > 0) {
				// Stock entry exists, update the available_stocks
				$stockRow = $stockCheckResult->fetch_assoc();
				$new_stock = $stockRow['available_stocks'] - $quantity;
				if ($new_stock < 0) {
					$resp['msg'] = "Insufficient stock available.";
					echo json_encode($resp);
					exit;
				}
				$updateStockSql = "UPDATE `stocks` SET `available_stocks` = '$new_stock', `date_updated` = CURRENT_TIMESTAMP WHERE `product_id` = '$product_id'";
				$this->conn->query($updateStockSql);
			} else {
				$resp['msg'] = "No stock entry found for this product.";
				echo json_encode($resp);
				exit;
			}

			// Update the `stock_reports` table with the sales ID
			$insertStockReportsSql = "
				INSERT INTO `stock_reports` (`id`, `report_datetime`, `product_id`, `stock_entries`, `available_stocks`, `stocks_sold`, `entry_type`)
				VALUES ('$id', CURRENT_TIMESTAMP, '$product_id', 0, '$new_stock', '$quantity', 0)
			";

			if ($this->conn->query($insertStockReportsSql)) {
				// Return success response
				$resp['status'] = 'success';
				$resp['msg'] = "Sales entry has been successfully added, stocks updated, and new stock report entry created.";
			} else {
				$resp['msg'] = "Failed to insert into stock reports: " . $this->conn->error;
			}
		} else {
			$resp['msg'] = "An error occurred during the operation.";
			$resp['err'] = "Error: " . $this->conn->error;
		}

		echo json_encode($resp);
	}



	public function delete_sale()
	{
		if (session_status() === PHP_SESSION_NONE) {
			session_start(); // Start session if not already active
		}

		$resp = ['status' => 'failed', 'msg' => 'An unexpected error occurred.'];
		$sale_id = isset($_POST['id']) ? $_POST['id'] : null;

		if (is_null($sale_id)) {
			$resp['msg'] = "Invalid sale ID.";
			return json_encode($resp);
		}

		// Retrieve the product_id and quantity from the sale
		$sale_id = $this->conn->real_escape_string($sale_id); // Prevent SQL injection
		$sale = $this->conn->query("SELECT product_id, quantity FROM `sales` WHERE `id` = '$sale_id'")->fetch_assoc();

		if ($sale) {
			$product_id = $sale['product_id'];
			$quantity = $sale['quantity'];

			// Update the stocks table to add the quantity back
			$update_stock = $this->conn->query("UPDATE `stocks` SET available_stocks = available_stocks + $quantity WHERE product_id = '$product_id'");

			if ($update_stock) {
				// Proceed with deleting the sale entry
				$sql = "DELETE FROM `sales` WHERE `id` = '$sale_id'";
				if ($this->conn->query($sql)) {
					if ($this->conn->affected_rows > 0) {
						// Now update the stock_reports status using the correct id
						$updateStockReportStatusSql = "UPDATE `stock_reports` SET `status` = 'Deleted' WHERE `id` = '$sale_id'";
						if ($this->conn->query($updateStockReportStatusSql)) {
							$resp['status'] = 'success';
							$resp['msg'] = "Sale entry with ID '{$sale_id}' has been successfully deleted and stocks updated.";
						} else {
							$resp['msg'] = "Failed to update stock reports status.";
							$resp['err'] = "Error: " . $this->conn->error;
						}
					} else {
						$resp['msg'] = "No sale entry found with ID '{$sale_id}'.";
					}
				} else {
					$resp['msg'] = "An error occurred during the deletion operation.";
					$resp['err'] = "Error: " . $this->conn->error;
				}
			} else {
				$resp['msg'] = "Failed to update stocks.";
			}
		} else {
			$resp['msg'] = "Sale entry not found.";
		}

		return json_encode($resp);
	}


	public function check_status()
	{
		if (session_status() === PHP_SESSION_NONE) {
			session_start(); // Start session if not already active
		}

		$resp = ['status' => 'failed', 'msg' => 'An unexpected error occurred.'];
		$code = isset($_GET['code']) ? $_GET['code'] : null;  // entry_code or sales_code
		$type = isset($_GET['type']) ? $_GET['type'] : null;  // Table type (inventory_entries or sales)

		if (is_null($code) || is_null($type)) {
			$resp['msg'] = "Invalid request. Missing code or type.";
			echo json_encode($resp);
			return;
		}

		// Escape the code and type to prevent SQL injection
		$code = $this->conn->real_escape_string($code);
		$type = $this->conn->real_escape_string($type);

		// Determine the table and column to query based on the 'type' parameter
		$table = '';
		$code_column = '';
		if ($type === 'inventory_entries') {
			$table = 'inventory_entries';
			$code_column = 'entry_code';
		} elseif ($type === 'sales') {
			$table = 'sales';
			$code_column = 'sales_code';
		} else {
			$resp['msg'] = "Invalid type. Must be 'inventory_entries' or 'sales'.";
			echo json_encode($resp);
			return;
		}

		// Query to check the status of the entry
		$sql = "SELECT `status` FROM `$table` WHERE `$code_column` = '$code' LIMIT 1";
		$result = $this->conn->query($sql);

		if ($result && $result->num_rows > 0) {
			$row = $result->fetch_assoc();
			if ($row['status'] == 1) {
				// Return APPROVED status
				$resp['status'] = 'APPROVED';
			} else {
				// Return NOT_APPROVED status
				$resp['status'] = 'NOT_APPROVED';
			}
		} else {
			$resp['msg'] = "No entry found with code '{$code}' in table '{$table}'.";
		}

		echo json_encode($resp);
	}




	public function update_status()
	{
		if (session_status() === PHP_SESSION_NONE) {
			session_start(); // Start session if not already active
		}
	
		header('Content-Type: application/json');
	
		$resp = ['status' => 'failed', 'msg' => 'An unexpected error occurred.'];
	
		// Decode JSON input
		$input_data = json_decode(file_get_contents('php://input'), true);
	
		$entry_codes = $input_data['entry_codes'] ?? null; // Array of entry codes
		$status = $input_data['status'] ?? null;
	
		// Validate input data
		if (is_null($entry_codes) || !is_array($entry_codes) || empty($entry_codes)) {
			$resp['msg'] = "Invalid request. Missing or invalid entry codes.";
			echo json_encode($resp);
			return;
		}
	
		if (is_null($status) || !in_array($status, [0, 1, 2])) {
			$resp['msg'] = "Invalid status value. Allowed values are 0 (NO STATUS), 1 (APPROVED), or 2 (DENIED).";
			echo json_encode($resp);
			return;
		}
	
		$status = (int)$status;
	
		// Sanitize and cast entry codes to integers
		$sanitized_entry_codes = array_map('intval', $entry_codes);
	
		// Ensure there's at least one valid entry code
		if (count($sanitized_entry_codes) === 0) {
			$resp['msg'] = "No valid entry codes provided.";
			echo json_encode($resp);
			return;
		}
	
		// Build the placeholders for the IN clause based on the entry codes
		$placeholders = implode(',', array_fill(0, count($sanitized_entry_codes), '?'));
	
		// Check current statuses of selected entries
		$check_sql = "SELECT `entry_code`, `status` FROM `inventory_entries` WHERE `entry_code` IN ($placeholders)";
		$stmt = $this->conn->prepare($check_sql);
	
		if (!$stmt) {
			$resp['msg'] = "Failed to prepare the query for status check.";
			$resp['error'] = $this->conn->error;
			echo json_encode($resp);
			return;
		}
	
		// Bind parameters as integers for entry codes
		$stmt->bind_param(str_repeat('i', count($sanitized_entry_codes)), ...$sanitized_entry_codes);
		$stmt->execute();
		$check_result = $stmt->get_result();
	
		if (!$check_result || $check_result->num_rows === 0) {
			$resp['msg'] = "No inventory entries found with the provided entry codes.";
			echo json_encode($resp);
			return;
		}
	
		// Determine entries already in the desired status
		$existing_entries = [];
		while ($row = $check_result->fetch_assoc()) {
			if ((int)$row['status'] === $status) {
				$existing_entries[] = $row['entry_code'];
			}
		}
	
		$stmt->close();
	
		// Filter out entries already updated
		$entry_codes_to_update = array_diff($sanitized_entry_codes, $existing_entries);
	
		if (empty($entry_codes_to_update)) {
			$statusText = $status === 1 ? 'APPROVED' : ($status === 2 ? 'DENIED' : 'NO STATUS');
			$resp['msg'] = "All selected entries are already marked as '{$statusText}'.";
			$resp['status'] = 'no_update';
			echo json_encode($resp);
			return;
		}
	
		// Update statuses for remaining entries
		$update_placeholders = implode(',', array_fill(0, count($entry_codes_to_update), '?'));
		$update_sql = "UPDATE `inventory_entries` SET `status` = ? WHERE `entry_code` IN ($update_placeholders)";
		$stmt = $this->conn->prepare($update_sql);
	
		if (!$stmt) {
			$resp['msg'] = "Failed to prepare the update query.";
			$resp['error'] = $this->conn->error;
			echo json_encode($resp);
			return;
		}
	
		// Prepare parameters for the update query (status followed by entry codes)
		$params = array_merge([$status], $entry_codes_to_update);
	
		// Bind the status (as integer) and the entry codes (as integers)
		$stmt->bind_param(str_repeat('i', 1) . str_repeat('i', count($entry_codes_to_update)), ...$params);
	
		if ($stmt->execute()) {
			if ($stmt->affected_rows > 0) {
				$statusText = $status === 1 ? 'APPROVED' : ($status === 2 ? 'DENIED' : 'NO STATUS');
				$resp['status'] = 'success';
				$resp['msg'] = "The status of the selected entries has been successfully updated to '{$statusText}'.";
			} else {
				$resp['msg'] = "No entries were updated. Please check the provided entry codes.";
			}
		} else {
			$resp['msg'] = "Failed to update entry statuses.";
			$resp['error'] = $this->conn->error;
		}
	
		$stmt->close();
	
		echo json_encode($resp);
		error_log("Check SQL: " . $check_sql);
		error_log("Update SQL: " . $update_sql);
	}
	


	// Function to filter sales entries by PO number
	public function filter_sales_by_po_number()
	{
		$resp = ['status' => 'failed', 'msg' => 'An unexpected error occurred.'];
		$po_number = isset($_POST['po_number']) ? $_POST['po_number'] : null;

		// Validate PO number input
		if (is_null($po_number) || empty($po_number)) {
			$resp['msg'] = "Invalid request. PO number is missing or empty.";
			return json_encode($resp);
		}

		// Escape PO number to prevent SQL injection
		$po_number = $this->conn->real_escape_string($po_number);

		// Prepare SQL query to filter sales records by PO number
		$sql = "SELECT * FROM `sales` WHERE `po_number` LIKE '%$po_number%'";
		$result = $this->conn->query($sql);

		if (!$result) {
			// Query failed
			$resp['msg'] = "Error executing query: " . $this->conn->error;
			return json_encode($resp);
		}
		// Process query results
		$status_map = [
			0 => 'NO STATUS',
			1 => 'APPROVED',
			2 => 'DENIED'
		]; // Status mapping for better readability

		// Fetch the filtered records
		$entries = [];
		while ($row = $result->fetch_assoc()) {
			$row['status_label'] = $status_map[$row['status']] ?? 'UNKNOWN STATUS';
			$entries[] = $row; // Add each entry to the array
		}

		// Check if any entries were found
		if (count($entries) > 0) {
			$resp['status'] = 'success';
			$resp['filteredEntries'] = $entries;
			$resp['msg'] = "Entries successfully retrieved."; // Provide a success message
		} else {
			$resp['status'] = 'success'; // Even though no records were found, mark as success
			$resp['filteredEntries'] = [];
			$resp['msg'] = "No sales entries found for the PO number '{$po_number}'.";
		}

		return json_encode($resp);
	}

	// Function to fetch all sales entries
	public function fetch_all_sales($user_type, $user_id)
	{
		// Initialize where condition based on user type
		$swhere = "";
		if ($user_type != 1 && $user_type != 3) { // For non-admin and non-manager users
			$swhere = " WHERE user_id = '{$user_id}' "; // Filter by the logged-in user ID
		}

		// SQL query to fetch sales data with optional filters
		$query = "SELECT s.id, s.purchase_date, s.product_id, s.quantity, s.sales_code, s.selling_price, s.user_id, s.status, s.po_number 
              FROM `sales` s {$swhere} ORDER BY date(s.purchase_date) ASC";

		// Execute query
		$result = $this->conn->query($query);

		// Initialize response array
		$resp = ['status' => 'failed', 'msg' => 'No sales data found.', 'allSales' => []];

		// Check if there are results
		if ($result->num_rows > 0) {
			$sales_data = [];
			while ($row = $result->fetch_assoc()) {
				// Fetch product details
				$product = $this->conn->query("SELECT name FROM products WHERE id = '{$row['product_id']}'")->fetch_assoc();
				$total_price = $row['quantity'] * $row['selling_price']; // Total price calculation

				// Map the status values to human-readable statuses
				$status_map = [
					0 => 'NO STATUS',
					1 => 'APPROVED',
					2 => 'DENIED'
				];
				$status = isset($row['status']) ? $row['status'] : 0;

				// Add formatted sales data to the result array
				$sales_data[] = [
					'purchase_date' => date("M d, Y", strtotime($row['purchase_date'])),
					'product_name' => htmlspecialchars($product['name'], ENT_QUOTES),
					'quantity' => number_format($row['quantity']),
					'selling_price' => "₱" . number_format($row['selling_price'], 2),
					'total_price' => "₱" . number_format($total_price, 2),
					'status' => $status_map[$status] ?? 'NO STATUS',
					'po_number' => !empty($row['po_number']) ? htmlspecialchars($row['po_number']) : 'N/A',
					'user_id' => $row['user_id'],
					'status_code' => $status,
					'sales_code' => $row['sales_code']
				];
			}

			// Return success response with all sales data
			$resp['status'] = 'success';
			$resp['allSales'] = $sales_data;
			$resp['msg'] = "All sales data successfully retrieved.";
		}

		return json_encode($resp);
	}

	public function filter_inventory_by_po_number()
	{
		$resp = ['status' => 'failed', 'msg' => 'An unexpected error occurred.'];
		$po_number = isset($_POST['po_number']) ? $_POST['po_number'] : null;

		// Validate PO number input
		if (is_null($po_number) || empty($po_number)) {
			$resp['msg'] = "Invalid request. PO number is missing or empty.";
			return json_encode($resp);
		}

		// Escape PO number to prevent SQL injection
		$po_number = $this->conn->real_escape_string($po_number);

		// Query to fetch filtered inventory entries
		$sql = "
		SELECT 
			i.id, 
			i.entry_code, 
			i.entry_date, 
			i.description, 
			i.quantity, 
			COALESCE(i.status, 0) AS status, 
			i.remarks, 
			i.product_id, 
			p.name AS product_name, 
			p.purchase_price AS product_price, 
			u.username AS user_inventory_username,
			u.type AS user_inventory_type
		FROM 
			`inventory_entries` i
		LEFT JOIN 
			`products` p ON i.product_id = p.id
		LEFT JOIN 
			`users_inventory` u ON i.user_id = u.id
		WHERE 
			i.remarks LIKE '%$po_number%'
		";

		// Execute the query and fetch the results
		$result = $this->conn->query($sql);

		if (!$result) {
			// Query execution failed
			$resp['msg'] = "Error executing query: " . $this->conn->error;
			return json_encode($resp);
		}

		// Process query results
		$entries = [];
		$status_map = [
			0 => 'NO STATUS',
			1 => 'APPROVED',
			2 => 'DENIED'
		]; // Status mapping for better readability

		while ($row = $result->fetch_assoc()) {
			// Map status to a readable format
			$row['status_label'] = $status_map[$row['status']] ?? 'UNKNOWN STATUS';
			// No need to manually add user_type since it's already fetched as user_inventory_type
			$entries[] = $row; // Add entry to results array
		}

		// Check if any entries were found
		if (count($entries) > 0) {
			$resp['status'] = 'success';
			$resp['filteredEntries'] = $entries;
			$resp['msg'] = "Entries successfully retrieved."; // Success message
		} else {
			$resp['status'] = 'success'; // Mark as success even if no entries are found
			$resp['filteredEntries'] = [];
			$resp['msg'] = "No inventory entries found for the PO number '{$po_number}'.";
		}

		// Return the user type along with filtered entries
		return json_encode($resp);
	}

	public function get_purchase_price_by_product_name($product_name)
	{
		$resp = ['status' => 'failed', 'msg' => 'An unexpected error occurred.'];

		// Validate the product name input
		if (empty($product_name)) {
			$resp['msg'] = "Invalid request. Product name is missing or empty.";
			return json_encode($resp); // Return JSON immediately if the product name is invalid
		}

		// Escape product name to prevent SQL injection
		$product_name = $this->conn->real_escape_string($product_name);

		// Query to fetch purchase price from the products table
		$sql = "
		SELECT 
			p.purchase_price 
		FROM 
			`products` p
		WHERE 
			p.name = '$product_name'
		LIMIT 1
		";

		// Execute the query and fetch the result
		$result = $this->conn->query($sql);

		if (!$result) {
			// Query execution failed
			$resp['msg'] = "Error executing query: " . $this->conn->error;
			return json_encode($resp);
		}

		// Check if a product with the specified name was found
		if ($row = $result->fetch_assoc()) {
			// Product found, return success with purchase price
			$resp['status'] = 'success';
			$resp['purchase_price'] = number_format((float)$row['purchase_price'], 2); // Format as a float with 2 decimal places
			$resp['msg'] = "Purchase price retrieved successfully.";
		} else {
			// No product found with the given name
			$resp['status'] = 'failed';
			$resp['msg'] = "No product found with the name '{$product_name}'.";
		}

		// Return the result as a JSON string
		return json_encode($resp);
	}


	// Function to fetch all inventory entries
	public function fetch_all_inventory($user_type, $user_id)
	{
		// Initialize where condition based on user type
		$swhere = "";
		if ($user_type != 1 && $user_type != 3) { // For non-admin and non-manager users
			$swhere = " WHERE i.user_id = '{$user_id}' "; // Filter by the logged-in user ID
		}

		// SQL query to fetch inventory data with optional filters
		$query = "SELECT i.id, i.entry_code, i.entry_date, i.description, i.remarks, i.user_id, i.date_created, i.date_updated, i.quantity, i.status, p.name AS product_name, p.purchase_price
              FROM `inventory_entries` i
              LEFT JOIN `products` p ON i.product_id = p.id {$swhere} ORDER BY date(i.entry_date) ASC";

		// Execute query
		$result = $this->conn->query($query);

		// Initialize response array
		$resp = ['status' => 'failed', 'msg' => 'No inventory data found.', 'allInventory' => []];

		// Check if there are results
		if ($result->num_rows > 0) {
			$inventory_data = [];
			while ($row = $result->fetch_assoc()) {
				// Calculate total price based on quantity and product purchase price
				$total_price = number_format($row['quantity'] * $row['purchase_price'], 2);

				// Add formatted inventory data to the result array
				$inventory_data[] = [
					'entry_code' => htmlspecialchars($row['entry_code'], ENT_QUOTES),
					'entry_date' => date("M d, Y", strtotime($row['entry_date'])),
					'description' => htmlspecialchars($row['description'], ENT_QUOTES),
					'remarks' => htmlspecialchars($row['remarks'], ENT_QUOTES), // PO number stored in remarks
					'quantity' => number_format($row['quantity']),
					'product_name' => htmlspecialchars($row['product_name'], ENT_QUOTES),
					'total_price' => '₱' . $total_price, // Format total price
					'status' => $row['status'] == 1 ? 'Active' : 'Inactive',
					'user_id' => $row['user_id'],
					'date_created' => $row['date_created'],
					'date_updated' => $row['date_updated'],
				];
			}

			// Return success response with all inventory data
			$resp['status'] = 'success';
			$resp['allInventory'] = $inventory_data;
			$resp['msg'] = "All inventory data successfully retrieved.";
		}

		return json_encode($resp);
	}






	public function update_sales_status()
	{
		if (session_status() === PHP_SESSION_NONE) {
			session_start(); // Start session if not already active
		}
	
		header('Content-Type: application/json');
	
		$resp = ['status' => 'failed', 'msg' => 'An unexpected error occurred.'];
	
		// Decode JSON input
		$input_data = json_decode(file_get_contents('php://input'), true);
	
		$sales_codes = $input_data['sales_codes'] ?? null; // Array of sales codes
		$status = $input_data['status'] ?? null;
	
		// Validate input data
		if (is_null($sales_codes) || !is_array($sales_codes) || empty($sales_codes)) {
			$resp['msg'] = "Invalid request. Missing or invalid sales codes.";
			echo json_encode($resp);
			return;
		}
	
		if (is_null($status) || !in_array($status, [1, 2])) {
			$resp['msg'] = "Invalid status value. Allowed values are 1 (APPROVED) or 2 (DENIED).";
			echo json_encode($resp);
			return;
		}
	
		$status = (int)$status;
	
		// Sanitize and cast sales codes to integers
		$sanitized_sales_codes = array_map('intval', $sales_codes);
	
		// Ensure there's at least one valid sales code
		if (count($sanitized_sales_codes) === 0) {
			$resp['msg'] = "No valid sales codes provided.";
			echo json_encode($resp);
			return;
		}
	
		// Build the placeholders for the IN clause based on the sales codes
		$placeholders = implode(',', array_fill(0, count($sanitized_sales_codes), '?'));
	
		// Check current statuses of selected sales entries
		$check_sql = "SELECT `sales_code`, `status` FROM `sales` WHERE `sales_code` IN ($placeholders)";
		$stmt = $this->conn->prepare($check_sql);
	
		if (!$stmt) {
			$resp['msg'] = "Failed to prepare the query for status check.";
			$resp['error'] = $this->conn->error;
			echo json_encode($resp);
			return;
		}
	
		// Bind parameters as integers for sales codes
		$stmt->bind_param(str_repeat('i', count($sanitized_sales_codes)), ...$sanitized_sales_codes);
		$stmt->execute();
		$check_result = $stmt->get_result();
	
		if (!$check_result || $check_result->num_rows === 0) {
			$resp['msg'] = "No sales entries found with the provided sales codes.";
			echo json_encode($resp);
			return;
		}
	
		// Determine entries already in the desired status
		$existing_entries = [];
		while ($row = $check_result->fetch_assoc()) {
			if ((int)$row['status'] === $status) {
				$existing_entries[] = $row['sales_code'];
			}
		}
	
		$stmt->close();
	
		// Filter out entries already updated
		$sales_codes_to_update = array_diff($sanitized_sales_codes, $existing_entries);
	
		if (empty($sales_codes_to_update)) {
			$statusText = $status === 1 ? 'APPROVED' : 'DENIED';
			$resp['msg'] = "All selected entries are already marked as '{$statusText}'.";
			$resp['status'] = 'no_update';
			echo json_encode($resp);
			return;
		}
	
		// Update statuses for remaining entries
		$update_placeholders = implode(',', array_fill(0, count($sales_codes_to_update), '?'));
		$update_sql = "UPDATE `sales` SET `status` = ? WHERE `sales_code` IN ($update_placeholders)";
		$stmt = $this->conn->prepare($update_sql);
	
		if (!$stmt) {
			$resp['msg'] = "Failed to prepare the update query.";
			$resp['error'] = $this->conn->error;
			echo json_encode($resp);
			return;
		}
	
		// Prepare parameters for the update query (status followed by sales codes)
		$params = array_merge([$status], $sales_codes_to_update);
	
		// Bind the status (as integer) and the sales codes (as integers)
		$stmt->bind_param(str_repeat('i', 1) . str_repeat('i', count($sales_codes_to_update)), ...$params);
	
		if ($stmt->execute()) {
			if ($stmt->affected_rows > 0) {
				$statusText = $status === 1 ? 'APPROVED' : 'DENIED';
				$resp['status'] = 'success';
				$resp['msg'] = "The status of the selected sales entries has been successfully updated to '{$statusText}'.";
			} else {
				$resp['msg'] = "No entries were updated. Please check the provided sales codes.";
			}
		} else {
			$resp['msg'] = "Failed to update sales entry statuses.";
			$resp['error'] = $this->conn->error;
		}
	
		$stmt->close();
	
		echo json_encode($resp);
		error_log("Check SQL: " . $check_sql);
		error_log("Update SQL: " . $update_sql);
	}
	
}
/*
	function save_group()
	{
		extract($_POST);
		$data = "";
		foreach ($_POST as $k => $v) {
			if (!in_array($k, array('id'))) {
				if (!is_numeric($v))
					$v = $this->conn->real_escape_string($v);
				if (!empty($data)) $data .= ",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		if (empty($id)) {
			$sql = "INSERT INTO `group_list` set {$data} ";
		} else {
			$sql = "UPDATE `group_list` set {$data} where id = '{$id}' ";
		}
		$check = $this->conn->query("SELECT * FROM `group_list` where `name` = '{$name}' and delete_flag = 0 " . ($id > 0 ? " and id != '{$id}'" : ""));
		if ($check->num_rows > 0) {
			$resp['status'] = 'failed';
			$resp['msg'] = " Account's Group Name already exists.";
		} else {
			$save = $this->conn->query($sql);
			if ($save) {
				$gid = !empty($id) ? $id : $this->conn->insert_id;
				$resp['status'] = 'success';
				if (empty($id))
					$resp['msg'] = " Account's Group has successfully added.";
				else
					$resp['msg'] = " Account's Group details has been updated successfully.";
			} else {
				$resp['status'] = 'failed';
				$resp['msg'] = "An error occured.";
				$resp['err'] = $this->conn->error . "[{$sql}]";
			}
		}
		if ($resp['status'] == 'success')
			$this->settings->set_flashdata('success', $resp['msg']);
		return json_encode($resp);
	}
	function delete_group()
	{
		extract($_POST);
		$del = $this->conn->query("UPDATE `group_list` set delete_flag = 1 where id = '{$id}'");
		if ($del) {
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success', " Account's Group has been deleted successfully.");
		} else {
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function save_account()
	{
		extract($_POST);
		$data = "";
		foreach ($_POST as $k => $v) {
			if (!in_array($k, array('id'))) {
				if (!is_numeric($v))
					$v = $this->conn->real_escape_string($v);
				if (!empty($data)) $data .= ",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		if (empty($id)) {
			$sql = "INSERT INTO `account_list` set {$data} ";
		} else {
			$sql = "UPDATE `account_list` set {$data} where id = '{$id}' ";
		}
		$check = $this->conn->query("SELECT * FROM `account_list` where `name` ='{$name}' and delete_flag = 0 " . ($id > 0 ? " and id != '{$id}' " : ""))->num_rows;
		if ($check > 0) {
			$resp['status'] = 'failed';
			$resp['msg'] = " Account's Name already exists.";
		} else {
			$save = $this->conn->query($sql);
			if ($save) {
				$rid = !empty($id) ? $id : $this->conn->insert_id;
				$resp['status'] = 'success';
				if (empty($id))
					$resp['msg'] = " Account has successfully added.";
				else
					$resp['msg'] = " Account has been updated successfully.";
			} else {
				$resp['status'] = 'failed';
				$resp['msg'] = "An error occured.";
				$resp['err'] = $this->conn->error . "[{$sql}]";
			}
			if ($resp['status'] == 'success')
				$this->settings->set_flashdata('success', $resp['msg']);
		}
		return json_encode($resp);
	}
	function delete_account()
	{
		extract($_POST);
		$del = $this->conn->query("UPDATE `account_list` set delete_flag = 1 where id = '{$id}'");
		if ($del) {
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success', " Account has been deleted successfully.");
		} else {
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function save_journal()
	{
		if (empty($_POST['id'])) {
			$prefix = date("Ym-");
			$code = sprintf("%'.05d", 1);
			while (true) {
				$check = $this->conn->query("SELECT * FROM `journal_entries` where `code` = '{$prefix}{$code}' ")->num_rows;
				if ($check > 0) {
					$code = sprintf("%'.05d", ceil($code) + 1);
				} else {
					break;
				}
			}
			$_POST['code'] = $prefix . $code;
			$_POST['user_id'] = $this->settings->userdata('id');
		}
		extract($_POST);
		$data = "";
		foreach ($_POST as $k => $v) {
			if (!in_array($k, array('id'))  && !is_array($_POST[$k])) {
				if (!is_numeric($v) && !is_null($v))
					$v = $this->conn->real_escape_string($v);
				if (!empty($data)) $data .= ",";
				if (!is_null($v))
					$data .= " `{$k}`='{$v}' ";
				else
					$data .= " `{$k}`= NULL ";
			}
		}
		if (empty($id)) {
			$sql = "INSERT INTO `journal_entries` set {$data} ";
		} else {
			$sql = "UPDATE `journal_entries` set {$data} where id = '{$id}' ";
		}
		$save = $this->conn->query($sql);
		if ($save) {
			$jid = !empty($id) ? $id : $this->conn->insert_id;
			$data = "";
			$this->conn->query("DELETE FROM `journal_items` where journal_id = '{$jid}'");
			foreach ($account_id as $k => $v) {
				if (!empty($data)) $data .= ", ";
				$data .= "('{$jid}','{$v}','{$group_id[$k]}','{$amount[$k]}')";
			}
			if (!empty($data)) {
				$sql = "INSERT INTO `journal_items` (`journal_id`,`account_id`,`group_id`,`amount`) VALUES {$data}";
				$save2 = $this->conn->query($sql);
				if ($save2) {
					$resp['status'] = 'success';
					if (empty($id)) {
						$resp['msg'] = " Journal Entry has successfully added.";
					} else
						$resp['msg'] = " Journal Entry has been updated successfully.";
				} else {
					$resp['status'] = 'failed';
					if (empty($id)) {
						$resp['msg'] = " Journal Entry has failed to save.";
						$this->conn->query("DELETE FROM `journal_entries` where id = '{$jid}'");
					} else
						$resp['msg'] = " Journal Entry has failed to update.";
					$resp['error'] = $this->conn->error;
				}
			} else {
				$resp['status'] = 'failed';
				if (empty($id)) {
					$resp['msg'] = " Journal Entry has failed to save.";
					$this->conn->query("DELETE FROM `journal_entries` where id = '{$jid}'");
				} else
					$resp['msg'] = " Journal Entry has failed to update.";
				$resp['error'] = "Journal Items is empty";
			}
		} else {
			$resp['status'] = 'failed';
			$resp['msg'] = "An error occured.";
			$resp['err'] = $this->conn->error . "[{$sql}]";
		}
		if ($resp['status'] == 'success')
			$this->settings->set_flashdata('success', $resp['msg']);
		return json_encode($resp);
	}
	function delete_journal()
	{
		extract($_POST);
		$del = $this->conn->query("DELETE FROM `journal_entries` where id = '{$id}'");
		if ($del) {
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success', " Journal Entry has been deleted successfully.");
		} else {
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function cancel_journal()
	{
		extract($_POST);
		$del = $this->conn->query("UPDATE `journal_entries` set `status` = '3' where id = '{$id}'");
		if ($del) {
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success', " journaling has successfully cancelled.");
		} else {
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function save_reservation()
	{
		$_POST['journal'] = $_POST['date'] . " " . $_POST['time'];
		extract($_POST);
		$capacity = $this->conn->query("SELECT `" . ($seat_type == 1 ? "first_class_capacity" : "economy_capacity") . "` FROM group_list where id in (SELECT group_id FROM `journal_entries` where id ='{$journal_id}') ")->fetch_array()[0];
		$reserve = $this->conn->query("SELECT * FROM `reservation_list` where journal_id = '{$journal_id}' and journal='{$journal}' and seat_type='$seat_type'")->num_rows;
		$slot = $capacity - $reserve;
		if (count($firstname) > $slot) {
			$resp['status'] = "failed";
			$resp['msg'] = "This journal has only [{$slot}] left for the selected seat type/group";
			return json_encode($resp);
		}
		$data = "";
		$sn = [];
		$prefix = $seat_type == 1 ? "FC-" : "E-";
		$seat = sprintf("%'.03d", 1);
		foreach ($firstname as $k => $v) {
			while (true) {
				$check = $this->conn->query("SELECT * FROM `reservation_list` where journal_id = '{$journal_id}' and journal='{$journal}' and seat_num = '{$prefix}{$seat}' and seat_type='$seat_type'")->num_rows;
				if ($check > 0) {
					$seat = sprintf("%'.03d", ceil($seat) + 1);
				} else {
					break;
				}
			}
			$seat_num = $prefix . $seat;
			$seat = sprintf("%'.03d", ceil($seat) + 1);
			$sn[] = $seat_num;
			if (!empty($data)) $data .= ", ";
			$data .= "('{$seat_num}','{$journal_id}','{$journal}','{$v}','{$middlename[$k]}','{$lastname[$k]}','{$seat_type}','{$fare_amount}')";
		}
		if (!empty($data)) {
			$sql = "INSERT INTO `reservation_list` (`seat_num`,`journal_id`,`journal`,`firstname`,`middlename`,`lastname`,`seat_type`,`fare_amount`) VALUES {$data}";
			$save_all = $this->conn->query($sql);
			if ($save_all) {
				$resp['status'] = 'success';
				$resp['msg'] = "Reservation successfully submitted.";
				$get_ids = $this->conn->query("SELECT id from `reservation_list` where `journal_id` = '{$journal_id}' and `journal` = '{$journal}' and seat_type='{$seat_type}' and seat_num in ('" . (implode("','", $sn)) . "') ");
				$res = $get_ids->fetch_all(MYSQLI_ASSOC);
				$ids = array_column($res, 'id');
				$ids = implode(",", $ids);
				$resp['ids'] = $ids;
			} else {
				$resp['status'] = 'failed';
				$resp['msg'] = "An error occured while saving the data. Error: " . $this->conn->error;
				$resp['sql'] = $sql;
			}
		} else {
			$resp['status'] = "failed";
			$resp['msg'] = "No Data to save.";
		}


		if ($resp['status'] == 'success')
			$this->settings->set_flashdata('success', $resp['msg']);
		return json_encode($resp);
	}
	function delete_reservation()
	{
		extract($_POST);
		$del = $this->conn->query("DELETE FROM `reservation_list` where id = '{$id}'");
		if ($del) {
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success', "Reservation Details has been deleted successfully.");
		} else {
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function update_reservation_status()
	{
		extract($_POST);
		$del = $this->conn->query("UPDATE `reservation_list` set `status` = '{$status}' where id = '{$id}'");
		if ($del) {
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success', "reservation Request status has successfully updated.");
		} else {
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
}
*/
$Master = new Master();
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$sysset = new SystemSettings();

error_log("Action received: " . $action); // Log the action for debugging

switch ($action) {
	case 'save_inventory_entry':
		$response = $Master->save_inventory_entry();
		error_log("Response from save_inventory_entry: " . $response); // Log the response
		echo $response;
		break;

	case 'delete_inventory_entry':
		echo $Master->delete_inventory_entry();
		break;

	case 'delete_inventory_item':
		echo $Master->delete_inventory_item();
		break;

	case 'edit_entry':
		echo $Master->edit_entry();
		break;

	case 'delete_product':
		echo $Master->delete_product();
		break;

	case 'save_product':
		echo $Master->save_product();
		break;

	case 'edit_product':
		echo $Master->edit_product();
		break;

	case 'sales_entry':
		echo $Master->sales_entry();
		break;

	case 'delete_sale':
		echo $Master->delete_sale();
		break;

	case 'filter_sales_by_po_number':
		echo $Master->filter_sales_by_po_number();
		break;

	case 'update_status':
		echo $Master->update_status();
		break;

	case 'update_sales_status':
		echo $Master->update_sales_status();
		break;

	case 'check_product_exist':
		if (isset($_GET['product_id'])) {
			$product_id = $_GET['product_id'];
			$productExists = $Master->check_product_exist($product_id);
			// Return the result as JSON
			echo json_encode(['product_exists' => $productExists]);
		} else {
			echo json_encode(['error' => 'Product ID not provided.']);
		}
		break;

	case 'fetch_all_sales':  // Add this case for fetching all sales
		// Ensure user_type and user_id are passed
		if (isset($_GET['user_type']) && isset($_GET['user_id'])) {
			$user_type = $_GET['user_type'];
			$user_id = $_GET['user_id'];

			// Fetch the sales
			$sales_data = $Master->fetch_all_sales($user_type, $user_id);

			// Return the sales data as JSON
			if ($sales_data) {
				echo json_encode(['status' => 'success', 'sales' => $sales_data]);
			} else {
				echo json_encode(['status' => 'failed', 'msg' => 'No sales data found.']);
			}
		} else {
			echo json_encode(['status' => 'failed', 'msg' => 'User type or user ID is missing.']);
		}
		break;

		// Case to filter inventory by PO number (remarks field)
	case 'filter_inventory_by_po_number':
		echo $Master->filter_inventory_by_po_number();
		break;

		// Case to fetch all inventory entries
	case 'fetch_all_inventory':
		// Ensure user_type and user_id are passed
		if (isset($_GET['user_type']) && isset($_GET['user_id'])) {
			$user_type = $_GET['user_type'];
			$user_id = $_GET['user_id'];

			// Fetch the inventory
			$inventory_data = $Master->fetch_all_inventory($user_type, $user_id);

			// Return the inventory data as JSON
			if ($inventory_data) {
				echo json_encode(['status' => 'success', 'inventory' => $inventory_data]);
			} else {
				echo json_encode(['status' => 'failed', 'msg' => 'No inventory data found.']);
			}
		} else {
			echo json_encode(['status' => 'failed', 'msg' => 'User type or user ID is missing.']);
		}
		break;

		// Add the new case for getting purchase price by product name
	case 'get_purchase_price_by_product_name':
		if (isset($_GET['product_name'])) {
			$product_name = $_GET['product_name'];
			echo $Master->get_purchase_price_by_product_name($product_name);
		} else {
			echo json_encode(['error' => 'Product name not provided.']);
		}
		break;

	default:
		echo json_encode(['error' => 'Invalid action.']);
		break;
}



/*
		// Other existing cases
	case 'save_reservation':
		echo $Master->save_reservation();
		break;
	case 'delete_reservation':
		echo $Master->delete_reservation();
		break;
	case 'update_reservation_status':
		echo $Master->update_reservation_status();
		break;
	case 'save_group':
		echo $Master->save_group();
		break;
	case 'delete_group':
		echo $Master->delete_group();
		break;
	case 'save_account':
		echo $Master->save_account();
		break;
	case 'delete_account':
		echo $Master->delete_account();
		break;
	case 'save_journal':
		echo $Master->save_journal();
		break;
	case 'delete_journal':
		echo $Master->delete_journal();
		break;
	case 'cancel_journal':
		echo $Master->cancel_journal();
		break;
	default:
		// echo $sysset->index();
		break;
		*/
