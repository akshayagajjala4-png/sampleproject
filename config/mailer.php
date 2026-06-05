<?php
function sendMail($to, $subject, $message) {
  $apiKey = "YOUR_BREVO_API_KEY_HERE";
    $data = [
        "sender"      => ["name" => "MindMerge SmartCampus", "email" => "ibabaprakurddin@gmail.com"],
        "to"          => [["email" => $to]],
        "subject"     => $subject,
        "htmlContent" => $message
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,            "https://api.brevo.com/v3/smtp/email");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST,           true);
    curl_setopt($ch, CURLOPT_POSTFIELDS,     json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER,     [
        "accept: application/json",
        "api-key: $apiKey",
        "content-type: application/json"
    ]);
    // WITH this:
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT,        20);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

$response = curl_exec($ch);
$curlErr  = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Log every failure so you can see what's wrong
if ($curlErr || $httpCode !== 201) {
    file_put_contents(
        __DIR__ . '/mail_error.log',
        date('Y-m-d H:i:s') . " | HTTP $httpCode | cURL: $curlErr | Body: $response\n",
        FILE_APPEND
    );
    return false;
}
return true;
}

/**
 * Send a branded OTP email.
 * Returns the 6-digit OTP string on success, false on failure.
 */
function sendOtpEmail($to, $name) {
    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    $html = '
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f5f0e8;font-family:DM Sans,Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f0e8;padding:40px 0;">
    <tr><td align="center">
      <table width="480" cellpadding="0" cellspacing="0"
             style="background:#fdfaf5;border-radius:16px;overflow:hidden;
                    box-shadow:0 4px 24px rgba(74,59,32,.10);">

        <!-- Header -->
        <tr>
          <td style="background:#4a3b20;padding:28px 36px;text-align:center;">
            <span style="font-size:32px;">🧠</span>
            <h1 style="margin:8px 0 0;color:#f5f0e8;font-size:22px;font-weight:700;
                       letter-spacing:-0.3px;">MindMerge SmartCampus</h1>
          </td>
        </tr>

        <!-- Body -->
        <tr>
          <td style="padding:36px 36px 28px;">
            <p style="margin:0 0 6px;color:#7a6a55;font-size:13px;
                      text-transform:uppercase;letter-spacing:2px;font-weight:600;">
              Email Verification</p>
            <h2 style="margin:0 0 18px;color:#2b2015;font-size:26px;font-weight:700;">
              Hi, ' . htmlspecialchars($name) . '!</h2>
            <p style="margin:0 0 24px;color:#4a3b28;font-size:15px;line-height:1.7;">
              Use the one-time code below to verify your email address.
              This code expires in <strong>10 minutes</strong>.
            </p>

            <!-- OTP Box -->
            <div style="background:#f5f0e8;border:2px dashed #b8a98c;border-radius:12px;
                        padding:24px;text-align:center;margin-bottom:28px;">
              <p style="margin:0 0 6px;color:#7a6a55;font-size:12px;
                        text-transform:uppercase;letter-spacing:2px;">Your OTP Code</p>
              <span style="font-size:42px;font-weight:700;letter-spacing:12px;
                           color:#4a3b20;font-family:monospace;">' . $otp . '</span>
            </div>

            <p style="margin:0;color:#7a6a55;font-size:13px;line-height:1.6;">
              If you did not create an account, you can safely ignore this email.
            </p>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="background:#ede6d6;padding:16px 36px;text-align:center;">
            <p style="margin:0;color:#b8a98c;font-size:12px;">
              &copy; ' . date('Y') . ' MindMerge SmartCampus. All rights reserved.
            </p>
          </td>
        </tr>

      </table>
    </td></tr>
  </table>
</body>
</html>';

    $sent = sendMail($to, 'Your MindMerge Verification Code: ' . $otp, $html);
    return $sent ? $otp : false;
}