<?php

require_once dirname(__DIR__, 2) . "/app/core/bootstrap.php";

ApiMiddleware::handle([
    "methods" => ["POST"],
    "roles" => ["superadmin", "manager", "consultant", "contractor", "clerk", "finance", "intern"],
    "csrf_form" => "contact_inbox",
]);

// Read JSON body (the JS sends Content-Type: application/json).
// Fall back to $_POST for any form-encoded requests.
$input = $_POST;
if ($input === []) {
    $decoded = json_decode(file_get_contents("php://input") ?: "", true);
    $input = is_array($decoded) ? $decoded : [];
}

$id = Security::cleanInt($input["id"] ?? 0);
$subject = trim(Security::cleanString((string) ($input["subject"] ?? "")));
$body = trim((string) ($input["body"] ?? ""));
$userId = (int) Auth::id();

if ($id <= 0) {
    Response::json(
        ["success" => false, "message" => "Contact message id is required."],
        422,
    );
}

if ($subject === "" || strlen($subject) > 220) {
    Response::json(
        [
            "success" => false,
            "message" => "Enter a reply subject under 220 characters.",
        ],
        422,
    );
}

if ($body === "" || strlen($body) < 10) {
    Response::json(
        [
            "success" => false,
            "message" => "Write a clear reply before sending.",
        ],
        422,
    );
}

$message = ContactSubmission::findDetailed($id);
if (!$message) {
    Response::json(
        [
            "success" => false,
            "message" => "Contact message could not be found.",
        ],
        404,
    );
}

$role = (string) (Auth::role() ?: "");
if (!ContactSubmission::canAccess($message, $userId, $role)) {
    Response::json(
        [
            "success" => false,
            "message" => "This contact message is not assigned to you.",
        ],
        403,
    );
}

$recipient = trim((string) ($message["email"] ?? ""));
if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
    Response::json(
        [
            "success" => false,
            "message" => "The sender email address is not valid.",
        ],
        422,
    );
}

$replyId = ContactReply::record([
    "contact_submission_id" => $id,
    "sender_user_id" => $userId,
    "recipient_email" => $recipient,
    "subject" => $subject,
    "body" => $body,
    "delivery_status" => "pending",
]);

$result = ResendMailer::send(
    $recipient,
    $subject,
    contact_reply_email_html($message, $body),
    [
        "template_key" => "contact_reply",
    ],
);

if (empty($result["success"])) {
    ContactReply::markDelivery(
        $replyId,
        "failed",
        $result["id"] ?? null,
        $result["message"] ?? "Email could not be sent.",
    );
    Logger::log("reply-email-failed", "contact_submissions", $id, [
        "email" => $recipient,
        "reason" => $result["message"] ?? "Email could not be sent.",
    ]);

    Response::json(
        [
            "success" => false,
            "message" =>
                $result["message"] ??
                "Email could not be sent. Check email settings and try again.",
        ],
        502,
    );
}

ContactReply::markDelivery($replyId, "sent", $result["id"] ?? null);
ContactSubmission::recordResponse($id, $body, $userId);
Logger::log("reply-email-sent", "contact_submissions", $id, [
    "email" => $recipient,
    "subject" => $subject,
]);

Response::json([
    "success" => true,
    "message" => "Reply sent to " . $recipient . ".",
]);

function contact_reply_email_html(array $message, string $reply): string
{
    $name = Security::e((string) ($message["name"] ?? "there"));
    $replyHtml = nl2br(Security::e($reply));
    $originalSubject = Security::e(
        (string) ($message["subject"] ?? "Contact enquiry"),
    );
    $originalMessage = nl2br(Security::e((string) ($message["message"] ?? "")));
    $sentAt = Security::e(format_datetime($message["created_at"] ?? ""));

    return <<<HTML
    <!doctype html>
    <html>
      <body style="margin:0;background:#f4f8f1;font-family:Arial,Helvetica,sans-serif;color:#10210b;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f8f1;padding:28px 12px;">
          <tr>
            <td align="center">
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border:1px solid #dbe8d2;border-radius:12px;overflow:hidden;">
                <tr>
                  <td style="padding:22px 26px;background:#123b05;color:#ffffff;">
                    <strong style="font-size:18px;">AHP Tracker</strong>
                    <div style="font-size:13px;opacity:.86;margin-top:4px;">Trans-Nzoia County Affordable Housing Programme</div>
                  </td>
                </tr>
                <tr>
                  <td style="padding:26px;">
                    <p style="margin:0 0 14px;">Hello {$name},</p>
                    <div style="font-size:15px;line-height:1.65;">{$replyHtml}</div>
                    <div style="margin-top:24px;padding:16px;border:1px solid #e2ecd9;border-radius:10px;background:#f8fbf5;">
                      <strong style="display:block;margin-bottom:8px;">Your enquiry</strong>
                      <div style="font-size:13px;color:#536a4a;margin-bottom:8px;">{$originalSubject} &middot; {$sentAt}</div>
                      <div style="font-size:13px;line-height:1.55;color:#374b31;">{$originalMessage}</div>
                    </div>
                    <p style="margin:24px 0 0;font-size:13px;color:#536a4a;">This response was sent by the Trans-Nzoia Affordable Housing Programme support team.</p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        </table>
      </body>
    </html>
    HTML;
}
