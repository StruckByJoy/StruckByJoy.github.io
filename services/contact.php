<?php
// Create this at https://www.callmebot.com/blog/free-api-signal-send-messages/
// Turn off caller id from the phone app on your Android phone to hide your phone number
$secrets = file_get_contents("../secrets.txt");
[$phoneId, $apiKey, $notifyEmail] = preg_split("/\r\n|\n|\r/", $secrets);
$supportLocalHostTesting = false;

$origin = '';
if (isset($_SERVER['HTTP_ORIGIN'])) {
	$origin = $_SERVER['HTTP_ORIGIN'];
}

$domain = apache_request_headers()['Host'];
if (0 === strpos($domain, 'services.')) {
	$domain = substr($domain, strlen('services.'));
}

// Allow from any origin
if (($supportLocalHostTesting && $origin == 'http://localhost') || $origin == "https://$domain") {
	// Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one
	// you want to allow, and if so:
	header("Access-Control-Allow-Origin: $origin");
	header("Vary: Origin");
	header('Access-Control-Allow-Credentials: true');
	header('Access-Control-Max-Age: 86400');    // cache for 1 day
	header("AMP-Access-Control-Allow-Origin: $origin");
	header("Access-Control-Expose-Headers: AMP-Access-Control-Allow-Source-Origin");	
}

// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
	if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
		// may also be using PUT, PATCH, HEAD etc
		header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
	}
	if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
		header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
	}
	exit(0);
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
	http_response_code(405);
	exit('I only support POST');
}
if (empty($_POST['name']) || empty($_POST['email']) || empty($_POST['message'])) {
	http_response_code(400);
	exit('name, email, and message POST variables are required');
}
if ($_POST['contact-location'] != '') {
	exit('Please use the contact form');
	return;
}

$name = trim($_POST['name']);
$name = preg_replace(['/\b"/','/"/',"/'/"], ['”','“',"’"], $name);

$email = trim($_POST['email']);
$email = filter_var($email, FILTER_VALIDATE_EMAIL);
if ($email === false) {
	http_response_code(400);
	exit('Invalid email');
}

$phone = trim($_POST['phone']) ;
$phone = filter_var($phone, FILTER_SANITIZE_STRING);
if ($phone !== '') {
	$phone = "Phone: $phone";
}

$message = trim($_POST['message']);
$message = preg_replace(['/\b"/','/"/',"/'/"], ['”','“',"’"], $message);

if ($notifyEmail != '') {
	mail($notifyEmail, "Webform query", trim("$phone\r\n\r\n$message"), "From: $name <$email>" . "\r\n");
}

$text = <<< EOD
Domain: $domain
Name: $name
Email: $email
$phone

$message
EOD;
$text = urlencode($text);

$url = "https://api.callmebot.com/signal/send.php?phone=$phoneId&apikey=$apiKey&text=$text";
file_get_contents($url);

header('Content-Type: application/json; charset=utf-8');
echo(json_encode(['success' => 'ok']));
?>