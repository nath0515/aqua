<?php 
    require 'session.php';
    require 'db.php';

    $user_id = $_SESSION['user_id'];
    if(!isset($_GET['id'])){
        header('Location: report.php');
        exit();
    }
    else{
        $report_id = $_GET['id'];
    }
    
    $role_id = $_SESSION['role_id'];
    if($role_id == 2){
        header("Location: home.php");
    }else if ($role_id == 3){
        header("Location: riderdashboard.php");
    }

    $sql = "SELECT u.user_id, username, email, role_id, firstname, lastname, address, contact_number FROM users u
    JOIN user_details ud ON u.user_id = ud.user_id
    WHERE u.user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    $sql = "SELECT a.order_id, a.report_id, b.product_id, c.product_name, d.amount, d.date FROM report_content a 
    JOIN orderitems b ON a.order_id = b.order_id
    JOIN products c ON b.product_id = c.product_id
    JOIN orders d ON a.order_id = d.order_id
    WHERE report_id = :report_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':report_id', $report_id);
    $stmt->execute();
    $order_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT SUM(b.amount) as total_amount FROM report_content a JOIN orders b ON a.order_id = b.order_id WHERE report_id = :report_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':report_id', $report_id);
    $stmt->execute();
    $total_amount_data = $stmt->fetch();
    $total_amount = $total_amount_data['total_amount'];

    $sql = "SELECT a.report_id, a.expense_id, b.date, c.expensetype_name, b.amount FROM report_expense a 
    JOIN expense b ON a.expense_id = b.expense_id 
    JOIN expensetype c ON b.expensetype_id = c.expensetype_id
    WHERE report_id = :report_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':report_id', $report_id);
    $stmt->execute();
    $expense_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT SUM(b.amount) as total_amount FROM report_expense a JOIN expense b ON a.expense_id = b.expense_id WHERE a.report_id = :report_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':report_id', $report_id);
    $stmt->execute();
    $total_expense_data = $stmt->fetch();
    $total_expense = $total_expense_data['total_amount'];

    $sql = "SELECT income FROM report_income WHERE report_id = :report_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':report_id', $report_id);
    $stmt->execute();
    $total_income = $stmt->fetchColumn();

    $sql = "SELECT date FROM reports WHERE report_id = :report_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':report_id', $report_id);
    $stmt->execute();
    $date_data = $stmt->fetchColumn();
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
                        <li><a class="dropdown-item" href="activitylogs.php">Activity Log</a></li>
                        <?php 
                        $sql = "SELECT status FROM store_status WHERE ss_id = 1";
                        $stmt = $conn->prepare($sql);
                        $stmt->execute();
                        $status = $stmt->fetchColumn();
                        ?>
                        <li>
                            <a 
                                href="process_dailyreport.php" 
                                class="dropdown-item"
                                <?php if ($status == 1): ?>
                                    onclick="return confirmCloseShop(event)"
                                <?php endif; ?>
                            >
                                <?php echo ($status == 1) ? 'Close Shop' : 'Open Shop'; ?>
                            </a>
                        </li>
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
                                    <a class="nav-link" href="stock.php">Stock</a>
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
                    <h1>💧 DoodsNer Water Refilling Station</h1>
                    <h5>📅 Daily Sales & Expense Report - <?php echo date('F j, Y', strtotime($date_data)); ?></h5>
                    <div id="reportContent">
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-table me-1"></i>
                                Sales
                            </div>
                            <div class="card-body">
                                <table id="datatablesSimple">
                                    <thead>
                                        <tr>
                                            <th> Date</th>
                                            <th> Product Name</th>
                                            <th> Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($order_data as $row):?>
                                            <tr>
                                                <td><?php echo date('F j, Y - g:iA', strtotime($row['date'])); ?></td>
                                                <td><?php echo $row['product_name'];?></td>
                                                <td>₱<?php echo $row['amount'];?></td>
                                            </tr>
                                        <?php endforeach;?>
                                        <tr>
                                            <td></td>
                                            <td></td>
                                            <td colspan="2" style="text-align: right;"><strong>Total: ₱<?php echo number_format($total_amount, 2); ?></strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-table me-1"></i>
                                Expenses
                            </div>
                            <div class="card-body">
                                <table id="datatablesSimple1">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Purpose</th>
                                            <th>Amount (₱)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($expense_data as $row):?>
                                            <tr>
                                                <td><?php echo date('F j, Y - g:iA', strtotime($row['date'])); ?></td>
                                                <td><?php echo $row['expensetype_name'];?></td>
                                                <td>₱<?php echo $row['amount'];?></td>
                                            </tr>
                                        <?php endforeach;?>
                                        <tr>
                                            <td></td>
                                            <td></td>
                                            <td colspan="2" style="text-align: right;"><strong>Total: ₱<?php echo number_format($total_expense, 2); ?></strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-table me-1"></i>
                                Income
                            </div>
                            <div class="card-body">
                                <table id="datatablesSimple2">
                                    <thead>
                                        <tr>
                                            <th>Total Sales</th>
                                            <th>Less : Expenses</th>
                                            <th>Net Income</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><?php echo number_format($total_amount, 2); ?></td>
                                            <td><?php echo number_format($total_expense, 2); ?></td>
                                            <td colspan="2" style="text-align: right;"><strong>Total: ₱<?php echo number_format($total_income, 2); ?></strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <button id="downloadPDF" class="btn btn-danger">
                            <i class="fas fa-file-pdf"></i> Download Report as PDF
                        </button>
                    </div>
                </main>    
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="js/scripts.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
        <script src="assets/demo/chart-area-demo.js"></script>
        <script src="assets/demo/chart-bar-demo.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>


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
        <script>
            window.addEventListener('DOMContentLoaded', event => {
            // Simple-DataTablesa
            // https://github.com/fiduswriter/Simple-DataTables/wikia

            const datatablesSimple = document.getElementById('datatablesSimple');
            if (datatablesSimple) {
                new simpleDatatables.DataTable(datatablesSimple);
            }
            const datatablesSimple2 = document.getElementById('datatablesSimple2');
            if (datatablesSimple2) {
                new simpleDatatables.DataTable(datatablesSimple2);
            }
            const datatablesSimple1 = document.getElementById('datatablesSimple1');
            if (datatablesSimple1) {
                new simpleDatatables.DataTable(datatablesSimple1);
            }
        });

        </script>
        <script>
            document.getElementById("downloadPDF").addEventListener("click", function () {
                const { jsPDF } = window.jspdf;

                const report = document.getElementById("reportContent");

                html2canvas(report, { scale: 2 }).then(canvas => {
                    const imgData = canvas.toDataURL('image/png');
                    const pdf = new jsPDF('p', 'mm', 'a4');

                    const pageWidth = pdf.internal.pageSize.getWidth();
                    const pageHeight = pdf.internal.pageSize.getHeight();
                    const imgWidth = pageWidth;
                    const imgHeight = canvas.height * imgWidth / canvas.width;

                    let heightLeft = imgHeight;
                    let position = 0;

                    pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                    heightLeft -= pageHeight;

                    while (heightLeft > 0) {
                        position = heightLeft - imgHeight;
                        pdf.addPage();
                        pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                        heightLeft -= pageHeight;
                    }

                    const filename = `Report_<?php echo date('Ymd', strtotime($date_data)); ?>.pdf`;
                    pdf.save(filename);
                });
            });
        </script>
    </body>
</html>
