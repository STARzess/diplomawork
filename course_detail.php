<?php
require_once __DIR__ . '/functions.php';
require_roles(['Админ','Сургалтын алба','Багш']);
close_expired_homeworks();

$role = current_role();
$teacherId = current_user()['id'];
$mode = $_GET['mode'] ?? 'course';
$id = (int)($_GET['id'] ?? 0);
$submissionType = $_GET['submission_type'] ?? 'Бүгд';
if (!in_array($submissionType, ['Бүгд', 'Тест', 'Энгийн'], true)) {
    $submissionType = 'Бүгд';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'Багш') {
    $hariuId = (int)($_POST['hariu_id'] ?? 0);
    $score = (float)($_POST['avsan_onoo'] ?? 0);
    $comment = trim($_POST['bagshiin_tailbar'] ?? '');
    $back = $_POST['back_url'] ?? 'course_list.php';
    $rowType = one_row('SELECT gd.daalgavar_turul FROM daalgavriin_hariu dh JOIN geriin_daalgavar gd ON gd.daalgavar_id=dh.daalgavar_id JOIN hicheel_bagsh hb ON hb.hicheel_bagsh_id=gd.hicheel_bagsh_id WHERE dh.hariu_id=? AND hb.bagsh_id=? LIMIT 1', [$hariuId, $teacherId]);
    if ($rowType && ($rowType['daalgavar_turul'] ?? 'Энгийн') !== 'Тест') {
        $exists = one_row('SELECT unelgee_id FROM unelgee WHERE hariu_id=?', [$hariuId]);
        if ($exists) {
            db()->prepare('UPDATE unelgee SET avsan_onoo=?, bagshiin_tailbar=?, unelsen_ognoo=NOW(), shalgasan_bagsh_id=? WHERE hariu_id=?')->execute([$score, $comment, $teacherId, $hariuId]);
        } else {
            db()->prepare('INSERT INTO unelgee (hariu_id, shalgasan_bagsh_id, avsan_onoo, bagshiin_tailbar) VALUES (?, ?, ?, ?)')->execute([$hariuId, $teacherId, $score, $comment]);
        }
        db()->prepare("UPDATE daalgavriin_hariu SET tuluv='Шалгагдсан' WHERE hariu_id=?")->execute([$hariuId]);
        flash('Үнэлгээ амжилттай хадгалагдлаа.');
    } else {
        flash('Тестийн даалгавар автоматаар шалгагдана эсвэл хандах эрхгүй байна.', 'error');
    }
    header('Location: ' . $back);
    exit;
}

if ($mode === 'assignment') {
    $assignmentSql = 'SELECT hb.hicheel_bagsh_id, hb.hicheel_id, hb.angi_id, hb.surgaltiin_jil, h.hicheel_ner, h.hicheel_kod, h.tailbar, a.angi_ner, aj.ovog, aj.ner, aj.ajiltan_id AS bagsh_id FROM hicheel_bagsh hb JOIN hicheel h ON h.hicheel_id=hb.hicheel_id JOIN angi a ON a.angi_id=hb.angi_id JOIN ajiltan aj ON aj.ajiltan_id=hb.bagsh_id WHERE hb.hicheel_bagsh_id=?';
    $params = [$id];
    if ($role === 'Багш') { $assignmentSql .= ' AND hb.bagsh_id=?'; $params[] = $teacherId; }
    $assignment = one_row($assignmentSql, $params);
    if (!$assignment) { flash('Хуваарилалт олдсонгүй эсвэл хандах эрхгүй байна.', 'error'); header('Location: course_list.php'); exit; }
    $courseId = (int)$assignment['hicheel_id'];
    $targetHbId = (int)$assignment['hicheel_bagsh_id'];
    $course = ['hicheel_id'=>$courseId, 'hicheel_ner'=>$assignment['hicheel_ner'], 'hicheel_kod'=>$assignment['hicheel_kod'], 'tailbar'=>$assignment['tailbar']];
} else {
    $course = one_row('SELECT * FROM hicheel WHERE hicheel_id=?', [$id]);
    if (!$course) { flash('Хичээл олдсонгүй.', 'error'); header('Location: course_list.php'); exit; }
    $courseId = (int)$course['hicheel_id'];
    $targetHbId = (int)($_GET['hb'] ?? 0);
}

