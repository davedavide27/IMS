<?php
/*
session_start(); // Start the session

// Check if session variables are set
if (empty($_SESSION)) {
    echo "No session variables are set.";
} else {
    echo "<h2>Current Session Variables:</h2>";
    echo "<ul>";
    foreach ($_SESSION as $key => $value) {
        echo "<li><strong>{$key}:</strong> " . htmlspecialchars(print_r($value, true)) . "</li>";
    }
    echo "</ul>";
}