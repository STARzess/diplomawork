<?php
require_once __DIR__ . '/functions.php';
require_roles(['Багш']);

$teacherId = (int)current_user()['id'];
$teacherCourses = all_rows(
    'SELECT hb.hicheel_bagsh_id, h.hicheel_kod, h.hicheel_ner, a.angi_ner, hb.surgaltiin_jil
     FROM hicheel_bagsh hb
     JOIN hicheel h ON h.hicheel_id = hb.hicheel_id
     JOIN angi a ON a.angi_id = hb.angi_id
     WHERE hb.bagsh_id = ?
     ORDER BY h.hicheel_ner, a.angi_ner',
    [$teacherId]
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hicheelBagshId = (int)($_POST['hicheel_bagsh_id'] ?? 0);
    $garchig = trim($_POST['garchig'] ?? '');
    $tailbar = trim($_POST['tailbar'] ?? '');
    $ogson = trim($_POST['ogson_ognoo'] ?? '');
    $duusah = trim($_POST['duusah_ognoo'] ?? '');
    $onoo = (float)($_POST['hamgiin_ih_onoo'] ?? 100);
    $turul = trim($_POST['daalgavar_turul'] ?? 'Энгийн');
    $video = trim($_POST['video_link'] ?? '');
    
    $tuluv = 'Идэвхтэй';
    $filePath = '';

    if (!in_array($turul, ['Энгийн', 'Тест'], true)) {
        $turul = 'Энгийн';
    }

    if ($turul === 'Тест') {
        $video = '';
        if (!empty($_FILES['file']['name'])) {
            flash('Тест төрлийн даалгаварт файл эсвэл видео хавсаргах боломжгүй.', 'error');
            header('Location: homework_create.php');
            exit;
        }
    } else {
        $filePath = upload_file_if_present('file');
    }

    if ($hicheelBagshId && $garchig && $duusah) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $ogsonSql = $ogson !== '' ? $ogson : date('Y-m-d H:i:s');
            $stmt = $pdo->prepare('INSERT INTO geriin_daalgavar (hicheel_bagsh_id, garchig, tailbar, daalgavar_turul, ogson_ognoo, duusah_ognoo, hamgiin_ih_onoo, tuluv) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$hicheelBagshId, $garchig, $tailbar, $turul, $ogsonSql, $duusah, $onoo, $tuluv]);
            $daalgavarId = (int)$pdo->lastInsertId();

            if ($turul === 'Энгийн') {
                if ($filePath || $video) {
                    $pdo->prepare('INSERT INTO daalgavar_havsralt (daalgavar_id, file_ner, file_zam, video_link, turul) VALUES (?, ?, ?, ?, ?)')
                        ->execute([$daalgavarId, $filePath ? basename($filePath) : null, $filePath ?: null, $video ?: null, $video ? 'Видео' : 'Файл']);
                }
            } else {
                $asuultuud = $_POST['asuult'] ?? [];
                $songolt1 = $_POST['songolt1'] ?? [];
                $songolt2 = $_POST['songolt2'] ?? [];
                $songolt3 = $_POST['songolt3'] ?? [];
                $songolt4 = $_POST['songolt4'] ?? [];
                $zuv = $_POST['zuv_songolt'] ?? [];
                $validCount = 0;
                foreach ($asuultuud as $i => $asuult) {
                    $asuult = trim((string)$asuult);
                    $s1 = trim((string)($songolt1[$i] ?? ''));
                    $s2 = trim((string)($songolt2[$i] ?? ''));
                    $s3 = trim((string)($songolt3[$i] ?? ''));
                    $s4 = trim((string)($songolt4[$i] ?? ''));
                    $z = (int)($zuv[$i] ?? 0);
                    if ($asuult === '' && $s1 === '' && $s2 === '' && $s3 === '' && $s4 === '') {
                        continue;
                    }
                    if ($asuult === '' || $s1 === '' || $s2 === '' || $s3 === '' || $s4 === '' || $z < 1 || $z > 4) {
                        throw new RuntimeException('Тестийн асуулт бүр 4 сонголт, зөв хариулттай байх ёстой.');
                    }
                    $pdo->prepare('INSERT INTO daalgavriin_asuult (daalgavar_id, asuult, zuv_songolt) VALUES (?, ?, ?)')
                        ->execute([$daalgavarId, $asuult, $z]);
                    $asuultId = (int)$pdo->lastInsertId();
                    foreach ([$s1, $s2, $s3, $s4] as $idx => $choice) {
                        $pdo->prepare('INSERT INTO daalgavriin_songolt (asuult_id, songolt_dugaar, songolt_text) VALUES (?, ?, ?)')
                            ->execute([$asuultId, $idx + 1, $choice]);
                    }
                    $validCount++;
                }
                if ($validCount === 0) {
                    throw new RuntimeException('Тест төрлийн даалгаварт дор хаяж 1 асуулт шаардлагатай.');
                }
            }

            $pdo->commit();
            flash('Гэрийн даалгавар амжилттай бүртгэгдлээ.');
            header('Location: teacher_homeworks.php');
            exit;
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash($e->getMessage() ?: 'Даалгавар бүртгэхэд алдаа гарлаа.', 'error');
            header('Location: homework_create.php');
            exit;
        }
    }
    flash('Хичээл, гарчиг, дуусах хугацааг заавал бөглөнө үү.', 'error');
}

