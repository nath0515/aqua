<?php 
    require 'session.php';
    require 'db.php';

    if(!isset($_GET['id'])){
        header('Location: costumerorder.php');
        exit;
    }
    else{
        $id = $_GET['id'];
    }

    $user_id = $_SESSION['user_id'];

    $sql = "SELECT u.user_id, username, email, role_id, firstname, lastname, address, contact_number FROM users u
    JOIN user_details ud ON u.user_id = ud.user_id
    WHERE u.user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    $sql = "SELECT * FROM products WHERE product_id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $product_data = $stmt->fetch();

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
            <li class="nav-item dropdown me-1">
                    <a class="nav-link position-relative" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            3
                            <span class="visually-hidden">unread messages</span>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown">
                        <li><a class="dropdown-item" href="#">Notification 1</a></li>
                        <li><a class="dropdown-item" href="#">Notification 2</a></li>
                        <li><a class="dropdown-item" href="#">Notification 3</a></li>
                    </ul>
                </li>
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
                            <a class="nav-link" href="home.php">
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
                                    <a class="nav-link" href="costumerorder.php">Order</a>
                                    <a class="nav-link" href="Orderhistory.php">Order History</a>
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
                        <div class="col-lg-6 col-md-12 mb-4">
                            <div class="card p-3 h-100">
                                <div class="text-center" style="font-size: 20px">
                                    <b><?php echo $product_data['product_name']; ?></b>
                                </div>
                                <div class="card-body bg-white text-center d-flex justify-content-center align-items-center">
                                    <img src="<?php echo $product_data['product_photo']; ?>" class="img-fluid rounded" alt="Product Image">
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-12">
                            <div class="card p-3">
                                <h5 class="mt-2 text-center">Add Order</h5>
                                <form id="purchaseForm">
                                    <div class="mb-3">
                                        <div class="card-header text-center fs-5">
                                            <?php echo $product_data['product_name']; ?>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="quantity" class="form-label">Quantity</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-bag-plus"></i></span>
                                            <input type="number" class="form-control" id="quantity" required min="1" max="15" onchange="fetchProductDetails(<?php echo $_GET['id']; ?>)">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="unitprice" class="form-label">Unit Price</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-currency-dollar"></i></span>
                                            <input type="number" class="form-control" id="unitprice" readonly>
                                        </div>
                                    </div>

                                    <div class="mb-3 row align-items-center">
                                        <div class="col-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="hasContainer" onchange="togglePriceInput()">
                                                <label class="form-check-label" for="hasContainer">Has Container</label>
                                            </div>
                                        </div>
                                        <div class="col-6 text-end" id="sameQuantityDiv" style="display: none;">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="sameQuantity" onchange="toggleSameQuantity()">
                                                <label class="form-check-label" for="sameQuantity">Same quantity as ordered</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-3" id="containerQuantity" style="display: none;">
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label">Container Quantity</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bi bi-bag-plus"></i></span>
                                                <input type="number" class="form-control" id="containerQuantityInput" value="0" onchange="updateTotalPrice()">
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label">Available Quantity</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bi bi-bag-check"></i></span>
                                                <input type="number" class="form-control" id="availablequantity" value="0" readonly>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3" id="priceContainer" style="display: none;">
                                        <label class="form-label">Container Price</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" step="0.01" id="containerprice" name="price" readonly>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="totalprice" class="form-label">Total Price</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" step="0.01" id="totalprice" name="price" readonly>
                                        </div>
                                    </div>

                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">Add To Cart</button>
                                    </div>
                                </form>
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
                        let quantity = document.getElementById('quantity').value;
                        if (quantity >= 10) {
                                document.getElementById("unitprice").value = data.data.water_price_promo;
                                document.getElementById('totalprice').value = (parseFloat(quantity) * parseFloat(data.data.water_price_promo)).toFixed(2);

                            } else {
                                document.getElementById("unitprice").value = data.data.water_price;
                                document.getElementById('totalprice').value = (parseFloat(quantity) * parseFloat(data.data.water_price)).toFixed(2);

                            }
                        document.getElementById("availablequantity").value = data.data.stock;
                        document.getElementById("containerprice").value = data.data.container_price;

                        
                    } else {
                        console.error("Product not found");
                    }
                })
                .catch(error => console.error('Error:', error));

                
            }
            
            function addToCart(){
                const unitPriceInput = document.getElementById('unitprice');
                const quantityInput = document.getElementById('quantity');
                const containerQuantityInput = document.getElementById('containerQuantityInput');
                const containerPriceInput = document.getElementById('containerprice');
                const totalPriceInput = document.getElementById('totalprice');
                const productIdInput = document.getElementById('product_id');
                const availableInput = document.getElementById('availablequantity');

                let unitprice = unitPriceInput.value;
                let quantity = quantityInput.value;
                let containerQuantity = Number(containerQuantityInput.value);
                let containerPrice = Number(containerPriceInput.value);
                let totalPrice = Number(totalPriceInput.value);
                let productId = <?php echo $_GET['id'];?>;
                let available = availableInput.value;
                let hasContainer = document.getElementById("hasContainer").checked;
                console.log(hasContainer);

                if(!productId){
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: "Please enter an item."
				    });
				    return;
                }
                else if(quantity < 1){
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: "Please enter a valid quantity."
                    });
                    return;
                }
                else if(containerQuantity < 1 && hasContainer){
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: "Please enter a valid container quantity."
                    });
                    return;
                }
                else if(containerQuantity > available){
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: "Container out of stock."
                    });
                    return;
                }
                
                fetch("process_getproductdata.php",{
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: "product_id=" + encodeURIComponent(productId)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: data.error
                            });
                            return;
                        }

                        let waterPrice = parseFloat(data.data.water_price);
                        let checkbox = document.getElementById("hasContainer");

                        let payload = {
                            product_id: productId,
                            quantity: quantity,
                            has_container: checkbox.checked ? 1 : 0,
                            container_quantity: containerQuantity
                        };

                        console.log("Sending payload:", payload);

                        let formBody = Object.keys(payload).map(key => {
                            return encodeURIComponent(key) + '=' + encodeURIComponent(payload[key]);
                        }).join('&');
                        fetch("process_addtocart.php", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/x-www-form-urlencoded"
                            },
                            body: formBody
                        })
                        .then(response => response.json())
                        .then(result => {
                            if (result.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Added to Cart!',
                                    text: result.message || 'Product successfully added to cart.',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    // Redirect after alert
                                    window.location.href = 'your_target_page.php'; // change this to your actual page
                                });

                                // Optional: Clear or reset form fields here
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: result.message || 'Something went wrong.'
                                });
                            }
                        })
                        .catch(error => {
                            console.error("Error:", error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'An error occurred while adding the item to the cart.'
                            });
                        });
                        
                    })
                    .catch(error => console.error("Error:", error));

            }


            document.getElementById("purchaseForm").addEventListener("submit", function(event) {
                event.preventDefault();
                addToCart();
            });

            function deleteRow(button) {
                const row = button.closest("tr");
                row.remove();
            }
            
        </script>
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
    </body>
</html>
