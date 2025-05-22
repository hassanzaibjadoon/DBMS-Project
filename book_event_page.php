<?php
require_once 'config.php';
requireLogin();

$error = '';
$success = '';

// Get event ID from URL
$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

if (!$event_id) {
    redirect('index.php');
}

// Fetch event details
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT e.*, v.venue_name, v.city, v.address 
        FROM events e 
        JOIN venues v ON e.venue_id = v.venue_id 
        WHERE e.event_id = ? AND e.status = 'active' AND e.event_date >= CURDATE()
    ");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        redirect('index.php');
    }
} catch (PDOException $e) {
    redirect('index.php');
}

// Handle booking submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $num_tickets = (int)$_POST['num_tickets'];
    
    if ($num_tickets <= 0) {
        $error = 'Please select at least 1 ticket';
    } elseif ($num_tickets > $event['available_tickets']) {
        $error = 'Not enough tickets available';
    } elseif ($num_tickets > 10) {
        $error = 'Maximum 10 tickets per booking';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Check availability again (to prevent race conditions)
            $stmt = $pdo->prepare("SELECT available_tickets FROM events WHERE event_id = ? FOR UPDATE");
            $stmt->execute([$event_id]);
            $current_availability = $stmt->fetchColumn();
            
            if ($current_availability < $num_tickets) {
                throw new Exception("Not enough tickets available");
            }
            
            // Calculate total amount
            $total_amount = $num_tickets * $event['ticket_price'];
            
            // Insert booking
            $stmt = $pdo->prepare("
                INSERT INTO bookings (user_id, event_id, number_of_tickets, total_amount, booking_status, payment_status) 
                VALUES (?, ?, ?, ?, 'confirmed', 'paid')
            ");
            $stmt->execute([$_SESSION['user_id'], $event_id, $num_tickets, $total_amount]);
            
            // Update available tickets
            $stmt = $pdo->prepare("UPDATE events SET available_tickets = available_tickets - ? WHERE event_id = ?");
            $stmt->execute([$num_tickets, $event_id]);
            
            $pdo->commit();
            
            $booking_id = $pdo->lastInsertId();
            redirect('booking_confirmation.php?booking_id=' . $booking_id);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Booking failed: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Event - <?php echo htmlspecialchars($event['event_name']); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .booking-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            padding: 30px;
            margin: 30px 0;
        }
        .event-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #f0f0f0;
            padding: 12px 15px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: none;
        }
        .price-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5em;
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
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-arrow-left me-1"></i>Back to Events
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Event Header -->
        <div class="event-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-3"><?php echo htmlspecialchars($event['event_name']); ?></h1>
                    <p class="mb-2"><i class="fas fa-calendar me-2"></i><?php echo date('l, F d, Y', strtotime($event['event_date'])); ?></p>
                    <p class="mb-2"><i class="fas fa-clock me-2"></i><?php echo date('g:i A', strtotime($event['event_time'])); ?></p>
                    <p class="mb-0"><i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($event['venue_name'] . ', ' . $event['city']); ?></p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="display-6 fw-bold">PKR <?php echo number_format($event['ticket_price'], 0); ?></div>
                    <p class="mb-0">per ticket</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="booking-container">
                    <h3 class="mb-4"><i class="fas fa-ticket-alt me-2"></i>Book Your Tickets</h3>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" id="bookingForm">
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label for="num_tickets" class="form-label">Number of Tickets</label>
                                <select class="form-select" id="num_tickets" name="num_tickets" required onchange="updateTotal()">
                                    <option value="">Select tickets</option>
                                    <?php for ($i = 1; $i <= min(10, $event['available_tickets']); $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?> Ticket<?php echo $i > 1 ? 's' : ''; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Available Tickets</label>
                                <div class="form-control-plaintext fw-bold text-success">
                                    <i class="fas fa-users me-1"></i><?php echo $event['available_tickets']; ?> remaining
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="customer_notes" class="form-label">Special Notes (Optional)</label>
                            <textarea class="form-control" id="customer_notes" name="customer_notes" rows="3" 
                                      placeholder="Any special requirements or notes..."></textarea>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-credit-card me-2"></i>Confirm Booking & Pay
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="booking-container">
                    <h4 class="mb-3"><i class="fas fa-receipt me-2"></i>Booking Summary</h4>
                    
                    <div class="price-summary">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Event:</span>
                            <span class="fw-bold"><?php echo htmlspecialchars($event['event_name']); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Date:</span>
                            <span><?php echo date('M d, Y', strtotime($event['event_date'])); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Time:</span>
                            <span><?php echo date('g:i A', strtotime($event['event_time'])); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Venue:</span>
                            <span><?php echo htmlspecialchars($event['venue_name']); ?></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Price per ticket:</span>
                            <span>PKR <?php echo number_format($event['ticket_price'], 0); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Number of tickets:</span>
                            <span id="selected_tickets">0</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">Total Amount:</span>
                            <span class="fw-bold text-primary" id="total_amount">PKR 0</span>
                        </div>
                    </div>
                    
                    <div class="mt-3 p-3 bg-light rounded">
                        <h6><i class="fas fa-info-circle me-2"></i>Important Information</h6>
                        <ul class="small mb-0">
                            <li>Tickets are non-refundable</li>
                            <li>Maximum 10 tickets per booking</li>
                            <li>Please arrive 30 minutes before the event</li>
                            <li>Valid ID required at the venue</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateTotal() {
            const numTickets = document.getElementById('num_tickets').value;
            const ticketPrice = <?php echo $event['ticket_price']; ?>;
            
            document.getElementById('selected_tickets').textContent = numTickets || '0';
            
            const total = numTickets ? (numTickets * ticketPrice) : 0;
            document.getElementById('total_amount').textContent = 'PKR ' + total.toLocaleString();
        }
    </script>
</body>
</html>