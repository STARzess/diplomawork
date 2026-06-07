<?php
require_once __DIR__ . '/functions.php';
require_roles(['Сургалтын алба']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kod = trim($_POST['hicheel_kod'] ?? '');
    $ner = trim($_POST['hicheel_ner'] ?? '');
    $tailbar = trim($_POST['tailbar'] ?? '');
    if ($ner !== '') {
        $stmt = db()->prepare('INSERT INTO hicheel (hicheel_kod, hicheel_ner, tailbar, tuluv, uusgesen_ognoo) VALUES (?, ?, ?, "Идэвхтэй", NOW())');
        $stmt->execute([$kod ?: null, $ner, $tailbar ?: null]);
        flash('Хичээл амжилттай бүртгэгдлээ.');
        header('Location: course_create.php');
        exit;
    }
    flash('Хичээлийн нэрийг оруулна уу.', 'error');
}
$pageTitle = 'Хичээл бүртгэх';
$rows = all_rows('SELECT hicheel_id, hicheel_kod, hicheel_ner, tailbar, tuluv, uusgesen_ognoo FROM hicheel ORDER BY hicheel_id DESC LIMIT 10');
include __DIR__ . '/header.php';
?>
<div class="grid lg:grid-cols-3 gap-6">
  <section class="form-card p-6">
    <div class="mb-5">
      <h3 class="font-headline text-xl font-extrabold text-primary">Шинэ хичээл</h3>
      <p class="page-subtitle mt-1">Хичээлийн үндсэн мэдээллийг бүртгэнэ.</p>
    </div>
    <form method="post" class="space-y-4">
      <div>
        <label class="block text-sm font-medium mb-2">Хичээлийн код</label>
        <input name="hicheel_kod" class="w-full rounded-2xl border-slate-300 px-4 py-3" placeholder="MATH1">
      </div>
      <div>
        <label class="block text-sm font-medium mb-2">Хичээлийн нэр</label>
        <input name="hicheel_ner" class="w-full rounded-2xl border-slate-300 px-4 py-3" placeholder="Математик 1-р анги" required>
      </div>
      <div>
        <label class="block text-sm font-medium mb-2">Тайлбар</label>
        <textarea name="tailbar" rows="5" class="w-full rounded-2xl border-slate-300 px-4 py-3" placeholder="Хичээлийн тайлбар"></textarea>
      </div>
      <button class="w-full bg-primary text-white rounded-2xl py-3 font-semibold">Хичээл бүртгэх</button>
    </form>
  </section>

  <section class="lg:col-span-2 table-shell">
    <div class="p-5 border-b flex items-center justify-between">
      <div>
        <h3 class="font-headline text-xl font-extrabold text-primary">Сүүлд бүртгэсэн хичээлүүд</h3>
        <p class="page-subtitle mt-1">Дараагийн алхам нь багш, ангид хуваарилах</p>
      </div>
      <a href="course_assignment.php" class="inline-flex items-center gap-2 rounded-2xl bg-slate-900 text-white px-4 py-2.5 text-sm font-medium">Хуваарилалт руу</a>
    </div>
    <table class="text-sm">
      <thead><tr><th class="text-left p-4">Код</th><th class="text-left p-4">Хичээл</th><th class="text-left p-4">Төлөв</th><th class="text-left p-4">Огноо</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $row): ?>
        <tr class="border-t"><td class="p-4"><?= e((string)$row['hicheel_kod']) ?></td><td class="p-4 font-medium"><a class="text-primary" href="course_detail.php?id=<?= e((string)$row['hicheel_id']) ?>"><?= e($row['hicheel_ner']) ?></a></td><td class="p-4"><span class="status-pill status-active"><?= e($row['tuluv']) ?></span></td><td class="p-4"><?= e(format_dt($row['uusgesen_ognoo'])) ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </section>
</div>
<?php include __DIR__ . '/footer.php'; ?>
