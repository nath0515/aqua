<?php
echo "DEBUG TEST - PHP is working!";
echo "<br>Current time: " . date('Y-m-d H:i:s');
echo "<br>Server: " . $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Test</title>
</head>
<body>
    <h1>Debug Test Page</h1>
    <p>If you can see this, the server is working.</p>
    
    <script>
        alert('JavaScript is working!');
        console.log('Debug test loaded successfully!');
    </script>
</body>
</html> 