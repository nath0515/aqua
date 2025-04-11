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
                        <li><a class="dropdown-item" href="#!">Settings</a></li>
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
                                    <a class="nav-link" href="#">Orders</a>
                                    <a class="nav-link" href="#">Sales</a>
                                    <a class="nav-link" href="#">Expenses</a>
                                    <a class="nav-link" href="#">Stock</a>
                                </nav>
                            </div>
                            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapsePages" aria-expanded="false" aria-controls="collapsePages">
                                <div class="sb-nav-link-icon"><i class="fas fa-book-open"></i></div>
                                Analytics
                                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                            </a>
                            <div class="collapse" id="collapsePages" aria-labelledby="headingTwo" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link" href="#">Stock</a>
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
                        <div class="col-lg-6 col-md-12" >
                            <div class="card p-3">
                                <h5 class="mt-2">Add Order</h5>
                                <form id="purchaseForm" action="">
                                    <div class="mb-3">
										<label for="product_name" class="form-label">Item</label>
										<select name="" id="product_id" class="form-select" required>
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
                                                <input type="number" class="form-control" id="quantity" required min="1" max="15" required>
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
                                                <input class="form-check-input" type="checkbox" id="sameQUantity" onchange="toggleSameQuantity()">
                                                <label class="form-check-label" for="hasContainer">
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
                                                <input type="number" class="form-control" id="containerQuantityInput" required>
                                            </div>
                                        </div>
                                        <div class="col mt-2">
                                            <label for="price" class="form-label">Available Container : 69</label>
                                        </div>    
                                    </div>
                                    <div class="mb-3" id="priceContainer" style="display: none;">
                                        <label for="price" class="form-label">Container Price</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" step="0.01" id="price" name="price" readonly>
                                        </div>
                                    </div>
                                    <script>
                                        function togglePriceInput() {
                                            const checkbox = document.getElementById('hasContainer');
                                            const sameQuantityDiv = document.getElementById('sameQuantityDiv');
                                            const containerQuantity = document.getElementById('containerQuantity');
                                            const priceContainer = document.getElementById('priceContainer');
                                            priceContainer.style.display = checkbox.checked ? 'block' : 'none';
                                            containerQuantity.style.display = checkbox.checked ? 'block' : 'none';
                                            sameQuantityDiv.style.display = checkbox.checked ? 'block' : 'none';
                                        }
                                        function toggleSameQuantity() {
                                            document.getElementById("containerQuantityInput").readOnly = true;
                                        }
                                    </script>
                                    <div class="mb-3">
										<label for="price" class="form-label">Total Price</label>
										<div class="input-group">
											<span class="input-group-text">₱</span>
											<input type="number" class="form-control" step=0.01 id="price" name="price" readonly>
										</div>
									</div>
    								<div class="d-flex justify-content-end">
										<button type="submit" class="btn btn-primary mb-2">Submit</button>
									</div>
                                </form>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card p-3">
								<div class="row">
									<div class="col-6">
										<button class="btn btn-primary mb-2" onclick="submitReceipt()">Checkout</button>
									</div>
									<div class="col-6">
										<h5 class="text-end" id="totalPrice">Total Price: ₱0.00</h5>
									</div>
									
									
								</div>
                                <h5 class="mt-2 text-center fw-bold">AquaDrop</h5>
								<div class="table-responsive">
                                <table class="table table-bordered" id="receipt">
                                    <thead>
                                        <tr>
                                            <th>Item Name</th>
                                            <th>Price</th>
                                            <th>Quantity</th>
                                            <th>Has Container</th>
                                            <th>Container Price</th>
                                            <th>Total Price</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        
                                    </tbody>
                                </table>
                            </div>
							<div class="col-md-4 mt-3">
								<div class="form-group form-group-default">
									<label>Payment Method</label>
									<select class="form-select" name="pm_id" required>
										<option>Choose Payment Method</option>
										<?php foreach ($data3 as $row):?>
											<option value="<?php echo $row['pm_id']?>"><?php echo $row['payment_method']?></option>
										<?php endforeach; ?>
									</select>
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

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
            $(document).ready(function() {
                $("#editOrderBtn").click(function() {
                    var orderId = $(this).data("id");

                    $.ajax({
                        url: "process_getorderdata.php",
                        type: "POST",
                        data: { order_id: orderId },
                        dataType: "json",
                        success: function(response) {
                            if (response.success) {
                                const orderItems = response.data2;

                                $("#editStatusId").val(response.data.status_id);


                                let itemsHtml = '<h5>Order Items:</h5>';
                                orderItems.forEach(item => {
                                    itemsHtml += `
                                        <div>
                                            <p>Item: ${item.product_name}</p>
                                            <p>Quantity: ${item.quantity}</p>
                                            <p>Price: ₱${item.price}</p>
                                        </div>
                                    `;
                                });
                                $('#orderItemsContainer').html(itemsHtml);
                                
                            } else {
                                alert("Error fetching product data.");
                            }
                        },
                        error: function() {
                            alert("Failed to fetch product details.");
                        }
                    });
                });
            });
        </script>
    </body>
</html>
