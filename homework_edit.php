<?php
require_once __DIR__ . '/functions.php';
require_roles(['Багш']);

$teacherId = (int)current_user()['id'];
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if (!$id) {
    flash('Даалгавар олдсонгүй.', 'error');
    header('Location: teacher_homeworks.php');
    exit;
}

$teacherCourses = all_rows(
    'SELECT hb.hicheel_bagsh_id, h.hicheel_kod, h.hicheel_ner, a.angi_ner, hb.surgaltiin_jil
     FROM hicheel_bagsh hb
     JOIN hicheel h ON h.hicheel_id = hb.hicheel_id
     JOIN angi a ON a.angi_id = hb.angi_id
     WHERE hb.bagsh_id = ?
     ORDER BY h.hicheel_ner, a.angi_ner',
    [$teacherId]
);

$homework = one_row(
    'SELECT gd.*, hb.bagsh_id, h.hicheel_kod, h.hicheel_ner, a.angi_ner, hb.surgaltiin_jil
     FROM geriin_daalgavar gd
     JOIN hicheel_bagsh hb ON hb.hicheel_bagsh_id = gd.hicheel_bagsh_id
     JOIN hicheel h ON h.hicheel_id = hb.hicheel_id
     JOIN angi a ON a.angi_id = hb.angi_id
     WHERE gd.daalgavar_id = ? AND hb.bagsh_id = ?
     LIMIT 1',
    [$id, $teacherId]
);
if (!$homework) {
    flash('Энэ даалгаврыг засах эрхгүй байна.', 'error');
    header('Location: teacher_homeworks.php');
    exit;
}

$attachment = one_row('SELECT * FROM daalgavar_havsralt WHERE daalgavar_id=? ORDER BY havsralt_id DESC LIMIT 1', [$id]);
$currentFile = $attachment['file_zam'] ?? '';
$currentVideo = $attachment['video_link'] ?? '';

