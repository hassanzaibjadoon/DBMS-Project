<?php
require_once 'config.php';
requireLogin();

$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

if (!$booking_id) {
    redirect('index.php');
}

// Fetch booking details
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT 
            b.*, 
            e.event_name, 
            e.event_date, 
            e.event_time, 
            e.ticket_price,
            v.venue_name, 
            v.city, 
            v.address,
            u.full_name,
            u.email
        FROM bookings b
        JOIN events e ON b.event_id = e.event_id
        JOIN venues v ON e.venue_id = v.venue_id
        JOIN users u ON b.user_id = u.user_id
        WHERE b.booking_id = ? AND b.user_id = ?
    ");
    $stmt->execute([$booking_id, $_SESSION['user_id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        redirect('index.php');
    }
} catch (PDOException $e) {
    redirect('index.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation - PSL Events</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .confirmation-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            padding: 40px;
            margin: 30px 0;
        }
        .success-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .success-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #28a745, #20c997);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 2rem;
        }
        .ticket-details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            border-left: 4px solid #667eea;
            margin: 20px 0;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
        }
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5em;
        }
        .booking-number {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            margin: 20px 0;
        }
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark no-print">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-cricket-bat-ball me-2"></i>PSL Events
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-home me-1"></i>Home
                </a>
                <a class="nav-link" href="my_bookings.php">
                    <i class="fas fa-ticket-alt me-1"></i>My Bookings
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="confirmation-container">
            <!-- Success Header -->
            <div class="success-header">
                <div class="success-icon">
                    <i class="fas fa-check"></i>
                </div>
                <h1 class="text-success mb-3">Booking Confirmed!</h1>
                <p class="lead">Your tickets have been successfully booked. Please save this confirmation for your records.</p>
            </div>

            <!-- Booking Number -->
            <div class="booking-number">
                <h4 class="mb-0">Booking Reference: #<?php echo str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT); ?></h4>
            </div>

            <!-- Ticket Details -->
            <div class="ticket-details">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="mb-3"><i class="fas fa-ticket-alt me-2"></i>Event Details</h5>
                        <div class="mb-3">
                            <strong>Event:</strong><br>
                            <?php echo htmlspecialchars($booking['event_name']); ?>
                        </div>
                        <div class="mb-3">
                            <strong>Date & Time:</strong><br>
                            <?php echo date('l, F d, Y', strtotime($booking['event_date'])); ?><br>
                            <?php echo date('g:i A', strtotime($booking['event_time'])); ?>
                        </div>
                        <div class="mb-3">
                            <strong>Venue:</strong><br>
                            <?php echo htmlspecialchars($booking['venue_name']); ?><br>
                            <?php echo htmlspecialchars($booking['city']); ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h5 class="mb-3"><i class="fas fa-user me-2"></i>Booking Details</h5>
                        <div class="mb-3">
                            <strong>Customer:</strong><br>
                            <?php echo htmlspecialchars($booking['full_name']); ?>
                        </div>
                        <div class="mb-3">
                            <strong>Email:</strong><br>
                            <?php echo htmlspecialchars($booking['email']); ?>
                        </div>
                        <div class="mb-3">
                            <strong>Booking Date:</strong><br>
                            <?php echo date('F d, Y g:i A', strtotime($booking['booking_date'])); ?>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-2">
                            <strong>Number of Tickets:</strong>
                            <span class="float-end"><?php echo $booking['number_of_tickets']; ?></span>
                        </div>
                        <div class="mb-2">
                            <strong>Price per Ticket:</strong>
                            <span class="float-end">PKR <?php echo number_format($booking['ticket_price'], 0); ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-2">
                            <strong>Booking Status:</strong>
                            <span class="float-end">
                                <span class="badge bg-success"><?php echo ucfirst($booking['booking_status']); ?></span>
                            </span>
                        </div>
                        <div class="mb-2">
                            <strong>Payment Status:</strong>
                            <span class="float-end">
                                <span class="badge bg-success"><?php echo ucfirst($booking['payment_status']); ?></span>
                            </span>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <div class="row">
                    <div class="col-md-12">
                        <h4 class="text-end">
                            <strong>Total Amount: PKR <?php echo number_format($booking['total_amount'], 0); ?></strong>
                        </h4>
                    </div>
                </div>
            </div>

            <!-- Important Information -->
            <div class="alert alert-info">
                <h6><i class="fas fa-info-circle me-2"></i>Important Information</h6>
                <ul class="mb-0">
                    <li>Please bring a valid photo ID to the venue</li>
                    <li>Arrive at least 30 minutes before the event starts</li>
                    <li>This confirmation serves as your ticket</li>
                    <li>Tickets are non-refundable and non-transferable</li>
                    <li>For any queries, please contact our support team</li>
                </ul>
            </div>

            <!-- Action Buttons -->
            <div class="text-center mt-4 no-print">
                <button onclick="window.print()" class="btn btn-outline-primary me-3">
                    <i class="fas fa-print me-2"></i>Print Confirmation
                </button>
                <a href="my_bookings.php" class="btn btn-primary me-3">
                    <i class="fas fa-ticket-alt me-2"></i>View All Bookings
                </a>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-home me-2"></i>Back to Home
                </a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5 no-print">
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