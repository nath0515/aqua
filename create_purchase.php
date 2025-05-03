<?php 
    require 'session.php';
    require 'db.php';

    $user_id = $_SESSION['user_id'];

    $sql = "SELECT u.user_id, username, email, role_id, firstname, lastname, address, contact_number FROM users u
    JOIN user_details ud ON u.user_id = ud.user_id
    WHERE u.user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    $sql = "SELECT * FROM products";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $product_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT * FROM payment_method";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $payment_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Orders</title>
        <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
        <link href="css/styles.css" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    </head>
    <body class="sb-nav-fixed">
        <nav class="sb-topnav navbar navbar-expand navbar-dark bg-primary">
            <!-- Navbar Brand-->
            <a class="navbar-brand ps-3" href="index.php">
                <img src="assets/img/aquadrop.png" alt="AquaDrop Logo" style="width: 236px; height: 40px;">
            </a>
            <!-- Sidebar Toggle-->
            <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>     
            <!-- Navbar-->
            <ul class="navbar-nav ms-auto d-flex flex-row align-items-center pe-1">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user fa-fw"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                        <li><a class="dropdown-item" href="#!">Activity Log</a></li>
                        <li><hr class="dropdown-divider" /></li>
                        <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                    </ul>
                </li>
            </ul>
        </nav>
        <div id="layoutSidenav">
            <div id="layoutSidenav_nav">
                <nav class="sb-sidenav accordion sb-sidenav-light" id="sidenavAccordion">
                    <div class="sb-sidenav-menu">
                        <div class="nav">
                            <div class="sb-sidenav-menu-heading">Menu</div>
                            <a class="nav-link" href="index.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                                Dashboard
                            </a>
                            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts" aria-expanded="false" aria-controls="collapseLayouts">
                                <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
                                Order Management
                                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                            </a>
                            <div class="collapse" id="collapseLayouts" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link" href="orders.php">Orders</a>
                                    <a class="nav-link" href="stock.php">Stock</a>
                                </nav>
                            </div>
                            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapsePages" aria-expanded="false" aria-controls="collapsePages">
                                <div class="sb-nav-link-icon"><i class="fas fa-book-open"></i></div>
                                Analytics
                                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                            </a>
                            <div class="collapse" id="collapsePages" aria-labelledby="headingTwo" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link" href="sales.php">Sales</a>
                                    <a class="nav-link" href="expenses.php">Expenses</a>
                                    <a class="nav-link" href="income.php">Income</a>
                                    <a class="nav-link" href="report.php">Report</a>
                                </nav>
                            </div>
                            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts2" aria-expanded="false" aria-controls="collapsePages">
                                <div class="sb-nav-link-icon"><i class="fas fa-book-open"></i></div>
                                Account Management
                                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                            </a>
                            <div class="collapse" id="collapseLayouts2" aria-labelledby="headingThree" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link" href="accounts.php">Accounts</a>
                                    <a class="nav-link" href="rideraccount.php">Add Rider</a>
                                </nav>
                            </div>
                        </div>
                    </div>
                    <div class="sb-sidenav-footer">
                        <div class="small">Logged in as:</div>
                        <?php echo "".$user_data['firstname']." ".$user_data['lastname'];?>
                    </div>
                </nav>
            </div>
            <div id="layoutSidenav_content">
                <main>
                    <div class="container-fluid px-4">
                        <h1 class="mt-4">Orders</h1>
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Order Management</li>
                            <li class="breadcrumb-item active">Orders</li>
                            <li class="breadcrumb-item active">Add Order</li>
                        </ol>
                        <div class="card mb-4">
                        <div class="container mb-4 mt-4">                  
                        <div class="row">
                        <!-- First Column (Form) -->
                        <div class="col-lg-6 col-md-12">
                            <div class="card p-3">
                                <h5 class="mt-2">Add Order</h5>
                                <form id="purchaseForm">
                                    <div class="mb-3">
                                        <label for="product_name" class="form-label">Item</label>
                                        <select name="" id="product_id" class="form-select" required onchange="fetchProductDetails(this.value)">
                                            <option>Select Item</option>
                                            <?php foreach($product_data as $row):?>
                                                <option value="<?php echo $row['product_id']?>"><?php echo $row['product_name']?></option>
                                            <?php endforeach;?>
                                        </select>
                                    </div>
                                    <div class="mb-3 row">
                                        <div class="col">
                                            <label for="price" class="form-label">Quantity</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bi bi-bag-plus"></i></span>
                                                <input type="number" class="form-control" id="quantity" required min="1" max="15" onchange="updateTotalPrice()" required>
                                            </div>
                                        </div>  
                                    </div>
                                    <div class="mb-3 row">
                                        <div class="col">
                                            <label for="price" class="form-label">Unit Price</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bi bi-bag-plus"></i></span>
                                                <input type="number" class="form-control" id="unitprice" required readonly>
                                            </div>
                                        </div>  
                                    </div>
                                    <div class="mb-3 row">
                                        <div class="col">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="hasContainer" onchange="togglePriceInput()">
                                                <label class="form-check-label" for="hasContainer">
                                                    Has Container
                                                </label>
                                            </div>
                                        </div>                    
                                        <div class="col">
                                            <div class="form-check float-end" id="sameQuantityDiv" style="display: none;">
                                                <input class="form-check-input" type="checkbox" id="sameQuantity" onchange="toggleSameQuantity()">
                                                <label class="form-check-label" for="sameQuantity">
                                                    Same quantity as ordered
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3 row" id="containerQuantity" style="display: none;">
                                        <div class="col">
                                            <label class="form-label">Container Quantity</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bi bi-bag-plus"></i></span>
                                                <input type="number" class="form-control" id="containerQuantityInput" value="0" onchange="updateTotalPrice()">
                                            </div>
                                        </div>
                                        <div class="col">
                                            <label class="form-label">Available Quantity</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bi bi-bag-plus"></i></span>
                                                <input type="number" class="form-control" id="availablequantity" value="0" onchange="updateTotalPrice()" readonly>
                                            </div>
                                        </div>    
                                    </div>
                                    <div class="mb-3" id="priceContainer" style="display: none;">
                                        <label for="price" class="form-label">Container Price</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" step="0.01" id="containerprice" name="price" readonly>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="price" class="form-label">Total Price</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" step=0.01 id="totalprice" name="price" readonly>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-end">
                                        <button type="submit" class="btn btn-primary mb-2">Submit</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <script>
                            function togglePriceInput() {
                                const checkbox = document.getElementById('hasContainer');
                                const sameQuantityDiv = document.getElementById('sameQuantityDiv');
                                const containerQuantity = document.getElementById('containerQuantity');
                                const priceContainer = document.getElementById('priceContainer');
                                const containerQuantityInput = document.getElementById('containerQuantityInput');
                                priceContainer.style.display = checkbox.checked ? 'block' : 'none';
                                containerQuantity.style.display = checkbox.checked ? 'block' : 'none';
                                containerQuantityInput.value = '0';
                                sameQuantityDiv.style.display = checkbox.checked ? 'block' : 'none';
                            }
                            function toggleSameQuantity() {
                                const sameQuantityCheckbox = document.getElementById("sameQuantity");
                                const containerQuantityInput = document.getElementById("containerQuantityInput");

                                if (sameQuantityCheckbox.checked) {
                                    containerQuantityInput.readOnly = true;
                                    containerQuantityInput.value = document.getElementById("quantity").value;
                                } else {
                                    containerQuantityInput.readOnly = false;
                                    containerQuantityInput.value = '';
                                }
                                updateTotalPrice();
                            }
                            function updateTotalPrice(){
                                const unitPriceInput = document.getElementById('unitprice');
                                const quantityInput = document.getElementById('quantity');
                                const containerQuantityInput = document.getElementById('containerQuantityInput');
                                const containerPriceInput = document.getElementById('containerprice');
                                const totalPriceInput = document.getElementById('totalprice');
                                

                                let unitprice = unitPriceInput.value;
                                let quantity = quantityInput.value;
                                let containerQuantity = containerQuantityInput.value;
                                let containerPrice = containerPriceInput.value;

                                let totalPrice = (unitprice * quantity) + (containerQuantity * containerPrice);
                                totalPrice = totalPrice.toFixed(2);

                                totalPriceInput.value = totalPrice;
                            }  
                        </script>

                        <!-- Second Column (Receipt and Checkout) -->
                        <div class="col-lg-6 col-md-12 mt-3">
                            <div class="card p-3">
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <button class="btn btn-primary mb-2" onclick="submitReceipt()">Checkout</button>
                                    </div>
                                    <div class="col-6">
                                        <h5 class="text-end" id="totalDisplay">Total Price: ₱0.00</h5>
                                    </div>
                                </div>
                                <h5 class="mt-2 text-center fw-bold">AquaDrop</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="receipt">
                                        <thead>
                                            <tr>
                                                <th>Item Name</th>
                                                <th>Unit Price</th>
                                                <th>Quantity</th>
                                                <th>Has Container</th>
                                                <th>Container Quantity</th>
                                                <th>Container Price</th>
                                                <th>Total Price</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Receipt items will be populated here -->
                                        </tbody>
                                    </table>
                                </div>
                                <div class="col-md-4 mt-3">
                                    <div class="form-group form-group-default">
                                        <label>Payment Method</label>
                                        <select name="payment_id" id="payment_id" class="form-select">
                                            <option value="0">Select Payment Method</option>
                                            <?php foreach($payment_data as $row):?>
                                                <option value="<?php echo $row['payment_id']?>"><?php echo $row['payment_name']?></option>
                                            <?php endforeach;?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
                <footer class="py-4 bg-light mt-auto">
                    <div class="container-fluid px-4">
                        <div class="d-flex align-items-center justify-content-between small">
                            
                        </div>
                    </div>
                </footer>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="js/scripts.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
        <script src="assets/demo/chart-area-demo.js"></script>
        <script src="assets/demo/chart-bar-demo.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
        <script src="js/datatables-simple-demo.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>                                        
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

        <script>
            function fetchProductDetails(productId) {
                if (productId === '') return;
            
                fetch('process_getproductdata.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'product_id=' + encodeURIComponent(productId)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById("quantity").addEventListener("input", function () {
                            let quantity = parseInt(this.value, 10);
                            if (!isNaN(quantity) && quantity >= 10) {
                                document.getElementById("unitprice").value = data.data.water_price_promo;
                            } else {
                                document.getElementById("unitprice").value = data.data.water_price;
                            }
                        });
                        document.getElementById("availablequantity").value = data.data.stock;
                        document.getElementById("containerprice").value = data.data.container_price;
                    } else {
                        console.error("Product not found");
                    }
                })
                .catch(error => console.error('Error:', error));
            }
            
            function addRow() {
                const unitPriceInput = document.getElementById('unitprice');
                const quantityInput = document.getElementById('quantity');
                const containerQuantityInput = document.getElementById('containerQuantityInput');
                const containerPriceInput = document.getElementById('containerprice');
                const totalPriceInput = document.getElementById('totalprice');
                const productIdInput = document.getElementById('product_id');
                const availableInput = document.getElementById('availablequantity');

                let unitprice = parseFloat(unitPriceInput.value);
                let quantity = parseInt(quantityInput.value);
                let containerQuantity = parseInt(containerQuantityInput.value);
                let containerPrice = parseFloat(containerPriceInput.value);
                let totalPrice = parseFloat(totalPriceInput.value);
                let productId = productIdInput.value;
                let available = parseInt(availableInput.value);
                let hasContainer = document.getElementById("hasContainer").checked;

                if (!productId) {
                    Swal.fire({ icon: 'error', title: 'Error!', text: "Please enter an item." });
                    return;
                } else if (quantity < 1) {
                    Swal.fire({ icon: 'error', title: 'Error!', text: "Please enter a valid quantity." });
                    return;
                } else if (containerQuantity < 1 && hasContainer) {
                    Swal.fire({ icon: 'error', title: 'Error!', text: "Please enter a valid container quantity." });
                    return;
                } else if (containerQuantity > available) {
                    Swal.fire({ icon: 'error', title: 'Error!', text: "Container out of stock." });
                    return;
                }

                fetch("process_getproductdata.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: "product_id=" + encodeURIComponent(productId)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        Swal.fire({ icon: 'error', title: 'Error!', text: data.error });
                        return;
                    }

                    let table = document.getElementById("receipt").getElementsByTagName("tbody")[0];
                    let rows = table.getElementsByTagName("tr");
                    let waterPrice;
                    if (quantity >= 10) {
                        waterPrice = parseFloat(data.data.water_price_promo);
                    } else {
                        waterPrice = parseFloat(data.data.water_price);
                    }
                    let found = false;

                    for (let i = 0; i < rows.length; i++) {
                        let existingProductId = rows[i].cells[8].innerText;
                        if (existingProductId === productId) {
                            // Update existing row
                            let existingQuantity = parseInt(rows[i].cells[2].innerText);
                            let existingContainerQuantity = parseInt(rows[i].cells[4].innerText);
                            let newQuantity = existingQuantity + quantity;
                            let newContainerQuantity = existingContainerQuantity + (hasContainer ? containerQuantity : 0);
                            let newTotal = newQuantity * unitprice + (hasContainer ? newContainerQuantity * containerPrice : 0);

                            rows[i].cells[2].innerText = newQuantity;
                            rows[i].cells[4].innerText = hasContainer ? newContainerQuantity : 0;
                            rows[i].cells[6].innerText = "₱" + newTotal.toFixed(2);
                            found = true;
                            break;
                        }
                    }

                    if (!found) {
                        // Insert new row
                        let newRow = table.insertRow();
                        newRow.insertCell(0).innerText = data.data.product_name;
                        newRow.insertCell(1).innerText = "₱" + waterPrice.toFixed(2);
                        newRow.insertCell(2).innerText = quantity;
                        newRow.insertCell(3).innerText = hasContainer ? 'Yes' : 'No';
                        newRow.insertCell(4).innerText = containerQuantity;
                        newRow.insertCell(5).innerText = hasContainer ? "₱" + containerPrice.toFixed(2) : "₱0.00";
                        newRow.insertCell(6).innerText = "₱" + totalPrice.toFixed(2);
                        newRow.insertCell(7).innerHTML = "<button type='button' class='btn btn-danger' title='Remove' onclick='deleteRow(this)'><i class='bi bi-trash'></i></button>";
                        newRow.insertCell(8).innerText = productId;
                        newRow.cells[8].style.display = 'none';
                    }

                    // Clear inputs
                    unitPriceInput.value = '';
                    quantityInput.value = '';
                    containerQuantityInput.value = '';
                    containerPriceInput.value = '';
                    totalPriceInput.value = '';
                    productIdInput.value = '';

                    updateTotalPrice1();
                })
                .catch(error => console.error("Error:", error));
            }

            document.getElementById("purchaseForm").addEventListener("submit", function(event) {
                event.preventDefault();
                addRow();
            });

            function deleteRow(button) {
                const row = button.closest("tr");
                row.remove();
                updateTotalPrice1();
            }

            function updateTotalPrice1() {
                let total = 0;
                let table = document.getElementById("receipt");
                let rows = table.getElementsByTagName("tbody")[0].getElementsByTagName("tr");

                for (let i = 0; i < rows.length; i++) {
                    let cell7 = rows[i].getElementsByTagName("td")[6];
                    let price = parseFloat(cell7.innerText.replace("₱", "").replace(",", "")) || 0;
                    total += price;
                }

                document.getElementById("totalDisplay").innerText = "Total Price: ₱" + total.toFixed(2);
            }
            
        </script>
        <script>                                     
        function submitReceipt() {
            let table = document.getElementById("receipt");
            let rows = table.getElementsByTagName("tbody")[0].getElementsByTagName("tr");
            let receiptData = [];

            let paymentMethod = document.getElementById("payment_id").value;
            if (paymentMethod != '1' && paymentMethod != '2') {
                Swal.fire("Error!", "Please select a valid payment method.", "error");
                return;
            }

            let totalPriceText = document.getElementById("totalDisplay").innerText;
            let totalPrice = parseFloat(totalPriceText.replace("Total Price: ₱", "").replace(",", ""));

            for (let i = 0; i < rows.length; i++) {
                let cells = rows[i].getElementsByTagName("td");

                let rowData = {
                    unit_price: parseFloat(cells[1].innerText.replace("₱", "").trim()),
                    quantity: parseInt(cells[2].innerText),
                    has_container: cells[3].innerText === 'Yes' ? 1 : 0,
                    container_quantity: parseInt(cells[4].innerText),
                    container_price: parseFloat(cells[5].innerText.replace("₱", "").trim()),
                    total_price: parseFloat(cells[6].innerText.replace("₱", "").trim()),
                    product_id: parseInt(cells[8].innerText) // hidden
                };

                receiptData.push(rowData);
            }

            if (receiptData.length === 0) {
                Swal.fire("Error!", "Your receipt is empty. Add items before checking out.", "error");
                return;
            }

            Swal.fire({
                title: "Are you sure you want to checkout?",
                text: "You won't be able to undo this action.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, Checkout!",
                cancelButtonText: "Cancel"
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch("process_receipt.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({
                            receipt: receiptData,
                            total_price: totalPrice,
                            payment_method: paymentMethod
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire("Success!", "Purchase submitted successfully!", "success")
                                .then(() => window.location.href = "orders.php");
                        } else {
                            Swal.fire("Error!", data.error, "error");
                        }
                    })
                    .catch(error => console.error("Error:", error));
                }
            });
        }

    </script>
    </body>
</html>
