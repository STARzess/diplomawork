<?php
require_once __DIR__ . '/functions.php';
require_roles(['Сурагч']);
close_expired_homeworks();

$studentId = (int)current_user()['id'];
$hariuId = (int)($_GET['id'] ?? $_POST['hariu_id'] ?? 0);

if (!$hariuId) {
    flash('Засах хариу олдсонгүй.', 'error');
    header('Location: student_homeworks.php');
    exit;
}

$submission = one_row(
    'SELECT dh.hariu_id, dh.daalgavar_id, dh.suragch_id, dh.tekst_hariu, dh.file_zam, dh.video_link, dh.ilgeesen_ognoo, dh.tuluv,
            gd.garchig, gd.tailbar, \'Энгийн\' AS daalgavar_turul, gd.ogson_ognoo, gd.duusah_ognoo, gd.hamgiin_ih_onoo, gd.tuluv AS daalgavar_tuluv,
            h.hicheel_ner, a.angi_ner, u.unelgee_id, u.avsan_onoo
     FROM daalgavriin_hariu dh
     JOIN geriin_daalgavar gd ON gd.daalgavar_id = dh.daalgavar_id
     JOIN hicheel_bagsh hb ON hb.hicheel_bagsh_id = gd.hicheel_bagsh_id
     JOIN hicheel h ON h.hicheel_id = hb.hicheel_id
     JOIN angi a ON a.angi_id = hb.angi_id
     LEFT JOIN unelgee u ON u.hariu_id = dh.hariu_id
     WHERE dh.hariu_id = ? AND dh.suragch_id = ?
     LIMIT 1',
    [$hariuId, $studentId]
);

if (!$submission) {
    flash('Энэ хариуг засах эрхгүй байна.', 'error');
    header('Location: student_homeworks.php');
    exit;
}