if ($role === 'Багш') {
    $assignments = all_rows('SELECT hb.hicheel_bagsh_id, hb.surgaltiin_jil, a.angi_ner, aj.ovog, aj.ner, COUNT(DISTINCT s.suragch_id) AS suragch_too, COUNT(DISTINCT gd.daalgavar_id) AS daalgavar_too FROM hicheel_bagsh hb JOIN angi a ON a.angi_id=hb.angi_id JOIN ajiltan aj ON aj.ajiltan_id=hb.bagsh_id LEFT JOIN suragch s ON s.angi_id=hb.angi_id AND s.tuluv="Идэвхтэй" LEFT JOIN geriin_daalgavar gd ON gd.hicheel_bagsh_id=hb.hicheel_bagsh_id WHERE hb.hicheel_id=? AND hb.bagsh_id=? GROUP BY hb.hicheel_bagsh_id ORDER BY hb.hicheel_bagsh_id DESC', [$courseId, $teacherId]);
    if (!$targetHbId && $assignments) $targetHbId = (int)$assignments[0]['hicheel_bagsh_id'];
} else {
    $assignments = all_rows('SELECT hb.hicheel_bagsh_id, hb.surgaltiin_jil, a.angi_ner, aj.ovog, aj.ner, COUNT(DISTINCT s.suragch_id) AS suragch_too, COUNT(DISTINCT gd.daalgavar_id) AS daalgavar_too FROM hicheel_bagsh hb JOIN angi a ON a.angi_id=hb.angi_id JOIN ajiltan aj ON aj.ajiltan_id=hb.bagsh_id LEFT JOIN suragch s ON s.angi_id=hb.angi_id AND s.tuluv="Идэвхтэй" LEFT JOIN geriin_daalgavar gd ON gd.hicheel_bagsh_id=hb.hicheel_bagsh_id WHERE hb.hicheel_id=? GROUP BY hb.hicheel_bagsh_id ORDER BY hb.hicheel_bagsh_id DESC', [$courseId]);
}

$hbFilterSql = $targetHbId ? ' AND hb.hicheel_bagsh_id=?' : '';
$hbParams = $targetHbId ? [$courseId, $targetHbId] : [$courseId];
if ($role === 'Багш' && !$targetHbId) $hbParams = [$courseId, $teacherId];

$homeworks = $targetHbId
    ? all_rows('SELECT gd.*, a.angi_ner FROM geriin_daalgavar gd JOIN hicheel_bagsh hb ON hb.hicheel_bagsh_id=gd.hicheel_bagsh_id JOIN angi a ON a.angi_id=hb.angi_id WHERE hb.hicheel_id=? AND hb.hicheel_bagsh_id=? ORDER BY gd.daalgavar_id DESC', [$courseId, $targetHbId])
    : all_rows('SELECT gd.*, a.angi_ner FROM geriin_daalgavar gd JOIN hicheel_bagsh hb ON hb.hicheel_bagsh_id=gd.hicheel_bagsh_id JOIN angi a ON a.angi_id=hb.angi_id WHERE hb.hicheel_id=? ORDER BY gd.daalgavar_id DESC', [$courseId]);

$students = [];
if ($targetHbId) {
    $students = all_rows('SELECT s.ovog, s.ner, s.email, a.angi_ner FROM hicheel_bagsh hb JOIN suragch s ON s.angi_id=hb.angi_id LEFT JOIN angi a ON a.angi_id=s.angi_id WHERE hb.hicheel_bagsh_id=? AND s.tuluv="Идэвхтэй" ORDER BY s.ovog, s.ner', [$targetHbId]);
}

