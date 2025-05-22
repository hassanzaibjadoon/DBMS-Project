<?php
require_once 'config.php';

// Fetch all active events with venue information
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT e.*, v.venue_name, v.city, v.address 
        FROM events e 
        JOIN venues v ON e.venue_id = v.venue_id 
        WHERE e.status = 'active' AND e.event_date >= CURDATE()
        ORDER BY e.event_date ASC, e.event_time ASC
    ");
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $events = [];
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PSL Event Booking System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 600"><rect fill="%23667eea" width="1200" height="600"/><polygon fill="%23764ba2" points="0,600 1200,300 1200,600"/></svg>');
            background-size: cover;
            color: white;
            padding: 100px 0;
            text-align: center;
        }
        .event-card {
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-radius: 15px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 30px;
        }
        .event-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        .event-image {
            height: 200px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            border-radius: 15px 15px 0 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3em;
        }
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5em;
        }
        .btn-book-now {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 25px;
            padding: 10px 25px;
            color: white;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        .btn-book-now:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: white;
        }
        .price-tag {
            background: #28a745;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
        }
        .event-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
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
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#events">Events</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
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
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt me-1"></i>Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php"><i class="fas fa-user-plus me-1"></i>Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4">Pakistan Super League 2024</h1>
            <p class="lead mb-4">Book your tickets for the most exciting cricket matches in Pakistan!</p>
            <a href="#events" class="btn btn-light btn-lg px-5">
                <i class="fas fa-ticket-alt me-2"></i>Book Tickets Now
            </a>
        </div>
    </section>

    <!-- Events Section -->
    <section id="events" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">Upcoming PSL Events</h2>
                <p class="lead text-muted">Don't miss out on the action-packed cricket matches</p>
            </div>

            <?php if (empty($events)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-calendar-times fa-4x text-muted mb-4"></i>
                    <h3>No upcoming events</h3>
                    <p class="text-muted">Check back later for new PSL matches!</p>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($events as $event): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card event-card">
                                <div class="event-image">
                                    <i class="fas fa-cricket-bat-ball"></i>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title fw-bold"><?php echo htmlspecialchars($event['event_name']); ?></h5>
                                    <p class="card-text text-muted"><?php echo htmlspecialchars($event['event_description']); ?></p>
                                    
                                    <div class="event-info">
                                        <div class="row">
                                            <div class="col-6">
                                                <small class="text-muted">Date & Time</small>
                                                <div class="fw-bold">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?php echo date('M d, Y', strtotime($event['event_date'])); ?>
                                                </div>
                                                <div class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?php echo date('g:i A', strtotime($event['event_time'])); ?>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">Venue</small>
                                                <div class="fw-bold">
                                                    <i class="fas fa-map-marker-alt me-1"></i>
                                                    <?php echo htmlspecialchars($event['venue_name']); ?>
                                                </div>
                                                <div class="text-muted">
                                                    <?php echo htmlspecialchars($event['city']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <div>
                                            <span class="price-tag">PKR <?php echo number_format($event['ticket_price'], 0); ?></span>
                                        </div>
                                        <div class="text-muted">
                                            <small><i class="fas fa-users me-1"></i><?php echo $event['available_tickets']; ?> tickets left</small>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <?php if ($event['available_tickets'] > 0): ?>
                                            <a href="book_event.php?event_id=<?php echo $event['event_id']; ?>" class="btn-book-now w-100 text-center">
                                                <i class="fas fa-ticket-alt me-2"></i>Book Now
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-secondary w-100" disabled>
                                                <i class="fas fa-exclamation-circle me-2"></i>Sold Out
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
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
