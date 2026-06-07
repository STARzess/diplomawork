<?php
require_once __DIR__ . '/functions.php';
ensure_gamified_homework_schema();
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
    $video = trim($_POST['video_link'] ?? '');
    $togloomchilson = !empty($_POST['togloomchilson']) ? 1 : 0;
    $togloomTurul = trim($_POST['togloom_turul'] ?? 'mission');
    $togloomZorilgo = trim($_POST['togloom_zorilgo'] ?? '');
    $togloomAlham = trim($_POST['togloom_alham'] ?? '');
    $odShagnal = max(1, min(5, (int)($_POST['od_shagnal'] ?? 3)));
    $turshlagaOnoo = max(0, (int)($_POST['turshlaga_onoo'] ?? 10));
    $tavDuudlaga = trim($_POST['tav_duudlaga'] ?? '');
    
    $tuluv = 'Идэвхтэй';
    $filePath = '';

    $filePath = upload_file_if_present('file');

    if ($hicheelBagshId && $garchig && $duusah) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $ogsonSql = $ogson !== '' ? $ogson : date('Y-m-d H:i:s');
            $stmt = $pdo->prepare('INSERT INTO geriin_daalgavar (hicheel_bagsh_id, garchig, tailbar, togloomchilson, togloom_turul, togloom_zorilgo, togloom_alham, od_shagnal, turshlaga_onoo, tav_duudlaga, ogson_ognoo, duusah_ognoo, hamgiin_ih_onoo, tuluv) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$hicheelBagshId, $garchig, $tailbar, $togloomchilson, $togloomTurul, $togloomZorilgo, $togloomAlham, $odShagnal, $turshlagaOnoo, $tavDuudlaga, $ogsonSql, $duusah, $onoo, $tuluv]);
            $daalgavarId = (int)$pdo->lastInsertId();

            if ($filePath || $video) {
                $pdo->prepare('INSERT INTO daalgavar_havsralt (daalgavar_id, file_ner, file_zam, video_link, turul) VALUES (?, ?, ?, ?, ?)')
                    ->execute([$daalgavarId, $filePath ? basename($filePath) : null, $filePath ?: null, $video ?: null, $video ? 'Видео' : 'Файл']);
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
  .game-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; }
  .game-choice { border:1px solid #dbe3ef; background:#fff; border-radius:10px; padding:14px; cursor:pointer; display:flex; gap:10px; align-items:flex-start; }
  .game-choice input { margin-top:4px; }
  .game-choice strong { display:block; color:#111c7a; font-size:14px; }
  .game-choice span { display:block; color:#64748b; font-size:12px; margin-top:3px; line-height:1.4; }
  .game-note { border-radius:12px; background:#f0f9ff; border:1px solid #bae6fd; color:#0f5280; padding:14px 16px; font-size:13px; line-height:1.6; }
  @media (max-width: 768px) { .game-grid { grid-template-columns:1fr; } }
</style>

<div class="homework-create-page space-y-8">
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
            <input name="garchig" class="hw-input" placeholder="Жишээ: Гэрийн даалгавар #1" required>
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

        <section class="hw-card p-7 space-y-6">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
              <h3 class="font-headline text-xl font-extrabold text-primary flex items-center gap-2">
                <span class="material-symbols-outlined">extension</span>
                1-5-р ангийн тоглоомчилсон даалгавар
              </h3>
              <p class="text-sm text-slate-500 mt-2">Бага ангийн хүүхдэд даалгаврыг аялал, шат, од, XP оноотойгоор харуулна.</p>
            </div>
            <label class="hw-switch" title="Тоглоомчилсон хэлбэрээр харуулах">
              <input type="checkbox" name="togloomchilson" value="1" checked>
              <span class="hw-slider"></span>
            </label>
          </div>

          <div class="game-note">Зөвлөмж: 1-5-р ангид нэг даалгаврыг 3-5 богино алхамтай, эерэг урамшууллын үгтэй оруулбал хүүхэд өөрөө хийхэд илүү ойлгомжтой байна.</div>

          <div>
            <label class="hw-label">Тоглоомын хэлбэр</label>
            <div class="game-grid">
              <label class="game-choice">
                <input type="radio" name="togloom_turul" value="mission" checked>
                <span><strong>Аялал</strong><span>Алхам алхмаар биелүүлэх даалгавар</span></span>
              </label>
              <label class="game-choice">
                <input type="radio" name="togloom_turul" value="quiz">
                <span><strong>Асуулт</strong><span>Богино асуултаар ахих хэлбэр</span></span>
              </label>
              <label class="game-choice">
                <input type="radio" name="togloom_turul" value="puzzle">
                <span><strong>Бодлого</strong><span>Таавар, бодлого тайлах мэдрэмж</span></span>
              </label>
            </div>
          </div>

          <div>
            <label class="hw-label">Хүүхдэд харагдах зорилго</label>
            <input name="togloom_zorilgo" class="hw-input" placeholder="Жишээ: Өнөөдөр 5 шинэ үг сурч, од цуглуулъя">
          </div>

          <div>
            <label class="hw-label">Тоглоомын алхмууд</label>
            <textarea name="togloom_alham" class="hw-textarea" style="min-height:120px" placeholder="1. Даалгавраа уншина&#10;2. 3 бодлого бодно&#10;3. Хариугаа зургаар эсвэл текстээр илгээнэ"></textarea>
          </div>

          <div class="grid md:grid-cols-3 gap-4">
            <div>
              <label class="hw-label">Од</label>
              <input name="od_shagnal" type="number" min="1" max="5" value="3" class="hw-input">
            </div>
            <div>
              <label class="hw-label">XP оноо</label>
              <input name="turshlaga_onoo" type="number" min="0" value="10" class="hw-input">
            </div>
            <div>
              <label class="hw-label">Урамшууллын үг</label>
              <input name="tav_duudlaga" class="hw-input" placeholder="Чи чадна!">
            </div>
          </div>
        </section>

      </div>

      <aside class="space-y-5 lg:sticky lg:top-24">
        <section class="hw-card p-7">
          <h3 class="hw-side-title">Тохиргоо</h3>

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
        </section>

        <button class="hw-primary-btn" type="submit" name="save_action" value="publish">
          <span class="material-symbols-outlined">send</span>
          Бүртгэх
        </button>
        <a href="teacher_homeworks.php" class="hw-cancel">Үйлдлийг цуцлах</a>
      </aside>
    </div>
  </form>
</div>

<script>
(function(){
  const fileInput = document.getElementById('fileInput');
  const selectedFile = document.getElementById('selectedFile');
  const selectedFileName = document.getElementById('selectedFileName');
  const videoBox = document.getElementById('videoBox');
  const videoInput = document.getElementById('videoInput');
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
})();
</script>
<?php include __DIR__ . '/footer.php'; ?>
