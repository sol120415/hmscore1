<?php
include_once 'db.php';

if(!isset($_SESSION['email'])){
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-coreui-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- CoreUI CSS -->
    <link href="css/coreui.min.css" rel="stylesheet">
    <link href="css/coreui-grid.min.css" rel="stylesheet">
    <link href="css/coreui-reboot.min.css" rel="stylesheet">
    <link href="css/coreui-utilities.min.css" rel="stylesheet">
    <link href="css/coreui-forms.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/@coreui/icons/css/all.min.css">
    
    <!-- Theme System CSS -->
    <link href="css/theme-system.css" rel="stylesheet">
    
    <script src="js/htmx.min.js"></script>
    <script src="/js/htmx.min.js"></script>

    <title>Dashboard</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23ffffff'%3E%3Cpath d='M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z'/%3E%3C/svg%3E">
    <style>
        .main-content {
            margin-left: 80px;
            margin-right: 10px;
        }
        .sidebar-nav .nav-link {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }
    </style>
    <!-- Popper.js for popovers -->
    <script src="js/popper.min.js"></script>
    <!-- CoreUI JS -->
    <script src="js/coreui.bundle.js"></script>
    <script src="js/bootstrap.bundle.js"></script>
    
    <!-- Theme System JS -->
    <script src="js/theme-system.js"></script>
</head>
<body>

<div class="d-flex flex-column min-vh-100">
  <div class="sidebar sidebar-narrow-unfoldable border-end" >
  <ul class="sidebar-nav">
    <li class="nav-item">
      <a class="nav-link" href="?page=frontdesk">
        <i class="nav-icon cil-speedometer"></i>Front Desk
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="?page=reservations">
        <i class="nav-icon cil-calendar"></i>Reservations
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="?page=rooms">
        <i class="nav-icon cil-bed"></i>Rooms
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="?page=room_billing">
        <i class="nav-icon cil-dollar"></i>Room Billing
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="?page=events">
        <i class="nav-icon cil-calendar-check"></i>Events
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="?page=housekeeping">
        <i class="nav-icon cil-home"></i>Housekeeping
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="?page=guests">
        <i class="nav-icon cil-people"></i>Guests
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="?page=inventory">
        <i class="nav-icon cil-list"></i>Inventory  
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="?page=marketing">
        <i class="nav-icon cil-bullhorn"></i>Marketing
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="?page=channels">
        <i class="nav-icon cil-wifi-signal-4"></i>Channels
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="?page=analytics">
        <i class="nav-icon cil-chart-line"></i>Analytics
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="?page=logout" onclick="if(confirm('Are you sure you want to logout?')) { window.location.href='?page=logout'; return false; } else { return false; }">
        <i class="nav-icon cil-exit-to-app"></i>Logout
      </a>
    </li>


  </ul>
</div>
<div class="main-content flex-grow-1" id="main-content">
  <div style=" padding: 1rem;">
    <?php
    $page = $_GET['page'] ?? 'frontdesk';
    $content_file = $page . '.php';
    if (file_exists($content_file)) {
      include $content_file;
    } else {
      echo '<h1>Welcome to Dashboard</h1>';
    }
    ?>
  </div>
</div>

<footer class="mt-2bg-dark text-white text-center pt-2">
  <p>&copy; 2025 Hotel Management System. All rights reserved.</p>
</footer>

<?php
// Handle logout
if (isset($_GET['page']) && $_GET['page'] == 'logout') {
 session_destroy();
 header('Location: login.php');
 exit;
}
  ?>

  <script>
    // Highlight current page in sidebar
    document.addEventListener('DOMContentLoaded', function() {
      const currentPage = '<?php echo $page; ?>';
      const navLinks = document.querySelectorAll('.sidebar-nav .nav-link');

      navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href && href.includes('page=' + currentPage)) {
          link.classList.add('active');
        }
      });
    });
  </script>
  
  <script>
    // Initialize popover
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl)
    })
</script>


</div>
</body>
</html>