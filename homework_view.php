<?php
require_once __DIR__ . '/functions.php';
require_roles(['Сурагч']);
close_expired_homeworks();

$studentId = current_user()['id'];
$daalgavarId = (int)($_GET['id'] ?? 0);
if (!$daalgavarId) {
    flash('Даалгавар олдсонгүй.', 'error');
    header('Location: student_homeworks.php');
    exit;
}

$homework = one_row(
    'SELECT gd.daalgavar_id, gd.garchig, gd.tailbar, gd.daalgavar_turul, gd.hariu_turul, gd.ogson_ognoo, gd.duusah_ognoo, gd.hamgiin_ih_onoo, gd.tuluv,
            h.hicheel_ner, a.angi_ner,
            CONCAT(COALESCE(aj.ovog, ""), CASE WHEN aj.ovog IS NOT NULL AND aj.ner IS NOT NULL THEN " " ELSE "" END, COALESCE(aj.ner, "")) AS bagsh_ner
     FROM geriin_daalgavar gd
     JOIN hicheel_bagsh hb ON hb.hicheel_bagsh_id = gd.hicheel_bagsh_id
     JOIN suragch s ON s.angi_id = hb.angi_id
     JOIN hicheel h ON h.hicheel_id = hb.hicheel_id
     JOIN angi a ON a.angi_id = hb.angi_id
     LEFT JOIN ajiltan aj ON aj.ajiltan_id = hb.bagsh_id
     WHERE gd.daalgavar_id = ? AND s.suragch_id = ?
     LIMIT 1',
    [$daalgavarId, $studentId]
);

if (!$homework) {
    flash('Энэ даалгаврыг харах эрхгүй байна.', 'error');
    header('Location: student_homeworks.php');
    exit;
}

$attachments = [];
$questions = [];
if (($homework['daalgavar_turul'] ?? 'Энгийн') === 'Энгийн') {
    $attachments = all_rows('SELECT havsralt_id, file_ner, file_zam, video_link, turul FROM daalgavar_havsralt WHERE daalgavar_id = ? ORDER BY havsralt_id ASC', [$daalgavarId]);
} else {
    $rows = all_rows('SELECT a.asuult_id, a.asuult, s.songolt_dugaar, s.songolt_text FROM daalgavriin_asuult a JOIN daalgavriin_songolt s ON s.asuult_id=a.asuult_id WHERE a.daalgavar_id=? ORDER BY a.asuult_id ASC, s.songolt_dugaar ASC', [$daalgavarId]);
    foreach ($rows as $row) {
        $questions[$row['asuult_id']]['asuult'] = $row['asuult'];
        $questions[$row['asuult_id']]['songoltuud'][] = ['dugaar' => $row['songolt_dugaar'], 'text' => $row['songolt_text']];
    }
}

$submission = one_row(
    'SELECT dh.hariu_id, dh.tuluv, dh.ilgeesen_ognoo, dh.file_zam, dh.video_link, dh.tekst_hariu, u.avsan_onoo, u.unelsen_ognoo, u.bagshiin_tailbar
     FROM daalgavriin_hariu dh
     LEFT JOIN unelgee u ON u.hariu_id = dh.hariu_id
     WHERE dh.daalgavar_id = ? AND dh.suragch_id = ?
     LIMIT 1',
    [$daalgavarId, $studentId]
);

$overdue = is_homework_overdue($homework['duusah_ognoo']);
$canSubmit = !$submission && !$overdue && ($homework['tuluv'] === 'Идэвхтэй');
$canEditSubmission = $submission && ($submission['avsan_onoo'] === null) && (($homework['daalgavar_turul'] ?? 'Энгийн') !== 'Тест');
$submissionStatus = $submission ? 'Шалгуулахаар илгээсэн' : 'Одоогоор хариу илгээгээгүй байна';
$gradingStatus = $submission && $submission['avsan_onoo'] !== null ? 'Үнэлгээ тавигдсан' : 'Үнэлгээ тавигдаагүй';
$timeText = $submission ? ('Илгээсэн огноо: ' . format_dt($submission['ilgeesen_ognoo'])) : homework_time_text($homework['duusah_ognoo']);