$submissionSql = 'SELECT dh.hariu_id, dh.tuluv, dh.tekst_hariu, dh.file_zam, dh.video_link, dh.ilgeesen_ognoo, s.ovog, s.ner, gd.garchig, gd.daalgavar_turul, gd.hamgiin_ih_onoo, a.angi_ner, u.avsan_onoo, u.bagshiin_tailbar, u.unelsen_ognoo FROM daalgavriin_hariu dh JOIN suragch s ON s.suragch_id=dh.suragch_id JOIN geriin_daalgavar gd ON gd.daalgavar_id=dh.daalgavar_id JOIN hicheel_bagsh hb ON hb.hicheel_bagsh_id=gd.hicheel_bagsh_id JOIN angi a ON a.angi_id=hb.angi_id LEFT JOIN unelgee u ON u.hariu_id=dh.hariu_id WHERE hb.hicheel_id=?';
$submissionParams = [$courseId];
if ($targetHbId) { $submissionSql .= ' AND hb.hicheel_bagsh_id=?'; $submissionParams[] = $targetHbId; }
if ($role === 'Багш') { $submissionSql .= ' AND hb.bagsh_id=?'; $submissionParams[] = $teacherId; }
if ($submissionType !== 'Бүгд') { $submissionSql .= ' AND gd.daalgavar_turul=?'; $submissionParams[] = $submissionType; }
$submissionSql .= ' ORDER BY dh.ilgeesen_ognoo DESC';
$submissions = all_rows($submissionSql, $submissionParams);

