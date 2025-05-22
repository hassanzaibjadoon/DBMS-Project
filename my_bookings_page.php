<?php
require_once 'config.php';
requireLogin();

// Fetch user's bookings
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT 
            b.*,
            e.event_name,
            e.event_date,
            e.event_time,
            v.venue_name,
            v.city
        FROM bookings b
        JOIN events e ON b.event_id = e.event_id
        JOIN venues v ON e.venue_id = v.venue_id
        WHERE b.user_id = ?
        ORDER BY b.booking_date DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $bookings = [];
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - PSL Events</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .booking-card {
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-radius: 15px;
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }
        .booking-card:hover {
            transform: translateY(-5px);
        }
        .booking-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 15px 15px 0 0;
        }
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5em;
        }
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: bold;
        }
        .status-confirmed {
            background-color: #28a745;
            color: white;
        }
        .status-cancelled {
            background-color: #dc3545;
            color: white;
        }
        .status-pending {
            background-color: #ffc107;
            color: black;
        }
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-cricket-bat-ball me-2"></i>PSL Events
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="my_bookings.php">My Bookings</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="my_bookings.php"><i class="fas fa-ticket-alt me-2"></i>My Bookings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-5 fw-bold mb-0">My Bookings</h1>
                    <p class="lead mb-0">Manage and view your PSL event tickets</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="index.php" class="btn btn-light btn-lg">
                        <i class="fas fa-plus me-2"></i>Book New Event
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (empty($bookings)): ?>
            <div class="empty-state">
                <i class="fas fa-ticket-alt fa-4x mb-4"></i>
                <h3>No Bookings Yet</h3>
                <p class="lead">You haven't booked any events yet. Browse our exciting PSL matches and book your tickets!</p>
                <a href="index.php" class="btn btn-primary btn-lg mt-3">
                    <i class="fas fa-search me-2"></i>Browse Events
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($bookings as $booking): ?>
                    <div class="col-lg-6">
                        <div class="card booking-card">
                            <div class="booking-header">
                                <div class="row align-items-center">
                                    <div class="col-8">
                                        <h5 class="mb-1"><?php echo htmlspecialchars($booking['event_name']); ?></h5>
                                        <small>Booking #<?php echo str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT); ?></small>
                                    </div>
                                    <div class="col-4 text-end">
                                        <span class="status-badge status-<?php echo $booking['booking_status']; ?>">
                                            <?php echo ucfirst($booking['booking_status']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <small class="text-muted">Event Date</small>
                                        <div class="fw-bold">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?php echo date('M d, Y', strtotime($booking['event_date'])); ?>
                                        </div>
                                        <div class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo date('g:i A', strtotime($booking['event_time'])); ?>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Venue</small>
                                        <div class="fw-bold">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?php echo htmlspecialchars($booking['venue_name']); ?>
                                        </div>
                                        <div class="text-muted">
                                            <?php echo htmlspecialchars($booking['city']); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <small class="text-muted">Tickets</small>
                                        <div class="fw-bold">
                                            <i class="fas fa-ticket-alt me-1"></i>
                                            <?php echo $booking['number_of_tickets']; ?> Ticket<?php echo $booking['number_of_tickets'] > 1 ? 's' : ''; ?>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Total Amount</small>
                                        <div class="fw-bold text-success">
                                            <i class="fas fa-money-bill me-1"></i>
                                            PKR <?php echo number_format($booking['total_amount'], 0); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <small class="text-muted">Booking Date</small>
                                        <div class="fw-bold">
                                            <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Payment Status</small>
                                        <div>
                                            <span class="status-badge status-<?php echo $booking['payment_status']; ?>">
                                                <?php echo ucfirst($booking['payment_status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <a href="booking_confirmation.php?booking_id=<?php echo $booking['booking_id']; ?>" 
                                       class="btn btn-outline-primary btn-sm flex-fill">
                                        <i class="fas fa-eye me-1"></i>View Details
                                    </a>
                                    <?php if ($booking['booking_status'] == 'confirmed'): ?>
                                        <button class="btn btn-outline-secondary btn-sm" 
                                                onclick="window.open('booking_confirmation.php?booking_id=<?php echo $booking['booking_id']; ?>', '_blank'); window.print();">
                                            <i class="fas fa-print me-1"></i>Print
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Booking Summary -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Booking Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-3">
                                    <div class="h4 text-primary"><?php echo count($bookings); ?></div>
                                    <small class="text-muted">Total Bookings</small>
                                </div>
                                <div class="col-md-3">
                                    <div class="h4 text-success">
                                        <?php echo count(array_filter($bookings, function($b) { return $b['booking_status'] == 'confirmed'; })); ?>
                                    </div>
                                    <small class="text-muted">Confirmed</small>
                                </div>
                                <div class="col-md-3">
                                    <div class="h4 text-info">
                                        <?php echo array_sum(array_column($bookings, 'number_of_tickets')); ?>
                                    </div>
                                    <small class="text-muted">Total Tickets</small>
                                </div>
                                <div class="col-md-3">
                                    <div class="h4 text-warning">
                                        PKR <?php echo number_format(array_sum(array_column($bookings, 'total_amount')), 0); ?>
                                    </div>
                                    <small class="text-muted">Total Spent</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-cricket-bat-ball me-2"></i>PSL Event Booking</h5>
                    <p class="text-muted">Your premier destination for PSL ticket bookings</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted">&copy; 2024 PSL Event Booking System. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
    