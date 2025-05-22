<?php
require_once 'config.php';

// Simple admin authentication (you can enhance this)
$admin_username = 'admin';
$admin_password = 'admin123';

if (!isset($_SESSION['admin_logged_in'])) {
    if (isset($_POST['admin_login'])) {
        if ($_POST['username'] == $admin_username && $_POST['password'] == $admin_password) {
            $_SESSION['admin_logged_in'] = true;
        } else {
            $admin_error = 'Invalid credentials';
        }
    }
    
    if (!isset($_SESSION['admin_logged_in'])) {
        // Show login form
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Admin Login</title>
            <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="bg-light">
            <div class="container">
                <div class="row justify-content-center mt-5">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h4>Admin Login</h4>
                            </div>
                            <div class="card-body">
                                <?php if (isset($admin_error)): ?>
                                    <div class="alert alert-danger"><?php echo $admin_error; ?></div>
                                <?php endif; ?>
                                <form method="POST">
                                    <div class="mb-3">
                                        <input type="text" name="username" class="form-control" placeholder="Username" required>
                                    </div>
                                    <div class="mb-3">
                                        <input type="password" name="password" class="form-control" placeholder="Password" required>
                                    </div>
                                    <button type="submit" name="admin_login" class="btn btn-primary w-100">Login</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit();
    }
}

// Admin is logged in, show dashboard
try {
    $pdo = getDBConnection();
    
    // Get statistics
    $stats = [];
    
    // Total events
    $stmt = $pdo->query("SELECT COUNT(*) FROM events");
    $stats['total_events'] = $stmt->fetchColumn();
    
    // Total bookings
    $stmt = $pdo->query("SELECT COUNT(*) FROM bookings");
    $stats['total_bookings'] = $stmt->fetchColumn();
    
    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $stats['total_users'] = $stmt->fetchColumn();
    
    // Total revenue
    $stmt = $pdo->query("SELECT SUM(total_amount) FROM bookings WHERE payment_status = 'paid'");
    $stats['total_revenue'] = $stmt->fetchColumn() ?: 0;
    
    // Recent bookings
    $stmt = $pdo->query("
        SELECT b.*, e.event_name, u.full_name, u.email
        FROM bookings b
        JOIN events e ON b.event_id = e.event_id
        JOIN users u ON b.user_id = u.user_id
        ORDER BY b.booking_date DESC
        LIMIT 10
    ");
    $recent_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // All events
    $stmt = $pdo->query("
        SELECT e.*, v.venue_name, v.city,
               (SELECT COUNT(*) FROM bookings WHERE event_id = e.event_id) as total_bookings,
               (SELECT SUM(number_of_tickets) FROM bookings WHERE event_id = e.event_id) as tickets_sold
        FROM events e
        JOIN venues v ON e.venue_id = v.venue_id
        ORDER BY e.event_date DESC
    ");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Handle admin logout
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged_in']);
    redirect('admin.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PSL Events</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .stats-card {
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-radius: 10px;
            transition: transform 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .sidebar {
            min-height: 100vh;
            background: #f8f9fa;
        }
        .main-content {
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-3">
                <h4 class="text-primary">Admin Panel</h4>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="#dashboard" onclick="showSection('dashboard')">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#events" onclick="showSection('events')">
                            <i class="fas fa-calendar me-2"></i>Events
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#bookings" onclick="showSection('bookings')">
                            <i class="fas fa-ticket-alt me-2"></i>Bookings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="?logout=1">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <!-- Dashboard Section -->
                <div id="dashboard" class="section">
                    <h2 class="mb-4">Dashboard Overview</h2>
                    
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card stats-card text-center p-3">
                                <div class="card-body">
                                    <i class="fas fa-calendar fa-2x text-primary mb-2"></i>
                                    <h4><?php echo $stats['total_events']; ?></h4>
                                    <p class="text-muted">Total Events</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card text-center p-3">
                                <div class="card-body">
                                    <i class="fas fa-ticket-alt fa-2x text-success mb-2"></i>
                                    <h4><?php echo $stats['total_bookings']; ?></h4>
                                    <p class="text-muted">Total Bookings</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card text-center p-3">
                                <div class="card-body">
                                    <i class="fas fa-users fa-2x text-warning mb-2"></i>
                                    <h4><?php echo $stats['total_users']; ?></h4>
                                    <p class="text-muted">Total Users</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card text-center p-3">
                                <div class="card-body">
                                    <i class="fas fa-money-bill fa-2x text-info mb-2"></i>
                                    <h4>PKR <?php echo number_format($stats['total_revenue'], 0); ?></h4>
                                    <p class="text-muted">Total Revenue</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Bookings -->
                    <div class="card">
                        <div class="card-header">
                            <h5>Recent Bookings</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Booking ID</th>
                                            <th>Customer</th>
                                            <th>Event</th>
                                            <th>Tickets</th>
                                            <th>Amount</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_bookings as $booking): ?>
                                            <tr>
                                                <td>#<?php echo str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT); ?></td>
                                                <td><?php echo htmlspecialchars($booking['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($booking['event_name']); ?></td>
                                                <td><?php echo $booking['number_of_tickets']; ?></td>
                                                <td>PKR <?php echo number_format($booking['total_amount'], 0); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Events Section -->
                <div id="events" class="section" style="display: none;">
                    <h2 class="mb-4">Event Management</h2>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Event Name</th>
                                    <th>Date</th>
                                    <th>Venue</th>
                                    <th>Price</th>
                                    <th>Available</th>
                                    <th>Sold</th>
                                    <th>Revenue</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($events as $event): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($event['event_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($event['event_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($event['venue_name'] . ', ' . $event['city']); ?></td>
                                        <td>PKR <?php echo number_format($event['ticket_price'], 0); ?></td>
                                        <td><?php echo $event['available_tickets']; ?></td>
                                        <td><?php echo $event['tickets_sold'] ?: 0; ?></td>
                                        <td>PKR <?php echo number_format(($event['tickets_sold'] ?: 0) * $event['ticket_price'], 0); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $event['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($event['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Bookings Section -->
                <div id="bookings" class="section" style="display: none;">
                    <h2 class="mb-4">All Bookings</h2>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Booking ID</th>
                                    <th>Customer</th>
                                    <th>Email</th>
                                    <th>Event</th>
                                    <th>Tickets</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_bookings as $booking): ?>
                                    <tr>
                                        <td>#<?php echo str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT); ?></td>
                                        <td><?php echo htmlspecialchars($booking['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['email']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['event_name']); ?></td>
                                        <td><?php echo $booking['number_of_tickets']; ?></td>
                                        <td>PKR <?php echo number_format($booking['total_amount'], 0); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $booking['booking_status'] == 'confirmed' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($booking['booking_status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y g:i A', strtotime($booking['booking_date'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.section').forEach(section => {
                section.style.display = 'none';
            });
            
            // Show selected section
            document.getElementById(sectionId).style.display = 'block';
            
            // Update active nav link
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            event.target.classList.add('active');
        }
    </script>
</body>
</html>