$pageTitle = 'Гэрийн даалгавар бүртгэх';
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
  .hw-date { padding-right:12px; }
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
    <h3 class="font-headline text-3xl font-extrabold text-primary">Гэрийн даалгавар бүртгэх</h3>
    <p class="text-slate-500 mt-2">Сурагчдад зориулсан шинэ даалгаврын мэдээллийг оруулах.</p>
  </div>

  <form method="post" enctype="multipart/form-data" id="homeworkForm">
    <div class="grid lg:grid-cols-[1fr_310px] gap-8 items-start">
      <div class="space-y-7">
        <section class="hw-card p-7 space-y-6">
          <div>
            <label class="hw-label">Хичээл сонгох</label>
            <select name="hicheel_bagsh_id" class="hw-select" required>
              <option value="">Сонгох...</option>
              <?php foreach ($teacherCourses as $c): ?>
                <option value="<?= e((string)$c['hicheel_bagsh_id']) ?>">
                  <?= e(($c['hicheel_kod'] ? $c['hicheel_kod'].' · ' : '').$c['hicheel_ner'].' / '.$c['angi_ner'].' / '.$c['surgaltiin_jil']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label class="hw-label">Гарчиг</label>
            <input name="garchig" class="hw-input" placeholder="Жишээ: Лаборатори №4 - Суурийн эффект" required>
          </div>

          <div>
            <label class="hw-label">Тайлбар</label>
            <textarea name="tailbar" class="hw-textarea" placeholder="Даалгаврын заавар, шаардлагуудыг энд дэлгэрэнгүй бичнэ үү..."></textarea>
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
              <span class="hw-attach-title">Файл хуулах</span>
              <span class="hw-attach-help">PDF, DOCX, ZIP (Max 50MB)</span>
            </label>
            <div class="hw-attach-box" id="videoBox">
              <span class="hw-attach-icon"><span class="material-symbols-outlined">videocam</span></span>
              <span class="hw-attach-title">Видео эсвэл Линк</span>
              <span class="hw-attach-help">YouTube, Drive холбоос</span>
            </div>
          </div>
          <input name="video_link" id="videoInput" class="hw-input mt-4 hidden" placeholder="https://...">
          <div id="selectedFile" class="hw-file-row hidden">
            <span class="material-symbols-outlined">description</span>
            <div>
              <div id="selectedFileName" class="font-semibold text-slate-700 text-sm"></div>
              <div class="text-xs text-slate-400">Сонгосон файл</div>
            </div>
          </div>
        </section>

        <section class="hw-card p-7 hidden" id="testFields">
          <h3 class="font-headline text-xl font-extrabold text-primary mb-4">Тестийн асуултууд</h3>
          <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 mb-4">
            Тест төрлийн даалгаварт файл, видео хавсаргах боломжгүй. Асуулт бүр 4 сонголттой байна.
          </div>
          <div id="questionsWrap" class="space-y-4"></div>
          <button type="button" id="addQuestionBtn" class="w-full rounded-xl border border-slate-300 bg-white py-3 font-medium text-slate-700 mt-4">Асуулт нэмэх</button>
        </section>
      </div>

      <aside class="space-y-5 lg:sticky lg:top-24">
        <section class="hw-card p-7">
          <h3 class="hw-side-title">Тохиргоо</h3>

          <div class="mb-5">
            <label class="hw-label">Даалгаврын төрөл</label>
            <select name="daalgavar_turul" id="daalgavarTurul" class="hw-select" required>
              <option value="Энгийн">Энгийн</option>
              <option value="Тест">Тест</option>
            </select>
          </div>

          <div class="mb-5">
            <label class="hw-label">Эхлэх огноо</label>
            <input name="ogson_ognoo" type="datetime-local" class="hw-input hw-date">
          </div>

          <div class="mb-5">
            <label class="hw-label">Дуусах хугацаа</label>
            <input name="duusah_ognoo" type="datetime-local" class="hw-input hw-date" required>
          </div>

          <div class="hw-divider"></div>

          <div>
            <label class="hw-label">Хамгийн их оноо</label>
            <div class="hw-score-wrap">
              <input name="hamgiin_ih_onoo" type="number" min="0" step="0.01" value="100" required>
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

        <button class="hw-primary-btn" type="submit" name="save_action" value="publish">
          <span class="material-symbols-outlined">send</span>
          Илгээх (Нийтлэх)
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
  const wrap = document.getElementById('questionsWrap');
  const addBtn = document.getElementById('addQuestionBtn');
  const fileInput = document.getElementById('fileInput');
  const selectedFile = document.getElementById('selectedFile');
  const selectedFileName = document.getElementById('selectedFileName');
  const videoBox = document.getElementById('videoBox');
  const videoInput = document.getElementById('videoInput');
  let idx = 0;

  function questionTemplate(i){
    return `
      <div class="hw-test-card space-y-3">
        <div class="flex items-center justify-between">
          <h4 class="font-semibold">Асуулт ${i+1}</h4>
          <button type="button" class="remove-question text-sm text-red-600">Устгах</button>
        </div>
        <textarea name="asuult[]" rows="2" class="w-full rounded-xl border-slate-300 px-4 py-3" placeholder="Асуулт оруулах"></textarea>
        <div class="grid md:grid-cols-2 gap-3">
          <input name="songolt1[]" class="w-full rounded-xl border-slate-300 px-4 py-3" placeholder="1-р сонголт">
          <input name="songolt2[]" class="w-full rounded-xl border-slate-300 px-4 py-3" placeholder="2-р сонголт">
          <input name="songolt3[]" class="w-full rounded-xl border-slate-300 px-4 py-3" placeholder="3-р сонголт">
          <input name="songolt4[]" class="w-full rounded-xl border-slate-300 px-4 py-3" placeholder="4-р сонголт">
        </div>
        <select name="zuv_songolt[]" class="w-full rounded-xl border-slate-300 px-4 py-3">
          <option value="">Зөв хариулт сонгох</option>
          <option value="1">1-р сонголт</option>
          <option value="2">2-р сонголт</option>
          <option value="3">3-р сонголт</option>
          <option value="4">4-р сонголт</option>
        </select>
      </div>`;
  }

  function addQuestion(){
    const holder = document.createElement('div');
    holder.innerHTML = questionTemplate(idx);
    wrap.appendChild(holder.firstElementChild);
    idx++;
  }

  function refreshMode(){
    const isTest = turul.value === 'Тест';
    attachmentSection.classList.toggle('hidden', isTest);
    testFields.classList.toggle('hidden', !isTest);
    if (isTest && wrap.children.length === 0) addQuestion();
  }

  addBtn?.addEventListener('click', addQuestion);
  wrap?.addEventListener('click', function(e){
    if (e.target.classList.contains('remove-question')) {
      const cards = wrap.querySelectorAll('.hw-test-card');
      if (cards.length > 1) e.target.closest('.hw-test-card').remove();
    }
  });

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
