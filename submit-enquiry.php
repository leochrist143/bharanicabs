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
    $customerName   = htmlspecialchars(trim($_POST["name"]));
    $contactNumber  = htmlspecialchars(trim($_POST["phone"]));
    $email          = htmlspecialchars(trim($_POST["enq_email"]));
    $service        = htmlspecialchars(trim($_POST["service"]));
    $tripDetails    = htmlspecialchars(trim($_POST["message"]));
    $submitted_at   = date("Y-m-d H:i:s");

    // Save to DB
    $stmt = $conn->prepare("INSERT INTO enquiries (name, phone, email, service, trip_details, submitted_at) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $customerName, $contactNumber, $email, $service, $tripDetails, $submitted_at);
    $stmt->execute();

    // Handlebars setup
    $loader = new FilesystemLoader(__DIR__ . '/templates', ['extension' => '.hbs']);
    $handlebars = new Handlebars(['loader' => $loader]);

    $templateData = [
        'name'        => $customerName,
        'email'       => $email,
        'contact'     => $contactNumber,
        'service'     => $service,
        'tripDetails' => nl2br($tripDetails),  // preserve line breaks
        'submitted_at' => $submitted_at
    ];

    $userEmailBody  = $handlebars->render('user-enquiry', $templateData);
    $adminEmailBody = $handlebars->render('admin-enquiry', $templateData);

    // Send Email
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'sandbox.smtp.mailtrap.io';
        $mail->SMTPAuth   = true;
        $mail->Username   = '4a20e9b73fa14b';
        $mail->Password   = 'c61fbb233ba883';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 2525;

        $mail->setFrom('support@bharanicabs.in', 'Bharani Cabs');

        // To User
        $mail->addAddress($email, $customerName);
        $mail->Subject = 'Your Enquiry Received - Bharani Cabs';
        $mail->Body    = $userEmailBody;
        $mail->isHTML(true);
        $mail->send();
        $mail->clearAddresses();

        // To Admin
        $mail->setFrom($email, $customerName);
        $mail->addAddress('support@bharanicabs.in', 'Admin');
        $mail->Subject = 'New Enquiry Received';
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
