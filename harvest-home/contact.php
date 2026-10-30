<?php
require_once 'includes/functions.php';

const SHOP_EMAIL = 'hello@harvestandhome.co.za';
$form = ['name' => '', 'email' => '', 'subject' => 'Order enquiry', 'message' => ''];
$errors = [];
$subjects = ['Order enquiry', 'Delivery', 'General'];

if (is_post()) {
    check_csrf();
    foreach ($form as $k => $v) $form[$k] = post($k);
    if (mb_strlen($form['name']) < 2) $errors[] = 'Enter your name.';
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (!in_array($form['subject'], $subjects, true)) $errors[] = 'Choose a subject.';
    if (mb_strlen($form['message']) < 10) $errors[] = 'Your message should be at least 10 characters.';

    if (!$errors) {
        // Strip line breaks from header values to block header injection
        $replyTo = str_replace(["\r", "\n"], '', $form['email']);
        $sent = @mail(
            SHOP_EMAIL,
            'Website enquiry: ' . $form['subject'],
            "From: {$form['name']} <{$form['email']}>\n\n{$form['message']}",
            'Reply-To: ' . $replyTo
        );
        if ($sent) {
            flash('success', 'Thank you, your message has been sent. We will reply within one business day.');
            redirect('contact.php');
        }
        $errors[] = 'Your message could not be sent right now. Please email us directly at ' . SHOP_EMAIL . '.';
    }
}

$pageTitle = 'Contact Us';
$active = 'contact.php';
require 'includes/header.php';
?>
<div class="page-head">
    <h1>Contact Us</h1>
    <p>We'd love to hear from you.</p>
</div>
<div class="two even">
    <div class="card info-list">
        <div class="info-item">
            <span class="ico"><?= icon('geo-alt') ?></span>
            <div><b>Our Location</b><p>Stellenbosch, Western Cape, 7600</p></div>
        </div>
        <div class="info-item">
            <span class="ico honey"><?= icon('envelope') ?></span>
            <div><b>Email Us</b><p><a href="mailto:<?= SHOP_EMAIL ?>"><?= SHOP_EMAIL ?></a></p></div>
        </div>
        <div class="info-item">
            <span class="ico brown"><?= icon('telephone') ?></span>
            <div><b>Call Us</b><p><a href="tel:+27211234567">+27 21 123 4567</a></p></div>
        </div>
        <div class="info-item">
            <span class="ico blue"><?= icon('clock') ?></span>
            <div><b>Business Hours</b><p>Mon to Fri: 08:00 to 17:00</p><p>Sat: 08:00 to 13:00</p></div>
        </div>
        <div class="info-item">
            <span class="ico plum"><?= icon('truck') ?></span>
            <div>
                <b>Delivery</b>
                <p>Mon to Fri: 09:00 to 16:00</p>
                <p>Delivery covers the Western Cape and takes up to 5 days depending on distance.</p>
                <p><?= money(DELIVERY_FEE) ?> once-off delivery fee, any distance.</p>
            </div>
        </div>
    </div>
    <form class="card" method="post" action="contact.php" data-validate>
        <?= csrf_field() ?>
        <h2 class="card-title"><?= icon('chat-dots') ?>Send us a message</h2>
        <?php foreach ($errors as $e): ?><div class="alert alert-error"><?= h($e) ?></div><?php endforeach; ?>
        <label for="name">Your Name</label>
        <input id="name" name="name" value="<?= h($form['name']) ?>" required>
        <label for="email">Your Email</label>
        <input id="email" type="email" name="email" value="<?= h($form['email']) ?>" data-rule="email" required>
        <label for="subject">Subject</label>
        <select id="subject" name="subject">
            <?php foreach ($subjects as $s): ?><option <?= $form['subject'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
        </select>
        <label for="message">Message</label>
        <textarea id="message" name="message" rows="5" required><?= h($form['message']) ?></textarea>
        <button class="btn" type="submit"><?= icon('send') ?>Send Message</button>
    </form>
</div>
<?php require 'includes/footer.php'; ?>
