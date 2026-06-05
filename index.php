<?php
require_once 'config/config.php';
require_once 'config/database.php';

if ($conn) {
    echo "✅ Database Connected Successfully!";
} else {
    echo "❌ Connection Failed!";
}
?>