if (!empty($submission['unelgee_id']) || $submission['avsan_onoo'] !== null) {
    flash('Багш шалгасан эсвэл тестийн хариу тул засах боломжгүй.', 'error');
    header('Location: homework_view.php?id=' . (int)$submission['daalgavar_id']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $guard = one_row('SELECT u.unelgee_id FROM unelgee u WHERE u.hariu_id=? LIMIT 1', [$hariuId]);
    if ($guard) {
        flash('Багш аль хэдийн шалгасан тул засах боломжгүй.', 'error');
        header('Location: homework_view.php?id=' . (int)$submission['daalgavar_id']);
        exit;
    }

    $text = trim($_POST['text_hariu'] ?? '');
    $video = trim($_POST['video_link'] ?? '');
    $removeCurrentFile = isset($_POST['remove_current_file']) && $_POST['remove_current_file'] === '1';
    $oldFile = (string)($submission['file_zam'] ?? '');
    $newFile = upload_file_if_present('file');

    if ($newFile !== '') {
        $filePath = $newFile;
        if ($oldFile !== '' && is_file(__DIR__ . '/' . $oldFile)) {
            @unlink(__DIR__ . '/' . $oldFile);
        }
    } elseif ($removeCurrentFile) {
        $filePath = '';
        if ($oldFile !== '' && is_file(__DIR__ . '/' . $oldFile)) {
            @unlink(__DIR__ . '/' . $oldFile);
        }
    } else {
        $filePath = $oldFile;
    }

    if ($text === '' && $video === '' && $filePath === '') {
        flash('Хариуны агуулга, файл эсвэл видео холбоос оруулна уу.', 'error');
        header('Location: submission_edit.php?id=' . $hariuId);
        exit;
    }

    db()->prepare('UPDATE daalgavriin_hariu SET tekst_hariu=?, file_zam=?, video_link=?, ilgeesen_ognoo=NOW(), tuluv="Илгээгдсэн" WHERE hariu_id=? AND suragch_id=?')
        ->execute([$text, $filePath, $video, $hariuId, $studentId]);

    flash('Илгээсэн хариу амжилттай засагдлаа.');
    header('Location: homework_view.php?id=' . (int)$submission['daalgavar_id']);
    exit;
}

$pageTitle = 'Хариу засах';
include __DIR__ . '/header.php';
?>
<style>
.submission-layout{display:grid;grid-template-columns:320px 1fr;gap:28px;align-items:start}
.sub-card{background:#fff;border:1px solid #e2e8f0;border-radius:18px;box-shadow:0 8px 24px rgba(15,23,42,.05)}
.sub-card-pad{padding:22px 24px}
.sub-assignment-head{display:flex;gap:14px;align-items:flex-start}
.sub-assignment-icon{width:64px;height:64px;border-radius:14px;background:#1e2d9d;color:#fff;display:flex;align-items:center;justify-content:center;font-size:28px;flex-shrink:0}
.sub-label{font-size:13px;font-weight:800;color:#0f2c8c;margin-bottom:10px}
.sub-muted{color:#64748b}
.sub-stat-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;padding-top:14px;border-top:1px solid #e2e8f0;margin-top:18px}
.sub-stat-title{font-size:12px;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.04em}
.sub-stat-value{font-size:16px;font-weight:800;color:#0f172a;margin-top:4px}.sub-stat-value.red{color:#dc2626}
.sub-section-title{font-size:20px;font-weight:800;color:#0f2c8c;display:flex;align-items:center;gap:8px;margin-bottom:14px}
.sub-input,.sub-textarea{width:100%;border:1px solid #dbe2ea;background:#f8fafc;border-radius:12px;padding:15px 16px;font-size:16px;color:#0f172a}.sub-textarea{min-height:170px;resize:vertical}
.sub-block{margin-bottom:28px}.sub-file-zone{border:1px dashed #d5dbe5;border-radius:16px;background:#fbfcfe;padding:34px 20px;text-align:center;cursor:pointer;display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:190px;width:100%;box-sizing:border-box;position:relative;overflow:hidden}.sub-file-zone:hover{background:#f8fafc}.sub-file-zone input[type="file"]{display:none!important}
.sub-file-icon{width:56px;height:56px;border-radius:14px;background:#eef2ff;color:#1e2d9d;display:inline-flex;align-items:center;justify-content:center;font-size:28px;margin-bottom:10px}.sub-file-note{font-size:13px;color:#64748b;margin-top:4px}.sub-link-wrap{position:relative}.sub-link-wrap .sub-input{padding-left:42px}.sub-link-icon{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94a3b8}
.sub-actions{display:flex;justify-content:space-between;gap:18px;padding-top:22px;border-top:1px solid #e2e8f0}.sub-btn{display:inline-flex;align-items:center;justify-content:center;border-radius:12px;padding:13px 26px;font-weight:700}.sub-btn-primary{background:#162a95;color:#fff;box-shadow:0 8px 18px rgba(37,99,235,.18)}.sub-btn-secondary{background:#fff;color:#334155;border:1px solid #cbd5e1}
.sub-tips{background:#f8fbff;border:1px solid #c7d2fe}@media (max-width:1024px){.submission-layout{grid-template-columns:1fr}}
</style>
<div class="space-y-6">
  <div>
    <h1 class="page-title">Илгээсэн хариу засах</h1>
    <p class="page-subtitle mt-1">Багш үнэлгээ тавиагүй үед л илгээсэн хариугаа засах боломжтой.</p>
  </div>

  <form method="post" enctype="multipart/form-data" class="submission-layout">
    <input type="hidden" name="hariu_id" value="<?= e((string)$hariuId) ?>">
    <div class="space-y-5">
      <div class="sub-card sub-card-pad">
        <div class="sub-assignment-head">
          <div class="sub-assignment-icon">📄</div>
          <div>
            <div class="text-2xl font-extrabold text-slate-900 leading-tight"><?= e($submission['garchig']) ?></div>
            <div class="sub-muted mt-1"><?= e($submission['hicheel_ner']) ?> · <?= e($submission['angi_ner']) ?></div>
          </div>
        </div>
        <div class="mt-5">
          <div class="sub-label">Даалгаврын тайлбар</div>
          <div class="text-slate-700 leading-8"><?= nl2br(e((string)$submission['tailbar'])) ?></div>
        </div>
        <div class="sub-stat-grid">
          <div><div class="sub-stat-title">Оноо</div><div class="sub-stat-value"><?= e((string)$submission['hamgiin_ih_onoo']) ?> дээд</div></div>
          <div><div class="sub-stat-title">Дуусах хугацаа</div><div class="sub-stat-value red"><?= e(substr((string)$submission['duusah_ognoo'],0,10)) ?></div><div class="text-xs text-slate-500 mt-1"><?= e(homework_time_text($submission['duusah_ognoo'])) ?></div></div>
        </div>
      </div>
      <div class="sub-card sub-card-pad sub-tips">
        <div class="sub-section-title !text-lg !mb-3">Санамж</div>
        <ul class="list-disc pl-5 space-y-2 text-slate-600 text-sm">
          <li>Багш шалгасны дараа хариу засах боломжгүй.</li>
          <li>Шинэ файл сонгохгүй бол өмнөх файл хэвээр үлдэнэ.</li>
          <li>Засвар хийсний дараа илгээсэн огноо шинэчлэгдэнэ.</li>
        </ul>
      </div>
    </div>

    <div class="sub-card sub-card-pad">
      <div class="sub-block">
        <div class="sub-section-title">Хариу бичих</div>
        <textarea name="text_hariu" rows="7" class="sub-textarea" placeholder="Хариултаа энд бичнэ үү...\"><?= e((string)$submission['tekst_hariu']) ?></textarea>
      </div>

      <div class="sub-block">
        <div class="sub-section-title">Файл хавсаргах</div>
        <?php if (!empty($submission['file_zam'])): ?>
          <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
              <span class="font-semibold text-slate-700">Одоогийн файл:</span>
              <a class="text-indigo-700 underline ml-1" href="<?= e($submission['file_zam']) ?>" target="_blank"><?= e(display_file_name($submission['file_zam'])) ?></a>
            </div>
            <label class="inline-flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-red-700 font-semibold cursor-pointer">
              <input type="checkbox" name="remove_current_file" value="1" class="rounded border-red-300 text-red-600 focus:ring-red-500">
              Одоогийн файлыг устгах
            </label>
          </div>
        <?php endif; ?>
        <label class="sub-file-zone" for="fileUpload">
          <div class="sub-file-icon">☁</div>
          <div class="font-bold text-slate-800">Шинэ файл сонгох</div>
          <div class="sub-file-note">PDF, DOCX, ZIP</div>
          <input id="fileUpload" name="file" type="file" onchange="document.getElementById('selectedFileName').textContent = this.files[0] ? this.files[0].name : ''">
        </label>
        <div id="selectedFileName" class="text-sm text-slate-500 mt-3"></div>
      </div>

      <div class="sub-block">
        <div class="sub-section-title">Видео линк</div>
        <div class="sub-link-wrap"><span class="sub-link-icon">🔗</span><input name="video_link" class="sub-input" value="<?= e((string)$submission['video_link']) ?>" placeholder="https://youtube.com/..."></div>
      </div>

      <div class="sub-actions">
        <a href="homework_view.php?id=<?= e((string)$submission['daalgavar_id']) ?>" class="sub-btn sub-btn-secondary">Буцах</a>
        <button class="sub-btn sub-btn-primary">Өөрчлөлт хадгалах</button>
      </div>
    </div>
  </form>
</div>
<?php include __DIR__ . '/footer.php'; ?>
