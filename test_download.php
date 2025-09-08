<?php
// Simple test download file
header('Content-Type: text/plain');
header('Content-Disposition: attachment; filename="test.txt"');

echo "This is a test download file.\n";
echo "If you can see this, the download is working!\n";
echo "Date: " . date('Y-m-d H:i:s');
?>
