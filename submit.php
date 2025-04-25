<?php
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Handlebars\Handlebars;
use Handlebars\Loader\FilesystemLoader;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // DB Config
    $host = 'localhost';
    $db   = 'bharani_cabs_form';
    $user = 'root';
    $pass = '';
    $conn = new mysqli($host, $user, $pass, $db);

    if ($conn->connect_error) {
        header("Location: index.html?status=error");
        exit;
    }

    // Sanitize form data (ordered same as frontend)
    $customerName    = htmlspecialchars(trim($_POST["customerName"]));
    $email           = htmlspecialchars(trim($_POST["customerEmail"]));
    $contactNumber   = htmlspecialchars(trim($_POST["contactNumber"]));
    $pickupLocation  = htmlspecialchars(trim($_POST["pickupLocation"]));
    $destination     = htmlspecialchars(trim($_POST["destination"]));
    $bookingDate     = htmlspecialchars(trim($_POST["bookingDate"]));
    $bookingTime     = htmlspecialchars(trim($_POST["bookingTime"]));
    $passengers      = htmlspecialchars(trim($_POST["passengers"]));
    $vehicleType     = htmlspecialchars(trim($_POST["vehicleType"]));
    $submitted_at    = date("Y-m-d H:i:s");

    // Save to DB
    $stmt = $conn->prepare("INSERT INTO bookings (name, email, contact, pickup, destination, date, time, passengers, vehicle) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssis", $customerName, $email, $contactNumber, $pickupLocation, $destination, $bookingDate, $bookingTime, $passengers, $vehicleType);
    $stmt->execute();

    // Handlebars setup
    $loader = new FilesystemLoader(__DIR__ . '/templates', ['extension' => '.hbs']);
    $handlebars = new Handlebars(['loader' => $loader]);

    $templateData = [
        'name'         => $customerName,
        'email'        => $email,
        'contact'      => $contactNumber,
        'pickup'       => $pickupLocation,
        'destination'  => $destination,
        'date'         => $bookingDate,
        'time'         => $bookingTime,
        'passengers'   => $passengers,
        'vehicle'      => ucfirst($vehicleType),
        'submitted_at' => $submitted_at
    ];

    $userEmailBody  = $handlebars->render('user-booking', $templateData);
    $adminEmailBody = $handlebars->render('admin-booking', $templateData);

    // Send Email
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'sandbox.smtp.mailtrap.io'; // Replace with real SMTP if needed
        $mail->SMTPAuth   = true;
        $mail->Username   = '4a20e9b73fa14b';
        $mail->Password   = 'c61fbb233ba883';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 2525;

        $mail->setFrom('support@bharanicabs.in', 'Bharani Cabs');

        // To User
        $mail->setFrom('support@bharanicabs.in', 'Bharani Cabs');
        $mail->addAddress($email, $customerName); // Send to user
        $mail->Subject = 'Your Ride Booking Confirmation';
        $mail->Body    = $userEmailBody;
        $mail->isHTML(true);
        $mail->send();
        $mail->clearAddresses();

        // To Admin
        $mail->setFrom($email, $customerName); // <-- From the user
        $mail->addAddress('support@bharanicabs.in', 'Admin');
        $mail->Subject = 'New Ride Booking Received';
        $mail->Body    = $adminEmailBody;
        $mail->isHTML(true);
        $mail->send();

        header("Location: index.html?status=success");
        exit;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        header("Location: index.html?status=mailerror");
        exit;
    }

    $stmt->close();
    $conn->close();
}