$pageTitle = 'Даалгаврын дэлгэрэнгүй';
include __DIR__ . '/header.php';
?>
<div class="max-w-5xl mx-auto space-y-6">
  <section class="bg-white rounded-2xl shadow-sm border overflow-hidden">
    <div class="p-6 border-b">
      <p class="text-sm text-indigo-700 font-semibold mb-2"><?= e($homework['hicheel_ner']) ?> · <?= e($homework['angi_ner']) ?></p>
      <div class="flex items-center gap-2 flex-wrap">
        <h3 class="font-semibold text-2xl text-slate-900"><?= e($homework['garchig']) ?></h3>
        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold <?= ($homework['daalgavar_turul'] ?? 'Энгийн') === 'Тест' ? 'bg-amber-100 text-amber-700' : 'bg-sky-100 text-sky-700' ?>"><?= e($homework['daalgavar_turul'] ?: 'Энгийн') ?></span>
      </div>
      <div class="mt-3 flex flex-wrap gap-3 text-sm text-slate-500">
        <span>Нээгдсэн: <strong class="text-slate-700"><?= e(format_dt($homework['ogson_ognoo'])) ?></strong></span>
        <span>Дуусах: <strong class="text-slate-700"><?= e(format_dt($homework['duusah_ognoo'])) ?></strong></span>
        <span>Багш: <strong class="text-slate-700"><?= e($homework['bagsh_ner'] ?: '-') ?></strong></span>
        <span>Оноо: <strong class="text-slate-700"><?= e((string)$homework['hamgiin_ih_onoo']) ?></strong></span>
      </div>
    </div>
    <div class="p-6 prose prose-slate max-w-none whitespace-pre-line leading-7 text-slate-700"><?= nl2br(e((string)($homework['tailbar'] ?: 'Тайлбар оруулаагүй байна.'))) ?></div>
  </section>

  <section class="bg-white rounded-2xl shadow-sm border overflow-hidden">
    <div class="p-6 border-b flex justify-between items-center gap-4">
      <h3 class="font-semibold text-2xl">Хариултын төлөв</h3>
      <div class="flex flex-wrap items-center gap-2">
        <?php if ($canSubmit): ?>
          <a href="submission_create.php?daalgavar_id=<?= e((string)$homework['daalgavar_id']) ?>" class="inline-flex items-center px-4 py-2 rounded-xl bg-indigo-700 text-white font-medium"><?= ($homework['daalgavar_turul'] ?? 'Энгийн') === 'Тест' ? 'Тест бөглөх' : 'Хариу илгээх' ?></a>
        <?php endif; ?>
        <?php if ($canEditSubmission): ?>
          <a href="submission_edit.php?id=<?= e((string)$submission['hariu_id']) ?>" class="inline-flex items-center px-4 py-2 rounded-xl bg-indigo-700 text-white font-medium">Хариу засах</a>
        <?php endif; ?>
        <?php if (!$submission && $overdue): ?>
          <span class="inline-flex px-4 py-2 rounded-xl bg-red-100 text-red-700 font-medium">Хугацаа дууссан</span>
        <?php endif; ?>
      </div>
    </div>
    <table class="w-full text-sm">
      <tbody>
        <tr class="border-t"><th class="text-left p-4 w-64 bg-slate-50">Хариултын төлөв</th><td class="p-4 <?= $submission ? 'bg-green-50' : '' ?>"><?= e($submissionStatus) ?></td></tr>
        <tr class="border-t"><th class="text-left p-4 bg-slate-50">Үнэлгээний төлөв</th><td class="p-4 <?= $submission && $submission['avsan_onoo'] !== null ? 'bg-green-50' : '' ?>"><?= e($gradingStatus) ?></td></tr>
        <tr class="border-t"><th class="text-left p-4 bg-slate-50">Үлдсэн хугацаа</th><td class="p-4 <?= $overdue && !$submission ? 'bg-red-50 text-red-700' : 'text-slate-700' ?>"><?= e($timeText) ?></td></tr>
        <?php if ($submission): ?>
          <tr class="border-t"><th class="text-left p-4 bg-slate-50">Сүүлд өөрчилсөн</th><td class="p-4"><?= e(format_dt($submission['ilgeesen_ognoo'])) ?></td></tr>
          <?php if (!empty($submission['file_zam'])): ?><tr class="border-t"><th class="text-left p-4 bg-slate-50">Илгээсэн файл</th><td class="p-4"><a class="text-indigo-700 underline" href="<?= e($submission['file_zam']) ?>" target="_blank"><?= e(display_file_name($submission['file_zam'])) ?></a></td></tr><?php endif; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </section>

  <div class="grid lg:grid-cols-3 gap-6">
    <section class="lg:col-span-2 bg-white rounded-2xl shadow-sm border p-6">
      <?php if (($homework['daalgavar_turul'] ?? 'Энгийн') === 'Энгийн'): ?>
        <h4 class="font-semibold text-lg mb-4">Даалгаврын хавсралт</h4>
        <div class="space-y-3">
          <?php foreach ($attachments as $att): ?>
            <div class="rounded-xl border border-slate-200 p-4 bg-slate-50">
              <div class="text-xs uppercase tracking-wide text-slate-400 mb-2"><?= e($att['turul'] ?: 'Хавсралт') ?></div>
              <?php if (!empty($att['file_zam'])): ?><a class="text-indigo-700 font-medium underline break-all" href="<?= e($att['file_zam']) ?>" target="_blank"><?= e(display_file_name($att['file_ner'] ?: $att['file_zam'])) ?></a><?php endif; ?>
              <?php if (!empty($att['video_link'])): ?><div class="mt-2"><a class="text-indigo-700 font-medium underline break-all" href="<?= e($att['video_link']) ?>" target="_blank">Видео холбоос нээх</a></div><?php endif; ?>
            </div>
          <?php endforeach; ?>
          <?php if (!$attachments): ?><div class="rounded-xl border border-dashed border-slate-300 p-4 text-sm text-slate-500 bg-slate-50">Хавсралт оруулаагүй байна.</div><?php endif; ?>
        </div>
      <?php else: ?>
        <h4 class="font-semibold text-lg mb-4">Тестийн асуултууд</h4>
        <div class="space-y-4">
          <?php foreach ($questions as $q): ?>
            <div class="rounded-xl border border-slate-200 p-4 bg-slate-50">
              <div class="font-semibold mb-3"><?= e($q['asuult']) ?></div>
              <ol class="list-decimal pl-5 space-y-1 text-sm text-slate-700"><?php foreach ($q['songoltuud'] as $choice): ?><li><?= e($choice['text']) ?></li><?php endforeach; ?></ol>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <section class="bg-white rounded-2xl shadow-sm border p-6">
      <h4 class="font-semibold text-lg mb-4">Үнэлгээний мэдээлэл</h4>
      <div class="space-y-3 text-sm">
        <div class="flex justify-between border-b pb-3"><span class="font-semibold">Оноо</span><span><?= e(format_score_with_letter($submission['avsan_onoo'] ?? null)) ?><?php if ($submission && $submission['avsan_onoo'] !== null): ?> / <?= e((string)$homework['hamgiin_ih_onoo']) ?><?php endif; ?></span></div>
        <div class="flex justify-between border-b pb-3"><span class="font-semibold">Шалгасан огноо</span><span><?= e(format_dt($submission['unelsen_ognoo'] ?? null)) ?></span></div>
        <div class="border-b pb-3"><div class="font-semibold mb-1">Багшийн тайлбар</div><div class="text-slate-600 whitespace-pre-line"><?= nl2br(e((string)($submission['bagshiin_tailbar'] ?? '-'))) ?></div></div>
      </div>
      <a href="student_homeworks.php" class="mt-6 inline-flex items-center px-4 py-2 rounded-xl border border-slate-300 text-slate-700 font-medium bg-white">Буцах</a>
    </section>
  </div>
</div>
<?php include __DIR__ . '/footer.php'; ?>
