<?php
/**
 * AI Chatbot Assistant API
 * Uses Groq API (FREE) with PHP fallback
 * Features: Context-aware suggestions + Auto-booking capability
 */

session_start();
header('Content-Type: application/json');

// Load environment variables
require_once __DIR__ . '/config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
loadEnv(__DIR__ . '/.env');

$pdo = require __DIR__ . '/config/db.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $message = trim($input['message'] ?? '');
    $conversationHistory = $input['history'] ?? [];

    if (empty($message)) {
        throw new Exception('Message cannot be empty');
    }

    // Get user ID if logged in (for context and auto-booking)
    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
    
    // Make $pdo available to helper functions
    global $pdo;
    
    // Get office information from database (with office_id for booking)
    $officesStmt = $pdo->query("
        SELECT office_id, office_name, location, description 
        FROM public.offices 
        ORDER BY office_name
    ");
    $offices = $officesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get user's appointment history for context (if logged in)
    $userAppointments = [];
    if ($user_id > 0) {
        try {
            $apptStmt = $pdo->prepare("
                SELECT 
                    a.appointment_id,
                    a.appointment_date,
                    a.appointment_time,
                    a.concern,
                    a.status,
                    o.office_name,
                    o.office_id
                FROM appointments a
                INNER JOIN appointment_offices ao ON a.appointment_id = ao.appointment_id
                INNER JOIN offices o ON ao.office_id = o.office_id
                WHERE a.user_id = ?
                ORDER BY a.appointment_date DESC, a.appointment_time DESC
                LIMIT 5
            ");
            $apptStmt->execute([$user_id]);
            $userAppointments = $apptStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Ignore errors, just continue without context
        }
    }
    
    // Check if this is a booking request
    $isBookingRequest = isBookingRequest($message);
    
    if ($isBookingRequest && $user_id > 0) {
        // Handle auto-booking
        $bookingResult = handleAutoBooking($pdo, $message, $offices, $user_id);
        if ($bookingResult['success']) {
            echo json_encode($bookingResult);
            exit;
        }
        // If booking failed, continue with normal response
    }
    
    // Get Groq API key from environment (optional - works without it using PHP fallback)
    $groqApiKey = getenv('GROQ_API_KEY');
    $useGroq = !empty($groqApiKey) && getenv('USE_PHP_FALLBACK') !== 'true';

    // Try Groq API first if available, otherwise use PHP fallback
    if ($useGroq) {
        try {
            $aiResponse = callGroqAPI($groqApiKey, $message, $conversationHistory, $offices, $userAppointments);
            echo json_encode([
                'success' => true,
                'response' => $aiResponse,
                'source' => 'groq',
                'has_context' => !empty($userAppointments)
            ]);
            exit;
        } catch (Exception $e) {
            // If Groq fails, fall back to PHP
            error_log('Groq API error: ' . $e->getMessage());
            $aiResponse = generatePHPResponse($message, $offices, $userAppointments);
            echo json_encode([
                'success' => true,
                'response' => $aiResponse,
                'source' => 'php_fallback',
                'has_context' => !empty($userAppointments)
            ]);
            exit;
        }
    } else {
        // Use PHP fallback directly (works without any API key!)
        $aiResponse = generatePHPResponse($message, $offices, $userAppointments);
        echo json_encode([
            'success' => true,
            'response' => $aiResponse,
            'source' => 'php',
            'has_context' => !empty($userAppointments)
        ]);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Create appointment directly (without HTTP call)
 */
function createAppointmentDirectly($pdo, $user_id, $bookingData) {
    $office_id = (int)$bookingData['office_id'];
    $appointment_date = trim($bookingData['appointment_date']);
    $appointment_time_raw = trim($bookingData['appointment_time']);
    $concern = trim($bookingData['concern'] ?? 'AI-assisted booking');
    
    // Parse time to HH:MM:SS format
    $timeFormatted = '';
    if (preg_match('/(\d{1,2}):(\d{2})\s*(AM|PM)/i', $appointment_time_raw, $matches)) {
        $hour = intval($matches[1]);
        $minute = intval($matches[2]);
        $ampm = strtoupper($matches[3]);
        
        if ($ampm === 'PM' && $hour != 12) {
            $hour += 12;
        } elseif ($ampm === 'AM' && $hour == 12) {
            $hour = 0;
        }
        
        $timeFormatted = sprintf('%02d:%02d:00', $hour, $minute);
    } else {
        if (preg_match('/^(\d{1,2}):(\d{2})/', $appointment_time_raw, $matches)) {
            $timeFormatted = sprintf('%02d:%02d:00', intval($matches[1]), intval($matches[2]));
        } else {
            $timeFormatted = $appointment_time_raw;
        }
    }
    
    try {
        // Check if slot is blocked
        $blockedCheck = $pdo->prepare("
            SELECT COUNT(*) AS c
            FROM office_blocked_slots
            WHERE office_id = ?
              AND block_date = ?
              AND start_time <= ?::time
              AND end_time > ?::time
        ");
        $blockedCheck->execute([$office_id, $appointment_date, $timeFormatted, $timeFormatted]);
        if ((int)($blockedCheck->fetch()['c'] ?? 0) > 0) {
            return [
                'success' => false,
                'error' => 'This time slot is unavailable due to an office event'
            ];
        }
        
        // Check for duplicate appointments
        $checkStmt = $pdo->prepare("
            SELECT COUNT(*) AS c
            FROM appointments a
            INNER JOIN appointment_offices ao ON a.appointment_id = ao.appointment_id
            WHERE a.user_id = ?
              AND ao.office_id = ?
              AND a.appointment_date = ?
              AND a.appointment_time = ?
              AND LOWER(a.status::text) NOT IN ('completed', 'cancelled')
        ");
        $checkStmt->execute([$user_id, $office_id, $appointment_date, $timeFormatted]);
        if ((int)($checkStmt->fetch()['c'] ?? 0) > 0) {
            return [
                'success' => false,
                'error' => 'You already have an appointment at this time'
            ];
        }
        
        // Create appointment - use 'Pending' to match schema default
        $status = 'Pending';
        $stmt = $pdo->prepare("
            INSERT INTO appointments (user_id, appointment_date, appointment_time, concern, status)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([$user_id, $appointment_date, $timeFormatted, $concern, $status]);
        
        if (!$result) {
            throw new Exception('Failed to create appointment');
        }
        
        $appointment_id = (int)$pdo->lastInsertId('appointments_appointment_id_seq');
        
        if ($appointment_id <= 0) {
            $stmt_id = $pdo->query("SELECT lastval()");
            $appointment_id = (int)($stmt_id->fetchColumn() ?? 0);
        }
        
        if ($appointment_id <= 0) {
            throw new Exception('Failed to get appointment ID');
        }
        
        // Link to office - use 'pending' (lowercase) for appointment_offices
        $officeStatus = 'pending';
        $stmt2 = $pdo->prepare("
            INSERT INTO appointment_offices (appointment_id, office_id, status)
            VALUES (?, ?, ?)
        ");
        
        $result2 = $stmt2->execute([$appointment_id, $office_id, $officeStatus]);
        
        if (!$result2) {
            // Rollback
            $pdo->prepare("DELETE FROM appointments WHERE appointment_id = ?")->execute([$appointment_id]);
            throw new Exception('Failed to assign office');
        }
        
        // Get office name for response
        $officeStmt = $pdo->prepare("SELECT office_name FROM offices WHERE office_id = ?");
        $officeStmt->execute([$office_id]);
        $office = $officeStmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'appointment_id' => $appointment_id,
            'office_name' => $office['office_name'] ?? 'Office',
            'appointment_date' => $appointment_date,
            'appointment_time' => $appointment_time_raw,
            'message' => 'Appointment booked successfully!'
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Detect if message is a booking request
 */
function isBookingRequest($message) {
    $lower = strtolower($message);
    $bookingKeywords = [
        'book', 'schedule', 'appointment', 'reserve', 'book me', 'i want to book',
        'can you book', 'please book', 'book an appointment', 'set up appointment',
        'mag-book', 'gusto ko mag-book', 'pwedeng mag-book'
    ];
    
    foreach ($bookingKeywords as $keyword) {
        if (strpos($lower, $keyword) !== false) {
            return true;
        }
    }
    return false;
}

/**
 * Handle auto-booking request
 */
function handleAutoBooking($pdo, $message, $offices, $user_id) {
    // Extract office name from message
    $officeName = extractOfficeName($message, $offices);
    if (!$officeName) {
        return [
            'success' => false,
            'error' => 'Please specify which office you want to book with.'
        ];
    }
    
    // Find office ID
    $officeId = null;
    foreach ($offices as $office) {
        if (stripos($office['office_name'], $officeName) !== false) {
            $officeId = (int)$office['office_id'];
            $officeName = $office['office_name'];
            break;
        }
    }
    
    if (!$officeId) {
        return [
            'success' => false,
            'error' => 'Office not found. Please specify a valid office name.'
        ];
    }
    
    // Extract date preference (default to tomorrow if not specified)
    $preferredDate = extractDate($message);
    if (!$preferredDate) {
        // Default to tomorrow
        $tomorrow = new DateTime('tomorrow');
        $preferredDate = $tomorrow->format('Y-m-d');
    }
    
    // Get available slots for the office and date
    // Use direct file inclusion for internal calls
    $_GET['office_id'] = $officeId;
    $_GET['date'] = $preferredDate;
    ob_start();
    include __DIR__ . '/get_available_slots_ai.php';
    $slotsResponse = ob_get_clean();
    $httpCode = 200; // Assume success for internal include
    
    if ($httpCode !== 200) {
        return [
            'success' => false,
            'error' => 'Could not check available slots. Please try booking manually.'
        ];
    }
    
    $slotsData = json_decode($slotsResponse, true);
    if (!$slotsData['success'] || empty($slotsData['available_slots'])) {
        // Try next day
        $nextDate = new DateTime($preferredDate);
        $nextDate->modify('+1 day');
        $nextDateStr = $nextDate->format('Y-m-d');
        
        // Try next day
        $_GET['office_id'] = $officeId;
        $_GET['date'] = $nextDateStr;
        ob_start();
        include __DIR__ . '/get_available_slots_ai.php';
        $slotsResponse = ob_get_clean();
        $httpCode = 200;
        
        $slotsData = json_decode($slotsResponse, true);
        if (!$slotsData['success'] || empty($slotsData['available_slots'])) {
            return [
                'success' => false,
                'error' => "No available slots found for {$officeName}. Please try a different date or book manually."
            ];
        }
        $preferredDate = $nextDateStr;
    }
    
    // Get the first available slot (best available time)
    $bestSlot = $slotsData['available_slots'][0];
    $appointmentTime = $bestSlot['time_formatted'];
    
    // Extract concern from message or use default
    $concern = extractConcern($message) ?: "AI-assisted booking";
    
    // Book the appointment
    $bookingData = [
        'office_id' => $officeId,
        'appointment_date' => $preferredDate,
        'appointment_time' => $appointmentTime,
        'concern' => $concern
    ];
    
    // Call booking function directly (refactored approach)
    // Pass $pdo as parameter since it's needed
    $bookingResult = createAppointmentDirectly($pdo, $user_id, $bookingData);
    
    if ($bookingResult['success']) {
        $dateFormatted = date('F j, Y', strtotime($preferredDate));
        return [
            'success' => true,
            'response' => "✅ **Appointment booked successfully!**\n\n" .
                         "**Office:** {$officeName}\n" .
                         "**Date:** {$dateFormatted}\n" .
                         "**Time:** {$appointmentTime}\n" .
                         "**Concern:** {$concern}\n\n" .
                         "Your appointment is now pending approval. You'll receive updates on the status.",
            'booking' => $bookingResult,
            'source' => 'auto_booking'
        ];
    } else {
        return [
            'success' => false,
            'error' => $bookingResult['error'] ?? 'Failed to book appointment. Please try manually.'
        ];
    }
}

/**
 * Extract office name from message
 */
function extractOfficeName($message, $offices) {
    $lower = strtolower($message);
    
    // Check for office keywords
    $officeKeywords = [
        'registrar' => ['registrar', 'transcript', 'diploma', 'certificate', 'records'],
        'cashier' => ['cashier', 'payment', 'tuition', 'pay', 'financial'],
        'guidance' => ['guidance', 'counseling', 'counselor'],
        'library' => ['library', 'book', 'borrow'],
        'clinic' => ['clinic', 'health', 'medical', 'doctor']
    ];
    
    foreach ($officeKeywords as $officeType => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($lower, $keyword) !== false) {
                // Find matching office
                foreach ($offices as $office) {
                    $officeNameLower = strtolower($office['office_name']);
                    if (strpos($officeNameLower, $officeType) !== false) {
                        return $office['office_name'];
                    }
                }
            }
        }
    }
    
    // Try direct office name match
    foreach ($offices as $office) {
        if (stripos($message, $office['office_name']) !== false) {
            return $office['office_name'];
        }
    }
    
    return null;
}

/**
 * Extract date preference from message
 */
function extractDate($message) {
    $lower = strtolower($message);
    
    // Check for specific dates
    if (preg_match('/(\d{1,2})\/(\d{1,2})\/(\d{4})/', $message, $matches)) {
        $month = $matches[1];
        $day = $matches[2];
        $year = $matches[3];
        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }
    
    // Check for relative dates
    if (strpos($lower, 'today') !== false) {
        return date('Y-m-d');
    }
    if (strpos($lower, 'tomorrow') !== false) {
        $tomorrow = new DateTime('tomorrow');
        return $tomorrow->format('Y-m-d');
    }
    if (preg_match('/(\d+)\s*(day|days)\s*(from now|later)/', $lower, $matches)) {
        $days = (int)$matches[1];
        $date = new DateTime("+{$days} days");
        return $date->format('Y-m-d');
    }
    
    return null;
}

/**
 * Extract concern from message
 */
function extractConcern($message) {
    // Remove booking keywords
    $concern = preg_replace('/\b(book|schedule|appointment|reserve|for|with|at|on)\b/i', '', $message);
    $concern = trim($concern);
    
    // If concern is too short or just office name, return null
    if (strlen($concern) < 10) {
        return null;
    }
    
    return $concern;
}

/**
 * Call Groq API (FREE - No payment required!)
 * Get your free API key at: https://console.groq.com/
 */
function callGroqAPI($apiKey, $message, $conversationHistory, $offices, $userAppointments = []) {
    // Build system prompt with office information
    $officeList = array_map(function($office) {
        $info = $office['office_name'];
        if (!empty($office['location'])) {
            $info .= " (Location: {$office['location']})";
        }
        if (!empty($office['description'])) {
            $info .= " - {$office['description']}";
        }
        return $info;
    }, $offices);
    
    $contextInfo = '';
    if (!empty($userAppointments)) {
        $contextInfo = "\n\nUser's Recent Appointments:\n";
        foreach (array_slice($userAppointments, 0, 3) as $apt) {
            $contextInfo .= "- {$apt['office_name']} on {$apt['appointment_date']} at {$apt['appointment_time']} (Status: {$apt['status']})\n";
        }
        $contextInfo .= "\nUse this context to provide personalized suggestions. For example, if they've booked with Registrar's Office before, you can reference that.";
    }

    $systemPrompt = "You are a helpful AI assistant for Cor Jesu College's appointment booking system. Your role is to help students and faculty book appointments with the correct office.

Available Offices:
" . implode("\n", $officeList) . $contextInfo . "

Guidelines:
- Help users understand which office they need for their concern
- Provide clear, friendly, and concise answers
- If asked about appointment booking, guide them through the process OR offer to book for them
- If asked about office hours, mention that appointments are typically available from 9:00 AM to 4:00 PM
- Use context from user's past appointments to provide personalized suggestions
- If user has previous appointments, reference them naturally (e.g., 'I see you've booked with Registrar's Office before...')
- Be helpful but encourage users to book appointments for specific requests
- Keep responses brief and actionable
- If you don't know something, admit it and suggest contacting the office directly
- Respond in a friendly, conversational manner
- You can offer to book appointments automatically if the user requests it

Current date: " . date('F j, Y') . "
Current time: " . date('g:i A') . "

Remember: You're here to help users navigate the appointment system efficiently.";

    // Build conversation messages
    $messages = [
        ['role' => 'system', 'content' => $systemPrompt]
    ];

    // Add conversation history (last 10 messages to avoid token limits)
    $recentHistory = array_slice($conversationHistory, -10);
    foreach ($recentHistory as $msg) {
        $messages[] = [
            'role' => $msg['role'] ?? 'user',
            'content' => $msg['content'] ?? ''
        ];
    }

    // Add current message
    $messages[] = ['role' => 'user', 'content' => $message];

    // Call Groq API (FREE tier - very fast!)
    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'model' => 'llama-3.1-8b-instant', // Fast and free model
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 500
        ])
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        throw new Exception('API request failed: ' . $curlError);
    }

    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        $errorMsg = $errorData['error']['message'] ?? 'Unknown API error';
        throw new Exception('Groq API error: ' . $errorMsg);
    }

    $data = json_decode($response, true);
    
    if (!isset($data['choices'][0]['message']['content'])) {
        throw new Exception('Invalid response from AI service');
    }

    return trim($data['choices'][0]['message']['content']);
}

/**
 * Generate PHP-based intelligent response (FREE - No API needed!)
 * This works even without any API key
 * Now includes context-aware suggestions!
 */
function generatePHPResponse($message, $offices, $userAppointments = []) {
    $lowerMessage = strtolower($message);
    $response = '';

    // Context-aware greeting
    if (preg_match('/\b(hi|hello|hey|halo|kamusta|good morning|good afternoon|good evening|mabuhay)\b/i', $message)) {
        $response = "Hello! 👋 I'm your AI assistant for Cor Jesu College's appointment booking system.\n\n";
        
        // Add context if user has appointments
        if (!empty($userAppointments)) {
            $recentApt = $userAppointments[0];
            $response .= "I see you have an upcoming appointment with **{$recentApt['office_name']}** on {$recentApt['appointment_date_formatted']} at {$recentApt['appointment_time_formatted']}.\n\n";
        }
        
        $response .= "I can help you:\n";
        $response .= "• Find the right office for your concern\n";
        $response .= "• Understand the appointment booking process\n";
        $response .= "• **Book appointments automatically for you**\n";
        $response .= "• Answer questions about office services\n\n";
        $response .= "What can I help you with today?";
    }
    // Check appointment status
    elseif (preg_match('/\b(status|check|my appointment|upcoming|pending|approved)\b/i', $message)) {
        if (!empty($userAppointments)) {
            $response = "Here are your recent appointments:\n\n";
            foreach (array_slice($userAppointments, 0, 5) as $apt) {
                $statusEmoji = $apt['status'] === 'approved' ? '✅' : ($apt['status'] === 'pending' ? '⏳' : '📋');
                $response .= "{$statusEmoji} **{$apt['office_name']}**\n";
                $response .= "   Date: {$apt['appointment_date']}\n";
                $response .= "   Time: {$apt['appointment_time']}\n";
                $response .= "   Status: {$apt['status']}\n\n";
            }
            $response .= "You can track all your appointments in the dashboard!";
        } else {
            $response = "You don't have any appointments yet. Would you like me to help you book one?";
        }
    }
    // Office-specific queries - Transcripts/Records
    elseif (preg_match('/\b(transcript|diploma|certificate|records|grades|tog|tor|form 137|form 138)\b/i', $message)) {
        $office = findOffice($offices, ['registrar', 'record', 'transcript']);
        if ($office) {
            $response = "For **transcripts, diplomas, certificates, and student records**, you should book with the **{$office['office_name']}**.\n\n";
            
            // Context: Check if user has booked with this office before
            $hasBookedBefore = false;
            foreach ($userAppointments as $apt) {
                if (stripos($apt['office_name'], 'registrar') !== false) {
                    $hasBookedBefore = true;
                    $response .= "I see you've booked with {$apt['office_name']} before! ";
                    break;
                }
            }
            
            if (!empty($office['description'])) {
                $response .= "They handle: {$office['description']}.\n\n";
            }
            $response .= "**Would you like me to book an appointment for you?** Just say 'book me with Registrar' or 'schedule an appointment' and I'll find the best available time!";
        } else {
            $response = "For transcripts, diplomas, certificates, and student records, you'll need to contact the **Registrar's Office**.\n\n";
            $response .= "**I can book this for you!** Just say 'book me with Registrar' and I'll schedule it automatically.";
        }
    }
    // Payment/Tuition
    elseif (preg_match('/\b(payment|pay|tuition|fee|financial|money|cash|installment|bayad)\b/i', $message)) {
        $office = findOffice($offices, ['cashier', 'accounting', 'finance', 'payment', 'tuition']);
        if ($office) {
            $response = "For **payment, tuition, and financial matters**, you should book with the **{$office['office_name']}**.\n\n";
            if (!empty($office['description'])) {
                $response .= "They handle: {$office['description']}.\n\n";
            }
            $response .= "**Would you like me to book an appointment for you?** Just say 'book me with {$office['office_name']}' and I'll find the best available time!";
        } else {
            $response = "For payment and tuition concerns, you'll need to contact the **Cashier's Office** or **Accounting Office**.\n\n";
            $response .= "**I can book this for you!** Just tell me which office and I'll schedule it automatically.";
        }
    }
    // Guidance/Counseling
    elseif (preg_match('/\b(guidance|counseling|counselor|mental health|stress|anxiety|depression|advice|help)\b/i', $message)) {
        $office = findOffice($offices, ['guidance', 'counseling', 'counselor']);
        if ($office) {
            $response = "For **guidance and counseling services**, you should book with the **{$office['office_name']}**.\n\n";
            if (!empty($office['description'])) {
                $response .= "They handle: {$office['description']}.\n\n";
            }
            $response .= "**Would you like me to book an appointment for you?** Just say 'book me with Guidance' and I'll schedule it automatically.";
        } else {
            $response = "For guidance and counseling, you'll need to contact the **Guidance Office**.\n\n";
            $response .= "**I can book this for you!** Just say 'book me with Guidance' and I'll schedule it automatically.";
        }
    }
    // Library
    elseif (preg_match('/\b(library|book|borrow|return|research|study|libro)\b/i', $message)) {
        $office = findOffice($offices, ['library']);
        if ($office) {
            $response = "For **library services** (borrowing books, research assistance, study spaces), you should book with the **{$office['office_name']}**.\n\n";
            if (!empty($office['description'])) {
                $response .= "They handle: {$office['description']}.\n\n";
            }
            $response .= "**Would you like me to book an appointment for you?** Just say 'book me with Library' and I'll schedule it automatically.";
        } else {
            $response = "For library services, you'll need to contact the **Library**.\n\n";
            $response .= "**I can book this for you!** Just say 'book me with Library' and I'll schedule it automatically.";
        }
    }
    // Clinic/Health
    elseif (preg_match('/\b(clinic|health|medical|doctor|nurse|sick|illness|medicine|gamot)\b/i', $message)) {
        $office = findOffice($offices, ['clinic', 'health', 'medical']);
        if ($office) {
            $response = "For **health and medical concerns**, you should book with the **{$office['office_name']}**.\n\n";
            if (!empty($office['description'])) {
                $response .= "They handle: {$office['description']}.\n\n";
            }
            $response .= "**Would you like me to book an appointment for you?** Just say 'book me with Clinic' and I'll schedule it automatically.";
        } else {
            $response = "For health and medical concerns, you'll need to contact the **Clinic**.\n\n";
            $response .= "**I can book this for you!** Just say 'book me with Clinic' and I'll schedule it automatically.";
        }
    }
    // Office hours
    elseif (preg_match('/\b(hours|time|when|open|close|available|schedule|oras)\b/i', $message)) {
        $response = "Office hours are typically **9:00 AM to 4:00 PM**, Monday to Friday.\n\n";
        $response .= "**I can book an appointment for you right now!** Just tell me which office you need and I'll find the best available time.";
    }
    // How to book
    elseif (preg_match('/\b(how|book|appointment|schedule|reserve|process|steps|paano|mag-book)\b/i', $message)) {
        $response = "Here's how to book an appointment:\n\n";
        $response .= "**Option 1: I can book for you!** 🚀\n";
        $response .= "Just say: 'Book me with [Office Name]' or 'Schedule an appointment with [Office Name]'\n";
        $response .= "I'll automatically find the best available time and book it for you!\n\n";
        $response .= "**Option 2: Manual booking**\n";
        $response .= "1. Select the office from the menu\n";
        $response .= "2. Choose your preferred date\n";
        $response .= "3. Select an available time slot\n";
        $response .= "4. Fill in your concern and submit\n\n";
        $response .= "Which would you prefer?";
    }
    // List offices
    elseif (preg_match('/\b(office|offices|list|what|which|available|options|ano|saan)\b/i', $message)) {
        if (count($offices) > 0) {
            $response = "Here are the available offices:\n\n";
            foreach ($offices as $office) {
                $response .= "• **{$office['office_name']}**";
                if (!empty($office['location'])) {
                    $response .= " - {$office['location']}";
                }
                $response .= "\n";
            }
            $response .= "\n**I can book any of these for you!** Just say 'book me with [Office Name]' and I'll schedule it automatically.";
        } else {
            $response = "Please select an office from the dropdown menu to see available options.\n\n";
            $response .= "**Or tell me which office you need and I'll book it for you!**";
        }
    }
    // Default response
    else {
        $response = "I understand you're asking about: \"" . htmlspecialchars($message) . "\"\n\n";
        $response .= "I can help you with:\n";
        $response .= "• Finding the right office for your concern\n";
        $response .= "• Understanding how to book appointments\n";
        $response .= "• **Booking appointments automatically for you**\n";
        $response .= "• Information about office hours and services\n\n";
        $response .= "**Try saying:**\n";
        $response .= "• 'Book me with Registrar' → I'll schedule it automatically\n";
        $response .= "• 'I need my transcript' → I'll direct you and offer to book\n";
        $response .= "• 'Check my appointments' → I'll show your booking history";
    }

    return $response;
}

/**
 * Helper function to find office by keywords
 */
function findOffice($offices, $keywords) {
    foreach ($offices as $office) {
        $officeName = strtolower($office['office_name']);
        $description = strtolower($office['description'] ?? '');
        
        foreach ($keywords as $keyword) {
            if (strpos($officeName, $keyword) !== false || strpos($description, $keyword) !== false) {
                return $office;
            }
        }
    }
    return null;
}
