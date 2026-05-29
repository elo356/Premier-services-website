<?php
header('Content-Type: application/json');
$recipient_email = '3li.35426@gmail.com';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = isset($_POST['formType']) ? $_POST['formType'] : 'contact';

    if ($type === 'contact') {
        $firstName = sanitize($_POST['firstName'] ?? '');
        $lastName = sanitize($_POST['lastName'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $service = sanitize($_POST['service'] ?? '');
        $projectType = sanitize($_POST['projectType'] ?? '');
        $message = sanitize($_POST['message'] ?? '');

        $subject = "New Contact Form Submission from $firstName $lastName";
        $body = "
Name: $firstName $lastName
Email: $email
Phone: $phone
Service of Interest: $service
Project Type: $projectType

Message:
$message
        ";

        if (send_email($recipient_email, $subject, $body)) {
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to send message']);
        }
    }
    elseif ($type === 'subcontractor') {
        $companyName = sanitize($_POST['companyName'] ?? '');
        $contactPerson = sanitize($_POST['contactPerson'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $services = sanitize($_POST['services'] ?? '');
        $experience = sanitize($_POST['experience'] ?? '');
        $message = sanitize($_POST['message'] ?? '');

        $subject = "New Subcontractor Application from $companyName";
        $body = "
Company Name: $companyName
Contact Person: $contactPerson
Email: $email
Phone: $phone
Services: $services
Years of Experience: $experience

Message:
$message
        ";

        // Handle file uploads
        $file_paths = [];
        if (isset($_FILES['attachments'])) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            foreach ($_FILES['attachments']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_name = basename($_FILES['attachments']['name'][$key]);
                    $file_name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file_name);
                    $timestamp = time();
                    $file_name = $timestamp . '_' . $file_name;
                    $file_path = $upload_dir . $file_name;

                    if (move_uploaded_file($tmp_name, $file_path)) {
                        $file_paths[] = $file_path;
                    }
                }
            }
        }

        if (send_email_with_attachments($recipient_email, $subject, $body, $file_paths)) {
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Application sent successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to send application']);
        }
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

function sanitize($input) {
    return htmlspecialchars(strip_tags($input));
}

function send_email($to, $subject, $body) {
    $headers = "From: noreply@cardonapropertygroup.com\r\n";
    $headers .= "Reply-To: noreply@cardonapropertygroup.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    return mail($to, $subject, $body, $headers);
}

function send_email_with_attachments($to, $subject, $body, $attachments = []) {
    $boundary = md5(time());
    $headers = "From: noreply@cardonapropertygroup.com\r\n";
    $headers .= "Reply-To: noreply@cardonapropertygroup.com\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";

    $message = "--$boundary\r\n";
    $message .= "Content-Type: text/plain; charset=\"UTF-8\"\r\n";
    $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $message .= $body . "\r\n";

    foreach ($attachments as $file_path) {
        if (file_exists($file_path)) {
            $file_name = basename($file_path);
            $file_content = file_get_contents($file_path);
            $file_content = chunk_split(base64_encode($file_content));

            $message .= "--$boundary\r\n";
            $message .= "Content-Type: application/octet-stream; name=\"$file_name\"\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n";
            $message .= "Content-Disposition: attachment; filename=\"$file_name\"\r\n\r\n";
            $message .= $file_content . "\r\n";
        }
    }

    $message .= "--$boundary--";

    return mail($to, $subject, $message, $headers);
}
?>
