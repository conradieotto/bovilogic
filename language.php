<?php
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/language.php';
require_once __DIR__ . '/lib/db.php';

requireLogin();
$user = currentUser();
loadLanguage($user['language']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lang = $_POST['language'] ?? 'en';
    if (in_array($lang, ['en', 'af'])) {
        DB::exec('UPDATE users SET language = ? WHERE id = ?', [$lang, $user['id']]);
        $_SESSION['user_language'] = $lang;
        setcookie('bl_lang', $lang, time() + 60*60*24*365, '/');
        header('Location: /language.php?saved=1');
        exit;
    }
}

$pageTitle = 'language';
require_once __DIR__ . '/templates/header.php';
?>

<header class="page-header">
  <a href="/more.php" class="btn-icon">
    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
  </a>
  <h1><?= t('language') ?></h1>
</header>

<?php if (isset($_GET['saved'])): ?>
<div class="alert-bar success" style="margin:16px"><?= t('success') ?></div>
<?php endif; ?>

<div style="padding:16px">
<form method="POST">
  <div class="list-card" style="margin:0 0 16px">
    <label class="list-item" style="cursor:pointer">
      <div class="item-body"><div class="item-title">English</div></div>
      <input type="radio" name="language" value="en" <?= $user['language']==='en'?'checked':'' ?>>
    </label>
    <label class="list-item" style="cursor:pointer">
      <div class="item-body"><div class="item-title">Afrikaans</div></div>
      <input type="radio" name="language" value="af" <?= $user['language']==='af'?'checked':'' ?>>
    </label>
  </div>
  <button type="submit" class="btn btn-primary btn-full"><?= t('save') ?></button>
</form>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
