<?php
include_once 'db.php';

if(!isset($_SESSION['email'])){
    header('Location: login.php');
    echo '<div class="toast align-items-center" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body">
          Please login first
        </div>
        <button type="button" class="btn-close me-2 m-auto" data-coreui-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>';
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
    
    <script src="js/htmx.min.js"></script>
    <script src="/js/htmx.min.js"></script>

    <title>Dashboard</title>
    <style>
        .main-content {
            margin-left: 40px;
            margin-right: 30px;
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
</head>
<body>

<div class="d-flex">
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
      <a class="nav-link" href="?page=logout">
        <i class="nav-icon cil-exit-to-app"></i>Logout
      </a>
    </li>


  </ul>
</div>
<div class="main-content" id="main-content">
</div>
  <?php
  $page = $_GET['page'] ?? 'frontdesk';
  $content_file = $page . '.php';
  if (file_exists($content_file)) {
    include $content_file;
  } else {
    echo '<h1>Welcome to Dashboard</h1>';
  }

  // Handle logout
 if ($_GET['page'] == 'logout') {
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