$questions = [];
if (($homework['daalgavar_turul'] ?? 'Энгийн') === 'Тест') {
    $rows = all_rows('SELECT a.asuult_id, a.asuult, a.zuv_songolt, s.songolt_dugaar, s.songolt_text
                      FROM daalgavriin_asuult a
                      LEFT JOIN daalgavriin_songolt s ON s.asuult_id=a.asuult_id
                      WHERE a.daalgavar_id=?
                      ORDER BY a.asuult_id ASC, s.songolt_dugaar ASC', [$id]);
    foreach ($rows as $row) {
        $qid = (int)$row['asuult_id'];
        if (!isset($questions[$qid])) {
            $questions[$qid] = [
                'asuult_id' => $qid,
                'asuult' => $row['asuult'],
                'zuv_songolt' => $row['zuv_songolt'],
                'songoltuud' => []
            ];
        }
        if ($row['songolt_dugaar']) {
            $questions[$qid]['songoltuud'][(int)$row['songolt_dugaar']] = $row['songolt_text'];
        }
    }
}

function normalize_edit_datetime(string $value, ?string $fallback = null): string {
    $value = trim($value);
    if ($value === '') return $fallback ?: date('Y-m-d H:i:s');
    $ts = strtotime($value);
    return $ts ? date('Y-m-d H:i:s', $ts) : $value;
}
function datetime_local_value(?string $value): string {
    if (!$value) return '';
    $ts = strtotime($value);
    return $ts ? date('Y-m-d\TH:i', $ts) : '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hicheelBagshId = (int)($_POST['hicheel_bagsh_id'] ?? $homework['hicheel_bagsh_id']);
    $garchig = trim($_POST['garchig'] ?? '');
    $tailbar = trim($_POST['tailbar'] ?? '');
    $ogson = normalize_edit_datetime($_POST['ogson_ognoo'] ?? '', $homework['ogson_ognoo']);
    $duusah = normalize_edit_datetime($_POST['duusah_ognoo'] ?? '', $homework['duusah_ognoo']);
    $onoo = (float)($_POST['hamgiin_ih_onoo'] ?? 100);
    $turul = trim($_POST['daalgavar_turul'] ?? 'Энгийн');
    $video = trim($_POST['video_link'] ?? '');
    
    $tuluv = 'Идэвхтэй';

    if (!in_array($turul, ['Энгийн', 'Тест'], true)) {
        $turul = 'Энгийн';
    }

    $courseAllowed = one_row('SELECT hicheel_bagsh_id FROM hicheel_bagsh WHERE hicheel_bagsh_id=? AND bagsh_id=? LIMIT 1', [$hicheelBagshId, $teacherId]);
    if (!$courseAllowed) {
        flash('Сонгосон хичээлд даалгавар засах эрхгүй байна.', 'error');
        header('Location: homework_edit.php?id=' . $id);
        exit;
    }

    if ($garchig === '' || $duusah === '') {
        flash('Гарчиг болон дуусах хугацааг заавал бөглөнө үү.', 'error');
        header('Location: homework_edit.php?id=' . $id);
        exit;
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE geriin_daalgavar
                       SET hicheel_bagsh_id=?, garchig=?, tailbar=?, daalgavar_turul=?, ogson_ognoo=?, duusah_ognoo=?, hamgiin_ih_onoo=?, tuluv=?
                       WHERE daalgavar_id=?')
            ->execute([$hicheelBagshId, $garchig, $tailbar, $turul, $ogson, $duusah, $onoo, $tuluv, $id]);

        if ($turul === 'Энгийн') {
            $newFilePath = upload_file_if_present('file');
            $finalFile = $newFilePath ?: ($currentFile ?: '');
            $finalVideo = $video;

            $pdo->prepare('DELETE FROM daalgavar_havsralt WHERE daalgavar_id=?')->execute([$id]);
            if ($finalFile || $finalVideo) {
                $attType = $finalVideo && !$finalFile ? 'Видео' : 'Файл';
                $pdo->prepare('INSERT INTO daalgavar_havsralt (daalgavar_id, file_ner, file_zam, video_link, turul) VALUES (?, ?, ?, ?, ?)')
                    ->execute([$id, $finalFile ? basename($finalFile) : null, $finalFile ?: null, $finalVideo ?: null, $attType]);
            }
        } else {
            $pdo->prepare('DELETE FROM daalgavar_havsralt WHERE daalgavar_id=?')->execute([$id]);
        }

        $pdo->commit();
        flash('Даалгаврын өөрчлөлт амжилттай хадгалагдлаа.');
        header('Location: homework_edit.php?id=' . $id);
        exit;
    } catch (Throwable $e) {
        $pdo->rollBack();
        flash('Даалгавар шинэчлэхэд алдаа гарлаа: ' . $e->getMessage(), 'error');
        header('Location: homework_edit.php?id=' . $id);
        exit;
    }
}

$pageTitle = 'Даалгавар засах';
include __DIR__ . '/header.php';
?>
<style>
  .homework-create-page { max-width: 1120px; }
  .hw-card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 10px 28px rgba(15,23,42,.04); }
  .hw-label { display:block; font-size:12px; line-height:1; letter-spacing:.08em; text-transform:uppercase; font-weight:800; color:#475569; margin-bottom:10px; }
  .hw-input, .hw-select, .hw-textarea { width:100%; border:0 !important; background:#f1f3f7; border-radius:8px; padding:14px 16px; font-size:14px; color:#0f172a; box-shadow:none !important; }
  .hw-input:focus, .hw-select:focus, .hw-textarea:focus { outline:none; box-shadow:0 0 0 3px rgba(26,35,126,.12) !important; background:#fff; }
  .hw-textarea { min-height:165px; resize:vertical; }
  .hw-side-title { font-size:18px; font-weight:800; color:#111c7a; margin-bottom:24px; }
  .hw-divider { height:1px; background:#e5e7eb; margin:22px 0; }
  .hw-score-wrap { display:flex; align-items:center; background:#f1f3f7; border-radius:8px; overflow:hidden; }
  .hw-score-wrap input { flex:1; background:transparent; border:0 !important; padding:14px 16px; box-shadow:none !important; }
  .hw-score-wrap span { padding:0 16px; color:#64748b; font-size:12px; font-weight:800; text-transform:uppercase; }
  .hw-toggle-row { display:flex; align-items:center; justify-content:space-between; gap:16px; margin-top:14px; font-size:14px; color:#334155; }
  .hw-switch { position:relative; width:38px; height:20px; display:inline-block; }
  .hw-switch input { opacity:0; width:0; height:0; }
  .hw-slider { position:absolute; cursor:pointer; inset:0; background:#dbe1ea; border-radius:999px; transition:.2s; }
  .hw-slider:before { content:""; position:absolute; height:14px; width:14px; left:3px; top:3px; background:white; border-radius:50%; transition:.2s; box-shadow:0 1px 3px rgba(15,23,42,.2); }
  .hw-switch input:checked + .hw-slider { background:#9ce0aa; }
  .hw-switch input:checked + .hw-slider:before { transform:translateX(18px); background:#45b95a; }
  .hw-attach-box { border:1px dashed #cbd5e1; border-radius:10px; min-height:138px; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; cursor:pointer; background:#fff; transition:.18s; }
  .hw-attach-box:hover { border-color:#1a237e; background:#f8fafc; }
  .hw-attach-icon { width:44px; height:44px; border-radius:12px; background:#f5f7fb; color:#8ca0bd; display:flex; align-items:center; justify-content:center; margin-bottom:12px; }
  .hw-attach-title { font-weight:800; color:#1e293b; }
  .hw-attach-help { font-size:12px; color:#8a9ab3; margin-top:6px; }
  .hw-file-row { display:flex; align-items:center; gap:12px; background:#f8fafc; border-radius:8px; padding:14px 16px; margin-top:22px; }
  .hw-file-row .material-symbols-outlined { color:#1a237e; }
  .hw-primary-btn { width:100%; border-radius:8px; background:#202184; color:#fff; padding:15px 18px; font-weight:800; display:flex; align-items:center; justify-content:center; gap:10px; box-shadow:0 8px 16px rgba(32,33,132,.22); }
  .hw-secondary-btn { width:100%; border-radius:8px; background:#fff; color:#111c7a; padding:15px 18px; font-weight:800; border:1px solid #dbe3ef; display:flex; align-items:center; justify-content:center; gap:10px; }
  .hw-cancel { display:block; text-align:center; color:#94a3b8; font-size:13px; margin-top:18px; }
  .hw-test-card { border:1px solid #e5e7eb; border-radius:12px; padding:16px; background:#f8fafc; }
</style>

<div class="homework-create-page space-y-8">
  <div>
    <h3 class="font-headline text-3xl font-extrabold text-primary">Даалгавар засах</h3>
    <p class="text-slate-500 mt-2">Бүртгэсэн гэрийн даалгаврын мэдээллийг засварлах.</p>
  </div>

  <form method="post" enctype="multipart/form-data" id="homeworkForm">
    <input type="hidden" name="id" value="<?= e((string)$id) ?>">
    <div class="grid lg:grid-cols-[1fr_310px] gap-8 items-start">
      <div class="space-y-7">
        <section class="hw-card p-7 space-y-6">
          <div>
            <label class="hw-label">Хичээл сонгох</label>
            <select name="hicheel_bagsh_id" class="hw-select" required>
              <option value="">Сонгох...</option>
              <?php foreach ($teacherCourses as $c): ?>
                <option value="<?= e((string)$c['hicheel_bagsh_id']) ?>" <?= (int)$c['hicheel_bagsh_id'] === (int)$homework['hicheel_bagsh_id'] ? 'selected' : '' ?>>
                  <?= e(($c['hicheel_kod'] ? $c['hicheel_kod'].' · ' : '').$c['hicheel_ner'].' / '.$c['angi_ner'].' / '.$c['surgaltiin_jil']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label class="hw-label">Гарчиг</label>
            <input name="garchig" class="hw-input" value="<?= e((string)$homework['garchig']) ?>" placeholder="Жишээ: Лаборатори №4 - Суурийн эффект" required>
          </div>

          <div>
            <label class="hw-label">Тайлбар</label>
            <textarea name="tailbar" class="hw-textarea" placeholder="Даалгаврын заавар, шаардлагуудыг энд дэлгэрэнгүй бичнэ үү..."> <?= e((string)$homework['tailbar']) ?></textarea>
          </div>
        </section>

        <section class="hw-card p-7" id="attachmentSection">
          <h3 class="font-headline text-xl font-extrabold text-primary mb-6 flex items-center gap-2">
            <span class="material-symbols-outlined">attach_file</span>
            Даалгаврын хавсралт
          </h3>
          <div class="grid md:grid-cols-2 gap-4">
            <label class="hw-attach-box">
              <input id="fileInput" name="file" type="file" class="hidden" accept=".pdf,.doc,.docx,.zip,.png,.jpg,.jpeg">
              <span class="hw-attach-icon"><span class="material-symbols-outlined">upload_file</span></span>
              <span class="hw-attach-title">Файл солих</span>
              <span class="hw-attach-help">PDF, DOCX, ZIP (Max 50MB)</span>
            </label>
            <div class="hw-attach-box" id="videoBox">
              <span class="hw-attach-icon"><span class="material-symbols-outlined">videocam</span></span>
              <span class="hw-attach-title">Видео эсвэл Линк</span>
              <span class="hw-attach-help">YouTube, Drive холбоос</span>
            </div>
          </div>
          <input name="video_link" id="videoInput" class="hw-input mt-4 <?= $currentVideo ? '' : 'hidden' ?>" value="<?= e((string)$currentVideo) ?>" placeholder="https://...">

          <?php if ($currentFile): ?>
            <div class="hw-file-row">
              <span class="material-symbols-outlined">description</span>
              <div>
                <a href="<?= e((string)$currentFile) ?>" target="_blank" class="font-semibold text-indigo-700 text-sm underline"><?= e(display_file_name($currentFile)) ?></a>
                <div class="text-xs text-slate-400">Одоогийн хавсралт</div>
              </div>
            </div>
          <?php endif; ?>
          <div id="selectedFile" class="hw-file-row hidden">
            <span class="material-symbols-outlined">description</span>
            <div>
              <div id="selectedFileName" class="font-semibold text-slate-700 text-sm"></div>
              <div class="text-xs text-slate-400">Шинээр сонгосон файл</div>
            </div>
          </div>
        </section>

        <section class="hw-card p-7 hidden" id="testFields">
          <h3 class="font-headline text-xl font-extrabold text-primary mb-4">Тестийн асуултууд</h3>
          <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 mb-4">
            Тест төрлийн даалгаврын асуултыг тусдаа засна. Доорх товчоор асуултын удирдлага руу орно.
          </div>
          <div class="grid md:grid-cols-2 gap-4">
            <div class="hw-test-card">
              <div class="text-sm text-slate-500">Бүртгэлтэй асуултын тоо</div>
              <div class="text-3xl font-extrabold text-indigo-700 mt-2"><?= e((string)count($questions)) ?></div>
            </div>
            <a href="test_question_manage.php?id=<?= e((string)$id) ?>" class="hw-test-card flex items-center justify-center font-bold text-indigo-700 hover:bg-indigo-50">
              Тестийн асуулт засах
            </a>
          </div>
        </section>
      </div>

      <aside class="space-y-5 lg:sticky lg:top-24">
        <section class="hw-card p-7">
          <h3 class="hw-side-title">Тохиргоо</h3>

          <div class="mb-5">
            <label class="hw-label">Даалгаврын төрөл</label>
            <select name="daalgavar_turul" id="daalgavarTurul" class="hw-select" required>
              <option value="Энгийн" <?= ($homework['daalgavar_turul'] ?? 'Энгийн') === 'Энгийн' ? 'selected' : '' ?>>Энгийн</option>
              <option value="Тест" <?= ($homework['daalgavar_turul'] ?? 'Энгийн') === 'Тест' ? 'selected' : '' ?>>Тест</option>
            </select>
          </div>

          <div class="mb-5">
            <label class="hw-label">Эхлэх огноо</label>
            <input name="ogson_ognoo" type="datetime-local" class="hw-input" value="<?= e(datetime_local_value($homework['ogson_ognoo'] ?? '')) ?>">
          </div>

          <div class="mb-5">
            <label class="hw-label">Дуусах хугацаа</label>
            <input name="duusah_ognoo" type="datetime-local" class="hw-input" value="<?= e(datetime_local_value($homework['duusah_ognoo'] ?? '')) ?>" required>
          </div>

          <div class="hw-divider"></div>

          <div>
            <label class="hw-label">Хамгийн их оноо</label>
            <div class="hw-score-wrap">
              <input name="hamgiin_ih_onoo" type="number" min="0" step="0.01" value="<?= e((string)$homework['hamgiin_ih_onoo']) ?>" required>
              <span>Оноо</span>
            </div>
          </div>

          <div class="hw-divider"></div>

          <div class="hw-toggle-row">
            <span>Хоцорсон хариу хүлээн авах</span>
            <label class="hw-switch"><input type="checkbox" name="late_allowed" value="1"><span class="hw-slider"></span></label>
          </div>
          <div class="hw-toggle-row">
            <span>Сурагчдад мэдэгдэх</span>
            <label class="hw-switch"><input type="checkbox" name="notify_students" value="1" checked><span class="hw-slider"></span></label>
          </div>
        </section>

        <section class="hw-card p-7">
          <h3 class="hw-side-title">Одоогийн мэдээлэл</h3>
          <div class="space-y-3 text-sm text-slate-700">
            <div><span class="font-medium">Хичээл:</span> <?= e($homework['hicheel_ner']) ?> / <?= e($homework['angi_ner']) ?></div>
            <div><span class="font-medium">Төрөл:</span> <?= e($homework['daalgavar_turul'] ?: 'Энгийн') ?></div>
            <div><span class="font-medium">Төлөв:</span> <?= e($homework['tuluv'] ?: '-') ?></div>
            <div><span class="font-medium">Эхлэх:</span> <?= e(format_dt($homework['ogson_ognoo'])) ?></div>
            <div><span class="font-medium">Дуусах:</span> <?= e(format_dt($homework['duusah_ognoo'])) ?></div>
            <div><span class="font-medium">Оноо:</span> <?= e((string)$homework['hamgiin_ih_onoo']) ?></div>
          </div>
        </section>

        <button class="hw-primary-btn" type="submit" name="save_action" value="publish">
          <span class="material-symbols-outlined">save</span>
          Өөрчлөлт хадгалах
        </button>
        <a href="teacher_homeworks.php" class="hw-cancel">Үйлдлийг цуцлах</a>
      </aside>
    </div>
  </form>
</div>

<script>
(function(){
  const turul = document.getElementById('daalgavarTurul');
  const attachmentSection = document.getElementById('attachmentSection');
  const testFields = document.getElementById('testFields');
  const fileInput = document.getElementById('fileInput');
  const selectedFile = document.getElementById('selectedFile');
  const selectedFileName = document.getElementById('selectedFileName');
  const videoBox = document.getElementById('videoBox');
  const videoInput = document.getElementById('videoInput');

  function refreshMode(){
    const isTest = turul.value === 'Тест';
    attachmentSection.classList.toggle('hidden', isTest);
    testFields.classList.toggle('hidden', !isTest);
  }

  turul?.addEventListener('change', refreshMode);
  fileInput?.addEventListener('change', function(){
    if (fileInput.files && fileInput.files[0]) {
      selectedFileName.textContent = fileInput.files[0].name;
      selectedFile.classList.remove('hidden');
    }
  });
  videoBox?.addEventListener('click', function(){
    videoInput.classList.toggle('hidden');
    if (!videoInput.classList.contains('hidden')) videoInput.focus();
  });
  refreshMode();
})();
</script>
<?php include __DIR__ . '/footer.php'; ?>
