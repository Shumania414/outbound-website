<?php
// OUTBOUND K.K. inquiry form handler (Sakura Rental Server, PHP mb_send_mail)
mb_language('Japanese');
mb_internal_encoding('UTF-8');
date_default_timezone_set('Asia/Tokyo');

define('TO', 'info@out-bounder.com');
// Sakura only sends from addresses that exist on this server, so the sender is the server's own mailbox.
// Replies still go to the right place through Reply-To.
define('FROM', 'outbound@outbound.sakura.ne.jp');
define('REPLY', 'info@out-bounder.com');
function p($k) { return isset($_POST[$k]) ? $_POST[$k] : ''; }
$mail_err = '';
// Try with envelope sender first, then without (some server setups refuse -f)
function send_mail($to, $subject, $body, $headers) {
  global $mail_err;
  if (@mb_send_mail($to, $subject, $body, $headers, '-f' . FROM)) return true;
  if (@mb_send_mail($to, $subject, $body, $headers)) return true;
  $e = error_get_last();
  $mail_err = $e ? $e['message'] : 'mb_send_mail returned false';
  return false;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /', true, 303); exit; }

$lang = ((p('lang')) === 'ja') ? 'ja' : 'en';
$back = $lang === 'ja' ? '/ja/' : '/';

// Spam trap: real visitors never fill this hidden field
if (!empty($_POST['bot-field'])) { header('Location: ' . ($lang === 'ja' ? '/ja/thanks/' : '/thanks/'), true, 303); exit; }

function clean($v, $max = 2000) {
  $v = is_string($v) ? trim($v) : '';
  $v = str_replace(["\r\n", "\r"], "\n", $v);
  return mb_substr($v, 0, $max);
}
function one_line($v) { return preg_replace('/[\r\n]+/', ' ', clean($v, 200)); }

$types = ['japan' => 'Event in Japan / 国内イベント', 'abroad' => 'Exhibition or event abroad / 海外展示会・海外イベント', 'staff' => 'Staffing only / スタッフ手配のみ', 'other' => 'Other / その他'];
$type    = isset($types[p('type')]) ? $types[p('type')] : 'Not selected';
$company = one_line(p('company'));
$name    = one_line(p('name'));
$email   = one_line(p('email'));
$country = one_line(p('country'));
$tel     = one_line(p('tel'));
$when    = one_line(p('when'));
$size    = one_line(p('guests') !== '' ? p('guests') : p('size'));
$message = clean(p('message'), 5000);

$errors = [];
if ($company === '') $errors[] = $lang === 'ja' ? '会社名' : 'Company';
if ($name === '')    $errors[] = $lang === 'ja' ? 'お名前' : 'Your name';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = $lang === 'ja' ? 'メールアドレス' : 'Email';
if ($message === '') $errors[] = $lang === 'ja' ? 'ご相談内容' : 'About the project';

if ($errors) {
  http_response_code(400);
  $title = $lang === 'ja' ? '入力内容をご確認ください' : 'Please check your inquiry';
  $lead  = ($lang === 'ja' ? '次の項目が未入力か、形式が正しくありません：' : 'These fields are missing or invalid: ') . htmlspecialchars(implode(', ', $errors), ENT_QUOTES, 'UTF-8');
  $btn   = $lang === 'ja' ? '戻って修正する' : 'Go back and fix';
  echo '<!doctype html><html lang="' . $lang . '"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex"><title>' . $title . '</title><link rel="stylesheet" href="/site.css"></head><body><main class="hero" style="padding-bottom:96px"><div class="wrap" style="display:block"><h1 style="font-size:clamp(30px,5vw,52px)">' . $title . '</h1><p class="lead" style="margin-top:16px">' . $lead . '</p><div class="ctas"><a class="btn" href="javascript:history.back()">' . $btn . '</a></div></div></main></body></html>';
  exit;
}

$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
$body = "Website inquiry (" . strtoupper($lang) . " page)\n"
      . "----------------------------------------\n"
      . "Type / 種類:      $type\n"
      . "Company / 会社名: $company\n"
      . "Name / お名前:    $name\n"
      . "Email:            $email\n"
      . ($tel !== ''     ? "Tel / 電話:       $tel\n" : '')
      . ($country !== '' ? "Country / 国:     $country\n" : '')
      . ($when !== ''    ? "City & dates / 開催地・時期: $when\n" : '')
      . ($size !== ''    ? "Guests / 人数:    $size\n" : '')
      . "----------------------------------------\n"
      . $message . "\n\n"
      . "Sent: " . date('Y-m-d H:i') . " JST / IP: $ip\n";

$subject = '[out-bounder.com] ' . ($lang === 'ja' ? 'お問い合わせ' : 'Inquiry') . ' - ' . $company;
$headers = "From: " . mb_encode_mimeheader('OUTBOUND Website') . " <" . FROM . ">\nReply-To: $email";
$ok = send_mail(TO, $subject, $body, $headers);

// Short confirmation to the sender (fixed text only, never echoes their message)
if ($ok) {
  if ($lang === 'ja') {
    $s = '【アウトバウンド】お問い合わせを受け付けました';
    $b = "$name 様\n\nお問い合わせありがとうございます。内容を確認し、1営業日以内にご連絡いたします。\n\n株式会社アウトバウンド（OUTBOUND K.K.）\ninfo@out-bounder.com\nhttps://www.out-bounder.com/ja/\n";
  } else {
    $s = 'We received your inquiry - OUTBOUND K.K.';
    $b = "Dear $name,\n\nThank you for contacting OUTBOUND. We will reply within one business day.\n\nOUTBOUND K.K.\ninfo@out-bounder.com\nhttps://www.out-bounder.com/\n";
  }
  send_mail($email, $s, $b, "From: " . mb_encode_mimeheader('OUTBOUND K.K.') . " <" . FROM . ">\nReply-To: " . REPLY);
  header('Location: ' . ($lang === 'ja' ? '/ja/thanks/' : '/thanks/'), true, 303);
  exit;
}

http_response_code(500);
echo '<!-- mail error: ' . htmlspecialchars($mail_err, ENT_QUOTES, 'UTF-8') . ' / PHP ' . PHP_VERSION . ' -->';
echo '<!doctype html><html lang="' . $lang . '"><head><meta charset="utf-8"><meta name="robots" content="noindex"><title>Error</title><link rel="stylesheet" href="/site.css"></head><body><main class="hero" style="padding-bottom:96px"><div class="wrap" style="display:block"><h1 style="font-size:clamp(30px,5vw,52px)">' . ($lang === 'ja' ? '送信できませんでした' : 'Your inquiry could not be sent') . '</h1><p class="lead" style="margin-top:16px">' . ($lang === 'ja' ? 'お手数ですが info@out-bounder.com へ直接ご連絡ください。' : 'Please email us directly at info@out-bounder.com.') . '</p><div class="ctas"><a class="btn" href="' . $back . '">' . ($lang === 'ja' ? 'トップへ戻る' : 'Back to home') . '</a></div></div></main></body></html>';
