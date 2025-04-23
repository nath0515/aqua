<?php 
require ('db.php');
require ('session.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Home</title>
    
    <!-- Styles -->
    <link href="css/styles.css" rel="stylesheet" />
    <link rel="manifest" href="/manifest.json"> <!-- ✅ Correct path to manifest -->

    <!-- Icons -->
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>

<body class="sb-nav-fixed">

<!-- Top Navbar -->
<nav class="sb-topnav navbar navbar-expand navbar-dark bg-primary">
    <a class="navbar-brand ps-3" href="index.html">AquaDrop</a>
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle"><i class="fas fa-bars"></i></button>     
    
    <ul class="navbar-nav ms-auto d-flex flex-row align-items-center pe-1">
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown">
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

<!-- Sidebar + Content -->
<div id="layoutSidenav">
    <div id="layoutSidenav_nav">
        <!-- Sidebar here (unchanged) -->
    </div>

    <div id="layoutSidenav_content">
        <!-- Header image -->
        <div class="text-center mt-3">
            <img src="assets/img/homepage.png" alt="Header Image" class="img-fluid" style="max-width: 100%; height: auto;">
        </div>

        <!-- Main content -->
        <main class="text-center my-4">
            <button id="installBtn" class="btn btn-primary" style="display: none;">Install AquaDrop</button>
        </main>

        <!-- Footer -->
        <footer class="py-4 bg-light mt-auto">
            <div class="container-fluid px-4">
                <div class="d-flex align-items-center justify-content-between small">
                    <div class="text-muted">Copyright &copy; AquaDrop 2023</div>
                    <div>
                        <a href="#">Privacy Policy</a> &middot;
                        <a href="#">Terms &amp; Conditions</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="js/scripts.js"></script>

<!-- PWA: Install Button Logic -->
<script>
    let deferredPrompt;

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        const installBtn = document.getElementById('installBtn');
        installBtn.style.display = 'inline-block';

        installBtn.addEventListener('click', () => {
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then(choiceResult => {
                if (choiceResult.outcome === 'accepted') {
                    console.log('✅ App installed');
                }
                deferredPrompt = null;
            });
        });
    });
</script>

<!-- PWA: Service Worker Registration -->
<script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/service-worker.js') // ✅ Root-level path
            .then(reg => console.log('✅ Service Worker registered:', reg))
            .catch(err => console.error('❌ Service Worker registration failed:', err));
    }
</script>

</body>
</html>
