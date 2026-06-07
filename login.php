<?php
require_once __DIR__ . '/functions.php';

if (is_logged_in()) {
    redirect_dashboard();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    try {
        if ($email && $password && login_attempt($email, $password)) {
            flash('Амжилттай нэвтэрлээ.');
            redirect_dashboard();
        }

        flash('Имэйл эсвэл нууц үг буруу байна.', 'error');

    } catch (Throwable $e) {
        flash('Өгөгдлийн сантай холбогдох үед алдаа гарлаа. config.php файлыг шалгана уу.', 'error');
    }
}

$flash = flash();
?>
<!DOCTYPE html>
<html lang="mn">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Нэвтрэх | <?= e(APP_NAME) ?></title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
  <script>
    tailwind.config = {theme:{extend:{colors:{primary:'#1A237E',secondary:'#526069',background:'#F7F9FC'}}}}
  </script>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body class="bg-background min-h-screen flex items-center justify-center p-6 relative overflow-hidden font-body">
  <div class="absolute inset-0 academic-pattern z-0"></div>
  <div class="absolute top-[-10%] right-[-5%] w-[40vw] h-[40vw] bg-primary/5 rounded-full blur-[120px] z-0"></div>
  <div class="absolute bottom-[-10%] left-[-5%] w-[30vw] h-[30vw] bg-primary/5 rounded-full blur-[100px] z-0"></div>

  <main class="relative z-10 w-full max-w-[1100px] grid grid-cols-1 lg:grid-cols-2 bg-white rounded-[2rem] shadow-2xl overflow-hidden ring-1 ring-slate-200/70">
    <section class="hidden lg:flex flex-col justify-between p-12 auth-hero relative overflow-hidden text-white">
      <div>
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center shadow-lg text-primary">
            <span class="material-symbols-outlined">history_edu</span>
          </div>
          <span class="font-headline font-extrabold text-2xl tracking-tighter"><?= e(APP_NAME) ?></span>
        </div>
      </div>
      <div class="space-y-6">
        <p class="text-indigo-100/90 text-lg max-w-md font-light leading-relaxed">Даалгавраа цаг тухайд нь гүйцэтгэх, өөрийн гүйцэтгэлээ цаг алдалгүй үнэлүүлэх боломжийг олгоно</p>
      </div>
      <div class="grid grid-cols-2 gap-4 text-sm">
        <div class="bg-white/10 rounded-2xl p-4">Суралцах явцыг хянах боломжтой</div>
        <div class="bg-white/10 rounded-2xl p-4">Сурагчид өгөх зөвлөгөө</div>
        <div class="bg-white/10 rounded-2xl p-4">Үнэлгээ, үр дүнг шууд харах</div>
        <div class="bg-white/10 rounded-2xl p-4">Цаг хугацаагаа зөв зохион байгуулахад тусална</div>
      </div>
    </section>

    <section class="p-8 md:p-16 flex flex-col justify-center">
      <div class="max-w-md mx-auto w-full space-y-8">
        <header class="space-y-2 text-center lg:text-left">
          <div class="lg:hidden flex justify-center mb-6">
            <div class="w-12 h-12 bg-primary rounded-xl flex items-center justify-center shadow-lg text-white">
              <span class="material-symbols-outlined">history_edu</span>
            </div>
          </div>
          <h2 class="font-headline text-3xl font-extrabold text-primary">Нэвтрэх хэсэг</h2>
          <p class="text-secondary text-sm">Системд өөрийн бүртгэлээр нэвтэрнэ үү.</p>
        </header>

        <?php if ($flash): ?>
          <div class="rounded-2xl px-5 py-4 border <?= $flash['type']==='error' ? 'bg-red-50 text-red-700 border-red-200' : 'bg-green-50 text-green-700 border-green-200' ?>">
            <?= e($flash['message']) ?>
          </div>
        <?php endif; ?>

        <form method="post" class="space-y-6">
          <div>
            <label class="text-xs font-bold text-secondary uppercase tracking-widest px-1">Имэйл</label>
            <input name="email" type="email" required
                   class="mt-2 w-full rounded-2xl border-slate-300 bg-white px-4 py-3"
                   placeholder="name@school.mn">
          </div>
          <div>
            <label class="text-xs font-bold text-secondary uppercase tracking-widest px-1">Нууц үг</label>
            <input name="password" type="password" required
                   class="mt-2 w-full rounded-2xl border-slate-300 bg-white px-4 py-3"
                   placeholder="••••••••">
          </div>
          <button class="w-full bg-primary text-white rounded-2xl py-3.5 font-semibold shadow-lg shadow-indigo-200">
            Нэвтрэх
          </button>
        </form>
      </div>
    </section>
  </main>
</body>
</html>
