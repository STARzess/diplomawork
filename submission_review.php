<?php
require_once __DIR__ . '/functions.php';
require_roles(['Багш']);
$teacherId = current_user()['id'];
$filterType = $_GET['turul'] ?? 'Бүгд';
if (!in_array($filterType, ['Бүгд', 'Тест', 'Энгийн'], true)) { $filterType = 'Бүгд'; }
$filterAngiId = (int)($_GET['angi_id'] ?? 0);
$filterDaalgavarId = (int)($_GET['daalgavar_id'] ?? 0);

function submission_review_url(array $extra = []): string {
    $params = [];
    if (isset($_GET['turul']) && $_GET['turul'] !== 'Бүгд') { $params['turul'] = $_GET['turul']; }
    if (!empty($_GET['angi_id'])) { $params['angi_id'] = (int)$_GET['angi_id']; }
    if (!empty($_GET['daalgavar_id'])) { $params['daalgavar_id'] = (int)$_GET['daalgavar_id']; }
    foreach ($extra as $key => $value) {
        if ($value === null || $value === '' || $value === 'Бүгд' || $value === 0) { unset($params[$key]); }
        else { $params[$key] = $value; }
    }
    return 'submission_review.php' . ($params ? '?' . http_build_query($params) : '');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hariuId = (int)($_POST['hariu_id'] ?? 0);
    $score = (float)($_POST['avsan_onoo'] ?? 0);
    $comment = trim($_POST['bagshiin_tailbar'] ?? '');
    $rowType = one_row('SELECT gd.daalgavar_turul FROM daalgavriin_hariu dh JOIN geriin_daalgavar gd ON gd.daalgavar_id=dh.daalgavar_id JOIN hicheel_bagsh hb ON hb.hicheel_bagsh_id=gd.hicheel_bagsh_id WHERE dh.hariu_id=? AND hb.bagsh_id=? LIMIT 1', [$hariuId, $teacherId]);
    if ($rowType && ($rowType['daalgavar_turul'] ?? 'Энгийн') === 'Тест') {
        flash('Тестийн даалгавар автоматаар шалгагдана. Гараар үнэлэх шаардлагагүй.', 'error');
        $backUrl = $_POST['back_url'] ?? submission_review_url();
        header('Location: ' . $backUrl);
        exit;
    }
    if ($hariuId) {
        $exists = all_rows('SELECT unelgee_id FROM unelgee WHERE hariu_id=?', [$hariuId]);
        if ($exists) {
            $stmt = db()->prepare('UPDATE unelgee SET avsan_onoo=?, bagshiin_tailbar=?, unelsen_ognoo=NOW(), shalgasan_bagsh_id=? WHERE hariu_id=?');
            $stmt->execute([$score, $comment, $teacherId, $hariuId]);
        } else {
            $stmt = db()->prepare('INSERT INTO unelgee (hariu_id, shalgasan_bagsh_id, avsan_onoo, bagshiin_tailbar) VALUES (?, ?, ?, ?)');
            $stmt->execute([$hariuId, $teacherId, $score, $comment]);
        }
        db()->prepare("UPDATE daalgavriin_hariu SET tuluv='Шалгагдсан' WHERE hariu_id=?")->execute([$hariuId]);
        flash('Үнэлгээ амжилттай хадгалагдлаа.');
        $backUrl = $_POST['back_url'] ?? submission_review_url();
        header('Location: ' . $backUrl);
        exit;
    }
}
$pageTitle = 'Илгээсэн хариу шалгах';
$angiOptions = all_rows('SELECT DISTINCT a.angi_id, a.angi_ner FROM hicheel_bagsh hb JOIN angi a ON a.angi_id=hb.angi_id WHERE hb.bagsh_id=? ORDER BY a.angi_ner', [$teacherId]);
$listSql = 'SELECT dh.hariu_id, s.ovog, s.ner, h.hicheel_ner, gd.garchig, gd.daalgavar_id, gd.daalgavar_turul, gd.hamgiin_ih_onoo, dh.tekst_hariu, dh.file_zam, dh.video_link, dh.ilgeesen_ognoo, u.avsan_onoo, u.bagshiin_tailbar, a.angi_id, a.angi_ner FROM daalgavriin_hariu dh JOIN suragch s ON s.suragch_id=dh.suragch_id JOIN geriin_daalgavar gd ON gd.daalgavar_id=dh.daalgavar_id JOIN hicheel_bagsh hb ON hb.hicheel_bagsh_id=gd.hicheel_bagsh_id JOIN hicheel h ON h.hicheel_id=hb.hicheel_id JOIN angi a ON a.angi_id=hb.angi_id LEFT JOIN unelgee u ON u.hariu_id=dh.hariu_id WHERE hb.bagsh_id=?';
$listParams = [$teacherId];
if ($filterDaalgavarId > 0) {
    $listSql .= ' AND gd.daalgavar_id=?';
    $listParams[] = $filterDaalgavarId;
}
if ($filterType !== 'Бүгд') {
    $listSql .= ' AND gd.daalgavar_turul=?';
    $listParams[] = $filterType;
}
if ($filterAngiId > 0) {
    $listSql .= ' AND a.angi_id=?';
    $listParams[] = $filterAngiId;
}
$listSql .= ' ORDER BY dh.hariu_id DESC';
$rows = all_rows($listSql, $listParams);
include __DIR__ . '/header.php';
?>
<div class="space-y-6">
  <div class="bg-white rounded-2xl shadow-sm border p-5">
    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
      <form method="get" class="grid grid-cols-1 sm:grid-cols-3 gap-3 lg:min-w-[620px]">
        <?php if ($filterDaalgavarId > 0): ?><input type="hidden" name="daalgavar_id" value="<?= e((string)$filterDaalgavarId) ?>"><?php endif; ?>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-2">Төрөл</label>
          <select name="turul" class="w-full rounded-xl border-slate-300 px-4 py-3">
            <option value="Бүгд" <?= $filterType === 'Бүгд' ? 'selected' : '' ?>>Бүгд</option>
            <option value="Тест" <?= $filterType === 'Тест' ? 'selected' : '' ?>>Тест</option>
            <option value="Энгийн" <?= $filterType === 'Энгийн' ? 'selected' : '' ?>>Энгийн</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-2">Анги</label>
          <select name="angi_id" class="w-full rounded-xl border-slate-300 px-4 py-3">
            <option value="0">Бүх анги</option>
            <?php foreach ($angiOptions as $angi): ?>
              <option value="<?= (int)$angi['angi_id'] ?>" <?= $filterAngiId === (int)$angi['angi_id'] ? 'selected' : '' ?>><?= e($angi['angi_ner']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="flex items-end gap-2">
          <button class="w-full rounded-xl bg-indigo-700 text-white px-4 py-3 font-medium">Шүүх</button>
          <?php if ($filterType !== 'Бүгд' || $filterAngiId > 0 || $filterDaalgavarId > 0): ?>
            <a href="submission_review.php" class="rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 whitespace-nowrap">Цэвэрлэх</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
    <div class="text-xs text-slate-400 mt-4">Нийт <?= count($rows) ?> хариу</div>
  </div>
  <?php if ($filterDaalgavarId > 0): ?><div class="bg-indigo-50 border border-indigo-100 rounded-2xl px-5 py-4 text-sm text-indigo-800 flex justify-between items-center"><span>Сонгосон даалгаврын илгээсэн хариуг шүүж харуулж байна.</span><a class="underline" href="<?= e(submission_review_url(['daalgavar_id' => 0])) ?>">Энэ даалгаврын шүүлтийг авах</a></div><?php endif; ?>
  <?php foreach ($rows as $row): ?>
  <?php
    $testAnswers = [];
    if (($row['daalgavar_turul'] ?? 'Энгийн') === 'Тест') {
        $testRows = all_rows('SELECT a.asuult, a.zuv_songolt, s.songolt_text AS zuv_text, th.songolt_dugaar AS songoson_dugaar, ss.songolt_text AS songoson_text FROM test_hariu th JOIN daalgavriin_asuult a ON a.asuult_id=th.asuult_id LEFT JOIN daalgavriin_songolt s ON s.asuult_id=a.asuult_id AND s.songolt_dugaar=a.zuv_songolt LEFT JOIN daalgavriin_songolt ss ON ss.asuult_id=a.asuult_id AND ss.songolt_dugaar=th.songolt_dugaar WHERE th.hariu_id=? ORDER BY a.asuult_id ASC', [$row['hariu_id']]);
        $testAnswers = $testRows;
    }
  ?>
  <div class="bg-white rounded-2xl shadow-sm border p-6">
    <div class="grid lg:grid-cols-2 gap-6">
      <div>
        <div class="flex items-center gap-2 flex-wrap">
          <h3 class="font-semibold text-lg"><?= e($row['garchig']) ?></h3>
          <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold <?= ($row['daalgavar_turul'] ?? 'Энгийн') === 'Тест' ? 'bg-amber-100 text-amber-700' : 'bg-sky-100 text-sky-700' ?>"><?= e($row['daalgavar_turul'] ?: 'Энгийн') ?></span>
        </div>
        <p class="text-slate-500 text-sm mt-1"><?= e($row['hicheel_ner']) ?> · <?= e($row['angi_ner']) ?> · <?= e($row['ovog'].' '.$row['ner']) ?></p>
        <div class="mt-4 space-y-3 text-sm">
          <div><span class="font-medium">Илгээсэн огноо:</span> <?= e(format_dt($row['ilgeesen_ognoo'])) ?></div>
          <?php if (($row['daalgavar_turul'] ?? 'Энгийн') === 'Тест'): ?>
            <div class="rounded-xl bg-amber-50 border border-amber-200 p-4 text-amber-800">Тестийн даалгаврыг систем автоматаар шалгасан.</div>
            <div class="space-y-3">
              <?php foreach ($testAnswers as $ans): ?>
                <div class="rounded-xl border border-slate-200 p-4 bg-slate-50">
                  <div class="font-medium mb-2"><?= e($ans['asuult']) ?></div>
                  <div class="text-sm">Сурагчийн сонголт: <span class="font-medium <?= ((int)$ans['songoson_dugaar'] === (int)$ans['zuv_songolt']) ? 'text-green-700' : 'text-red-700' ?>"><?= e($ans['songoson_text'] ?: 'Сонгоогүй') ?></span></div>
                  <div class="text-sm mt-1">Зөв хариулт: <span class="font-medium text-slate-800"><?= e($ans['zuv_text'] ?: '-') ?></span></div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div><span class="font-medium">Текст хариу:</span><div class="mt-1 p-3 bg-slate-50 rounded-xl min-h-[80px]"><?= nl2br(e((string)$row['tekst_hariu'])) ?></div></div>
            <?php if ($row['file_zam']): ?><div><a class="text-indigo-700 underline" href="<?= e($row['file_zam']) ?>" target="_blank"><?= e(display_file_name($row['file_zam'])) ?></a></div><?php endif; ?>
            <?php if ($row['video_link']): ?><div><a class="text-indigo-700 underline" href="<?= e($row['video_link']) ?>" target="_blank">Видео холбоос</a></div><?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
      <div>
        <?php if (($row['daalgavar_turul'] ?? 'Энгийн') === 'Тест'): ?>
          <div class="space-y-4">
            <div class="rounded-2xl border border-slate-200 p-5 bg-slate-50">
              <div class="text-sm text-slate-500 mb-2">Автоматаар шалгасан оноо</div>
              <div class="text-3xl font-extrabold text-indigo-700"><?= e((string)($row['avsan_onoo'] ?? 0)) ?> / <?= e((string)$row['hamgiin_ih_onoo']) ?></div>
            </div>
            <div class="rounded-2xl border border-slate-200 p-5 bg-white">
              <div class="text-sm text-slate-500 mb-2">Системийн тайлбар</div>
              <div class="text-sm text-slate-700 whitespace-pre-line"><?= nl2br(e((string)$row['bagshiin_tailbar'])) ?></div>
            </div>
          </div>
        <?php else: ?>
          <form method="post" class="space-y-4">
            <input type="hidden" name="hariu_id" value="<?= e((string)$row['hariu_id']) ?>">
            <input type="hidden" name="back_url" value="<?= e(submission_review_url()) ?>">
            <div>
              <label class="block text-sm font-medium mb-2">Оноо</label>
              <input name="avsan_onoo" type="number" step="0.01" value="<?= e((string)($row['avsan_onoo'] ?? 0)) ?>" class="w-full rounded-xl border-slate-300 px-4 py-3" required>
            </div>
            <div>
              <label class="block text-sm font-medium mb-2">Багшийн тайлбар</label>
              <textarea name="bagshiin_tailbar" rows="6" class="w-full rounded-xl border-slate-300 px-4 py-3"><?= e((string)$row['bagshiin_tailbar']) ?></textarea>
            </div>
            <button class="w-full bg-indigo-700 text-white rounded-xl py-3 font-medium">Үнэлгээ хадгалах</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php if (!$rows): ?><div class="empty-state bg-white rounded-2xl border">Шалгах илгээсэн хариу алга байна.</div><?php endif; ?>
</div>
<?php include __DIR__ . '/footer.php'; ?>
