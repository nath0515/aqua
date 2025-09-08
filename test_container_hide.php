<!DOCTYPE html>
<html>
<head>
    <title>Test Container Hide Logic</title>
    <style>
        .container-option { 
            padding: 10px; 
            border: 1px solid #ccc; 
            margin: 10px 0; 
            background-color: #f9f9f9;
        }
        .hidden { display: none !important; }
    </style>
</head>
<body>
    <h2>🧪 Test Container Hide Logic</h2>
    
    <div class="container-option" id="containerDiv">
        <input type="checkbox" id="hasContainer">
        <label for="hasContainer">With Container</label>
    </div>
    
    <button onclick="testHide()">Test Hide (container_price = 0)</button>
    <button onclick="testShow()">Test Show (container_price = 250)</button>
    
    <script>
        function testHide() {
            console.log('Testing hide logic...');
            const containerCheckbox = document.getElementById("hasContainer");
            const containerDiv = containerCheckbox.closest('.container-option');
            
            // Simulate container_price = 0
            const containerPrice = 0;
            
            if (containerPrice <= 0) {
                containerDiv.style.display = 'none';
                console.log('Container option hidden');
            } else {
                containerDiv.style.display = 'block';
                console.log('Container option shown');
            }
        }
        
        function testShow() {
            console.log('Testing show logic...');
            const containerCheckbox = document.getElementById("hasContainer");
            const containerDiv = containerCheckbox.closest('.container-option');
            
            // Simulate container_price = 250
            const containerPrice = 250;
            
            if (containerPrice <= 0) {
                containerDiv.style.display = 'none';
                console.log('Container option hidden');
            } else {
                containerDiv.style.display = 'block';
                console.log('Container option shown');
            }
        }
    </script>
</body>
</html>
