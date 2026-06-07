<?php
require_once __DIR__ . '/functions.php';
require_roles(['Багш']);
$teacherId = current_user()['id'];
$daalgavarId = (int)($_GET['id'] ?? $_POST['daalgavar_id'] ?? 0);
if (!$daalgavarId) {
    flash('Даалгавар олдсонгүй.', 'error');
    header('Location: homework_create.php');
    exit;
}
$homework = one_row('SELECT gd.*, hb.bagsh_id, h.hicheel_ner, a.angi_ner FROM geriin_daalgavar gd JOIN hicheel_bagsh hb ON hb.hicheel_bagsh_id=gd.hicheel_bagsh_id JOIN hicheel h ON h.hicheel_id=hb.hicheel_id JOIN angi a ON a.angi_id=hb.angi_id WHERE gd.daalgavar_id=? AND hb.bagsh_id=? LIMIT 1', [$daalgavarId, $teacherId]);
if (!$homework || ($homework['daalgavar_turul'] ?? 'Энгийн') !== 'Тест') {
    flash('Тест даалгавар олдсонгүй эсвэл хандах эрхгүй байна.', 'error');
    header('Location: homework_create.php');
    exit;
}

if (isset($_GET['delete'])) {
    $qid = (int)$_GET['delete'];
    db()->prepare('DELETE FROM daalgavriin_asuult WHERE asuult_id=? AND daalgavar_id=?')->execute([$qid, $daalgavarId]);
    flash('Асуулт устгагдлаа.');
    header('Location: test_question_manage.php?id=' . $daalgavarId);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update';
    if ($action === 'update') {
        $qid = (int)($_POST['asuult_id'] ?? 0);
        $asuult = trim($_POST['asuult'] ?? '');
        $zuv = (int)($_POST['zuv_songolt'] ?? 1);
        $choices = [
            1 => trim($_POST['songolt1'] ?? ''),
            2 => trim($_POST['songolt2'] ?? ''),
            3 => trim($_POST['songolt3'] ?? ''),
            4 => trim($_POST['songolt4'] ?? ''),
        ];
        if ($qid && $asuult !== '' && $zuv >= 1 && $zuv <= 4 && !in_array('', $choices, true)) {
            $pdo = db();
            $pdo->beginTransaction();
            try {
                $pdo->prepare('UPDATE daalgavriin_asuult SET asuult=?, zuv_songolt=? WHERE asuult_id=? AND daalgavar_id=?')->execute([$asuult, $zuv, $qid, $daalgavarId]);
                foreach ($choices as $num => $text) {
                    $existing = one_row('SELECT songolt_id FROM daalgavriin_songolt WHERE asuult_id=? AND songolt_dugaar=? LIMIT 1', [$qid, $num]);
                    if ($existing) {
                        $pdo->prepare('UPDATE daalgavriin_songolt SET songolt_text=? WHERE songolt_id=?')->execute([$text, $existing['songolt_id']]);
                    } else {
                        $pdo->prepare('INSERT INTO daalgavriin_songolt (asuult_id, songolt_dugaar, songolt_text) VALUES (?, ?, ?)')->execute([$qid, $num, $text]);
                    }
                }
                $pdo->commit();
                flash('Тестийн асуулт шинэчлэгдлээ.');
            } catch (Throwable $e) {
                $pdo->rollBack();
                flash('Асуулт шинэчлэхэд алдаа гарлаа: ' . $e->getMessage(), 'error');
            }
        } else {
            flash('Асуулт, 4 сонголт болон зөв хариултыг бүрэн бөглөнө үү.', 'error');
        }
    }
    header('Location: test_question_manage.php?id=' . $daalgavarId);
    exit;
}

$rows = all_rows('SELECT a.asuult_id, a.asuult, a.zuv_songolt, s.songolt_dugaar, s.songolt_text FROM daalgavriin_asuult a LEFT JOIN daalgavriin_songolt s ON s.asuult_id=a.asuult_id WHERE a.daalgavar_id=? ORDER BY a.asuult_id ASC, s.songolt_dugaar ASC', [$daalgavarId]);
$questions = [];
foreach ($rows as $row) {
    $qid = (int)$row['asuult_id'];
    if (!isset($questions[$qid])) {
        $questions[$qid] = ['asuult_id'=>$qid,'asuult'=>$row['asuult'],'zuv_songolt'=>(int)$row['zuv_songolt'],'songoltuud'=>[]];
    }
    if ($row['songolt_dugaar']) {
        $questions[$qid]['songoltuud'][(int)$row['songolt_dugaar']] = $row['songolt_text'];
    }
}
$pageTitle = 'Тестийн асуулт засах';
include __DIR__ . '/header.php';
?>
<div class="space-y-6">
  <section class="bg-white rounded-2xl shadow-sm border p-6">
    <div class="flex items-center justify-between gap-3 flex-wrap">
      <div>
        <h3 class="font-semibold text-lg">Тестийн асуултууд</h3>
        <p class="text-sm text-slate-500 mt-1"><?= e($homework['garchig']) ?> · <?= e($homework['hicheel_ner']) ?> / <?= e($homework['angi_ner']) ?></p>
      </div>
      <a href="homework_edit.php?id=<?= e((string)$daalgavarId) ?>" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium">Даалгавар луу буцах</a>
    </div>
  </section>

  <?php foreach ($questions as $q): ?>
  <form method="post" class="bg-white rounded-2xl shadow-sm border p-6 space-y-4">
    <input type="hidden" name="daalgavar_id" value="<?= e((string)$daalgavarId) ?>">
    <input type="hidden" name="asuult_id" value="<?= e((string)$q['asuult_id']) ?>">
    <div class="flex items-center justify-between gap-3">
      <h4 class="font-semibold">Асуулт #<?= e((string)$q['asuult_id']) ?></h4>
      <a class="rounded-xl bg-red-600 text-white px-4 py-2 text-sm font-medium" href="test_question_manage.php?id=<?= e((string)$daalgavarId) ?>&delete=<?= e((string)$q['asuult_id']) ?>" onclick="return confirm('Асуултыг устгах уу?')">Устгах</a>
    </div>
    <textarea name="asuult" rows="2" class="w-full rounded-xl border-slate-300 px-4 py-3" required><?= e($q['asuult']) ?></textarea>
    <div class="grid md:grid-cols-2 gap-3">
      <?php for ($i=1; $i<=4; $i++): ?>
        <div class="space-y-2">
          <label class="block text-sm font-medium"><?= $i ?>-р сонголт</label>
          <input name="songolt<?= $i ?>" class="w-full rounded-xl border-slate-300 px-4 py-3" value="<?= e($q['songoltuud'][$i] ?? '') ?>" required>
        </div>
      <?php endfor; ?>
    </div>
    <div>
      <label class="block text-sm font-medium mb-2">Зөв хариулт</label>
      <select name="zuv_songolt" class="w-full rounded-xl border-slate-300 px-4 py-3" required>
        <?php for ($i=1; $i<=4; $i++): ?>
          <option value="<?= $i ?>" <?= (int)$q['zuv_songolt'] === $i ? 'selected' : '' ?>><?= $i ?>-р сонголт</option>
        <?php endfor; ?>
      </select>
    </div>
    <button class="rounded-xl bg-indigo-700 text-white px-5 py-3 font-medium">Засах</button>
  </form>
  <?php endforeach; ?>

  <?php if (!$questions): ?>
    <div class="empty-state bg-white rounded-2xl border">Энэ тестэд асуулт бүртгэгдээгүй байна.</div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/footer.php'; ?>
