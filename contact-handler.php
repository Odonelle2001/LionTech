<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$whatsapp = trim($_POST['whatsapp'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if (empty($name) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Veuillez remplir les champs obligatoires.']);
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO messages (name, email, whatsapp, subject, message) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $email, $whatsapp, $subject, $message]);
    echo json_encode(['success' => true, 'message' => 'Message envoyé avec succès ! Nous vous répondrons sous 24h.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'envoi. Réessayez plus tard.']);
}
