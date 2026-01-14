<?php

declare(strict_types=1);

/**
 * Example Snippet: Call to Action
 *
 * Usage in content:
 * [snippet name="cta" heading="Join Us" button_text="Sign Up" button_url="/signup"]
 *
 * Available variables:
 * - $params: array of shortcode attributes
 * - $content: inner content (if any)
 * - $app: Ava Application instance
 */

$heading = $params['heading'] ?? 'Get Started';
$text = $params['text'] ?? 'Ready to try Ava CMS?';
$buttonText = $params['button_text'] ?? 'Learn More';
$buttonUrl = $params['button_url'] ?? '/about';

?>
<div class="cta-box" style="background: #f3f4f6; padding: 2rem; border-radius: 0.5rem; text-align: center; margin: 2rem 0;">
    <h3 style="margin: 0 0 0.5rem;"><?= htmlspecialchars($heading) ?></h3>
    <p style="margin: 0 0 1rem; color: #6b7280;"><?= htmlspecialchars($text) ?></p>
    <a href="<?= htmlspecialchars($buttonUrl) ?>" style="display: inline-block; background: #2563eb; color: white; padding: 0.75rem 1.5rem; border-radius: 0.375rem; text-decoration: none;">
        <?= htmlspecialchars($buttonText) ?>
    </a>
</div>