$pageTitle = 'Хичээлийн дэлгэрэнгүй';
include __DIR__ . '/header.php';
$backParams = [];
if ($mode === 'assignment') {
    $backParams['id'] = $targetHbId;
    $backParams['mode'] = 'assignment';
} else {
    $backParams['id'] = $courseId;
    if ($targetHbId) {
        $backParams['hb'] = $targetHbId;
    }
}
if ($submissionType !== 'Бүгд') {
    $backParams['submission_type'] = $submissionType;
}
$backUrl = 'course_detail.php?' . http_build_query($backParams);
?>
<div class="space-y-6">
  <section class="bg-white rounded-2xl shadow-sm border p-6">
    <h3 class="font-headline text-2xl font-extrabold text-primary"><?= e((($course['hicheel_kod'] ?? '') ? $course['hicheel_kod'].' - ' : '').$course['hicheel_ner']) ?></h3>
    <p class="page-subtitle mt-2"><?= e((string)($course['tailbar'] ?? 'Тайлбар байхгүй.')) ?></p>
    <?php if ($mode === 'assignment'): ?><p class="mt-2 text-sm text-slate-600">Багш: <?= e($assignment['ovog'].' '.$assignment['ner']) ?> · Анги: <?= e($assignment['angi_ner']) ?> · Сургалтын жил: <?= e($assignment['surgaltiin_jil']) ?></p><?php endif; ?>
  </section>

  <section class="table-shell">
    <div class="p-5 border-b"><h3 class="font-semibold text-lg">Хуваарилалтын мэдээлэл</h3></div>
    <table class="w-full text-sm">
      <thead class="bg-slate-50"><tr><th class="text-left p-4">Багш</th><th class="text-left p-4">Анги</th><th class="text-left p-4">Жил</th><th class="text-left p-4">Сурагч</th><th class="text-left p-4">Даалгавар</th></tr></thead>
      <tbody>
      <?php foreach ($assignments as $row): ?>
        <tr class="border-t"><td class="p-4"><?= e($row['ovog'].' '.$row['ner']) ?></td><td class="p-4"><a class="text-primary" href="course_detail.php?id=<?= e((string)$row['hicheel_bagsh_id']) ?>&mode=assignment"><?= e($row['angi_ner']) ?></a></td><td class="p-4"><?= e($row['surgaltiin_jil']) ?></td><td class="p-4"><?= e((string)$row['suragch_too']) ?></td><td class="p-4"><?= e((string)$row['daalgavar_too']) ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$assignments): ?><tr><td colspan="5" class="empty-state">Хуваарилалт олдсонгүй.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </section>

  <section class="table-shell">
    <div class="p-5 border-b flex items-center justify-between"><h3 class="font-semibold text-lg">Энэ хичээлийн даалгаврууд</h3><?php if ($role === 'Багш'): ?><a class="px-4 py-2 rounded-xl bg-indigo-700 text-white text-sm" href="homework_create.php">Даалгавар нэмэх</a><?php endif; ?></div>
    <table class="w-full text-sm">
      <thead class="bg-slate-50"><tr><th class="text-left p-4">Гарчиг</th><th class="text-left p-4">Анги</th><th class="text-left p-4">Төрөл</th><th class="text-left p-4">Дуусах огноо</th><th class="text-left p-4">Төлөв</th><th class="text-left p-4">Оноо</th><?php if ($role === 'Багш'): ?><th class="text-left p-4">Үйлдэл</th><?php endif; ?></tr></thead>
      <tbody>
      <?php foreach ($homeworks as $row): ?>
        <?php $overdue = is_homework_overdue($row['duusah_ognoo']); ?>
        <tr class="border-t"><td class="p-4 font-medium"><?= e($row['garchig']) ?></td><td class="p-4"><?= e($row['angi_ner']) ?></td><td class="p-4"><?= e($row['daalgavar_turul'] ?: 'Энгийн') ?></td><td class="p-4"><div><?= e(format_dt($row['duusah_ognoo'])) ?></div><div class="text-xs <?= $overdue ? 'text-red-600' : 'text-slate-500' ?>"><?= e(homework_time_text($row['duusah_ognoo'])) ?></div></td><td class="p-4"><span class="px-3 py-1 rounded-full text-xs font-semibold <?= $overdue ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' ?>"><?= e($overdue ? 'Хугацаа дууссан' : $row['tuluv']) ?></span></td><td class="p-4"><?= e((string)$row['hamgiin_ih_onoo']) ?></td><?php if ($role === 'Багш'): ?><td class="p-4"><div class="flex flex-wrap gap-2"><a class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium" href="homework_edit.php?id=<?= (int)$row['daalgavar_id'] ?>">Засах</a><a class="rounded-lg bg-indigo-700 text-white px-3 py-1.5 text-xs font-medium" href="submission_review.php?daalgavar_id=<?= (int)$row['daalgavar_id'] ?>">Хариу шалгах</a></div></td><?php endif; ?></tr>
      <?php endforeach; ?>
      <?php if (!$homeworks): ?><tr><td colspan="<?= $role === 'Багш' ? 7 : 6 ?>" class="empty-state">Даалгавар олдсонгүй.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </section>

  <?php if ($role === 'Багш'): ?>
  <section class="space-y-4">
    <div class="bg-white rounded-2xl shadow-sm border p-5">
      <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
          <h3 class="font-semibold text-lg">Сурагчдын илгээсэн хариу шалгах</h3>
          <p class="text-sm text-slate-500 mt-1">Энд зөвхөн энэ хичээл/сонгосон ангийн даалгаварт ирсэн хариунууд харагдана.</p>
        </div>
        <form method="get" class="flex items-end gap-3">
          <input type="hidden" name="id" value="<?= e((string)($mode === 'assignment' ? $targetHbId : $courseId)) ?>">
          <?php if ($mode === 'assignment'): ?><input type="hidden" name="mode" value="assignment"><?php endif; ?>
          <?php if ($mode !== 'assignment' && $targetHbId): ?><input type="hidden" name="hb" value="<?= e((string)$targetHbId) ?>"><?php endif; ?>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Даалгаврын төрөл</label>
            <select name="submission_type" onchange="this.form.submit()" class="rounded-xl border-slate-300 px-4 py-3 min-w-[180px]">
              <option value="Бүгд" <?= $submissionType === 'Бүгд' ? 'selected' : '' ?>>Бүгд</option>
              <option value="Тест" <?= $submissionType === 'Тест' ? 'selected' : '' ?>>Тест</option>
              <option value="Энгийн" <?= $submissionType === 'Энгийн' ? 'selected' : '' ?>>Энгийн</option>
            </select>
          </div>
          <button type="submit" class="px-4 py-3 rounded-xl bg-indigo-700 text-white text-sm font-medium">Шүүх</button>
        </form>
      </div>
    </div>
    <?php foreach ($submissions as $row): ?>
      <div class="bg-white rounded-2xl shadow-sm border p-6">
        <div class="grid lg:grid-cols-2 gap-6">
          <div>
            <div class="flex gap-2 items-center flex-wrap"><h4 class="font-semibold text-lg"><?= e($row['garchig']) ?></h4><span class="px-3 py-1 rounded-full text-xs font-semibold <?= ($row['daalgavar_turul'] ?? 'Энгийн') === 'Тест' ? 'bg-amber-100 text-amber-700' : 'bg-sky-100 text-sky-700' ?>"><?= e($row['daalgavar_turul'] ?: 'Энгийн') ?></span></div>
            <p class="text-sm text-slate-500 mt-1"><?= e($row['angi_ner']) ?> · <?= e($row['ovog'].' '.$row['ner']) ?> · Илгээсэн: <?= e(format_dt($row['ilgeesen_ognoo'])) ?></p>
            <?php if (($row['daalgavar_turul'] ?? 'Энгийн') === 'Тест'): ?>
              <div class="mt-4 rounded-xl bg-amber-50 border border-amber-200 p-4 text-sm text-amber-800">Тестийн хариу автоматаар шалгагдсан. Оноо: <strong><?= e(format_score_with_letter($row['avsan_onoo'] ?? null)) ?></strong></div>
            <?php else: ?>
              <div class="mt-4 text-sm"><span class="font-medium">Текст хариу:</span><div class="mt-1 p-3 bg-slate-50 rounded-xl min-h-[80px]"><?= nl2br(e((string)$row['tekst_hariu'])) ?></div></div>
              <?php if ($row['file_zam']): ?><div class="mt-2"><a class="text-indigo-700 underline" href="<?= e($row['file_zam']) ?>" target="_blank"><?= e(display_file_name($row['file_zam'])) ?></a></div><?php endif; ?>
              <?php if ($row['video_link']): ?><div class="mt-2"><a class="text-indigo-700 underline" href="<?= e($row['video_link']) ?>" target="_blank">Видео холбоос</a></div><?php endif; ?>
            <?php endif; ?>
          </div>
          <div>
            <?php if (($row['daalgavar_turul'] ?? 'Энгийн') === 'Тест'): ?>
              <div class="rounded-2xl border border-slate-200 p-5 bg-slate-50"><div class="text-sm text-slate-500 mb-2">Автоматаар шалгасан оноо</div><div class="text-3xl font-extrabold text-indigo-700"><?= e((string)($row['avsan_onoo'] ?? 0)) ?> / <?= e((string)$row['hamgiin_ih_onoo']) ?></div><div class="text-sm text-slate-600 mt-3"><?= e((string)$row['bagshiin_tailbar']) ?></div></div>
            <?php else: ?>
              <form method="post" class="space-y-4">
                <input type="hidden" name="hariu_id" value="<?= e((string)$row['hariu_id']) ?>">
                <input type="hidden" name="back_url" value="<?= e($backUrl) ?>">
                <div><label class="block text-sm font-medium mb-2">Оноо / <?= e((string)$row['hamgiin_ih_onoo']) ?></label><input name="avsan_onoo" type="number" min="0" max="<?= e((string)$row['hamgiin_ih_onoo']) ?>" step="0.01" value="<?= e((string)($row['avsan_onoo'] ?? 0)) ?>" class="w-full rounded-xl border-slate-300 px-4 py-3" required></div>
                <div><label class="block text-sm font-medium mb-2">Багшийн тайлбар</label><textarea name="bagshiin_tailbar" rows="5" class="w-full rounded-xl border-slate-300 px-4 py-3"><?= e((string)$row['bagshiin_tailbar']) ?></textarea></div>
                <button class="w-full bg-indigo-700 text-white rounded-xl py-3 font-medium">Үнэлгээ хадгалах</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (!$submissions): ?><div class="empty-state bg-white rounded-2xl border">
      <?= e($submissionType === 'Бүгд' ? 'Энэ хичээл дээр сурагчийн илгээсэн хариу алга байна.' : $submissionType . ' төрлийн даалгаврын хариу олдсонгүй.') ?>
    </div><?php endif; ?>
  </section>
  <?php endif; ?>

  <?php if ($targetHbId): ?>
  <section class="table-shell">
    <div class="p-5 border-b"><h3 class="font-semibold text-lg">Энэ ангийн сурагчид</h3></div>
    <table class="w-full text-sm"><thead class="bg-slate-50"><tr><th class="text-left p-4">Нэр</th><th class="text-left p-4">Анги</th><th class="text-left p-4">Имэйл</th></tr></thead><tbody>
    <?php foreach ($students as $student): ?><tr class="border-t"><td class="p-4"><?= e($student['ovog'].' '.$student['ner']) ?></td><td class="p-4"><?= e((string)$student['angi_ner']) ?></td><td class="p-4"><?= e((string)$student['email']) ?></td></tr><?php endforeach; ?>
    <?php if (!$students): ?><tr><td colspan="3" class="empty-state">Энэ ангид идэвхтэй сурагч бүртгэгдээгүй байна.</td></tr><?php endif; ?>
    </tbody></table>
  </section>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/footer.php'; ?>
