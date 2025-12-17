<?php
/**
 * Newsletter Subscription Handler
 * File: newsletter_subscribe.php
 */

require_once 'config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean_input($_POST['email']);
    
    if (empty($email)) {
        $_SESSION['newsletter_message'] = 'Email tidak boleh kosong!';
        $_SESSION['newsletter_type'] = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['newsletter_message'] = 'Format email tidak valid!';
        $_SESSION['newsletter_type'] = 'error';
    } else {
        try {
            // Check if email already subscribed
            $stmt = $pdo->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $_SESSION['newsletter_message'] = 'Email ini sudah terdaftar di newsletter kami!';
                $_SESSION['newsletter_type'] = 'info';
            } else {
                // Insert new subscriber
                $stmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email) VALUES (?)");
                $stmt->execute([$email]);
                
                $_SESSION['newsletter_message'] = 'Terima kasih! Email Anda telah berhasil didaftarkan.';
                $_SESSION['newsletter_type'] = 'success';
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $_SESSION['newsletter_message'] = 'Terjadi kesalahan. Silakan coba lagi.';
            $_SESSION['newsletter_type'] = 'error';
        }
    }
}

// Redirect back to homepage
header('Location: index.php');
exit;
?>