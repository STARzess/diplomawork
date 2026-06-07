<?php
require_once __DIR__ . '/functions.php';
require_roles(['Сурагч']);
close_expired_homeworks();

$studentId = current_user()['id'];
$selected = (int)($_GET['daalgavar_id'] ?? 0);

$homeworks = all_rows('SELECT gd.daalgavar_id, gd.garchig, gd.daalgavar_turul, gd.duusah_ognoo, gd.hamgiin_ih_onoo, gd.ogson_ognoo, h.hicheel_ner FROM geriin_daalgavar gd JOIN hicheel_bagsh hb ON hb.hicheel_bagsh_id=gd.hicheel_bagsh_id JOIN suragch s ON s.angi_id=hb.angi_id JOIN hicheel h ON h.hicheel_id=hb.hicheel_id LEFT JOIN daalgavriin_hariu dh ON dh.daalgavar_id=gd.daalgavar_id AND dh.suragch_id=? WHERE s.suragch_id=? AND dh.hariu_id IS NULL AND gd.duusah_ognoo >= NOW() AND gd.tuluv="Идэвхтэй" ORDER BY gd.duusah_ognoo ASC', [$studentId, $studentId]);

$selectedHomework = null;
if ($selected) {
    $selectedHomework = one_row('SELECT gd.daalgavar_id, gd.garchig, gd.tailbar, gd.daalgavar_turul, gd.duusah_ognoo, gd.ogson_ognoo, gd.hamgiin_ih_onoo, gd.tuluv, h.hicheel_ner FROM geriin_daalgavar gd JOIN hicheel_bagsh hb ON hb.hicheel_bagsh_id=gd.hicheel_bagsh_id JOIN suragch s ON s.angi_id=hb.angi_id JOIN hicheel h ON h.hicheel_id=hb.hicheel_id LEFT JOIN daalgavriin_hariu dh ON dh.daalgavar_id=gd.daalgavar_id AND dh.suragch_id=? WHERE gd.daalgavar_id=? AND s.suragch_id=? AND dh.hariu_id IS NULL LIMIT 1', [$studentId, $selected, $studentId]);
    if ($selectedHomework && is_homework_overdue($selectedHomework['duusah_ognoo'])) {
        try { db()->prepare("UPDATE geriin_daalgavar SET tuluv='Хугацаа дууссан' WHERE daalgavar_id=?")->execute([$selected]); } catch (Throwable $e) {}
        flash('Энэ даалгаврын хугацаа дууссан тул хариу илгээх боломжгүй.', 'error');
        header('Location: homework_view.php?id=' . $selected);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $daalgavarId = (int)($_POST['daalgavar_id'] ?? 0);
    $homework = one_row('SELECT gd.daalgavar_id, gd.garchig, gd.daalgavar_turul, gd.duusah_ognoo, gd.hamgiin_ih_onoo, gd.tuluv, hb.bagsh_id FROM geriin_daalgavar gd JOIN hicheel_bagsh hb ON hb.hicheel_bagsh_id=gd.hicheel_bagsh_id JOIN suragch s ON s.angi_id=hb.angi_id LEFT JOIN daalgavriin_hariu dh ON dh.daalgavar_id=gd.daalgavar_id AND dh.suragch_id=? WHERE gd.daalgavar_id=? AND s.suragch_id=? AND dh.hariu_id IS NULL LIMIT 1', [$studentId, $daalgavarId, $studentId]);
    if (!$homework) {
        flash('Даалгавар олдсонгүй эсвэл өмнө нь хариу илгээсэн байна.', 'error');
        header('Location: student_homeworks.php');
        exit;
    }
    if (is_homework_overdue($homework['duusah_ognoo']) || $homework['tuluv'] !== 'Идэвхтэй') {
        try { db()->prepare("UPDATE geriin_daalgavar SET tuluv='Хугацаа дууссан' WHERE daalgavar_id=?")->execute([$daalgavarId]); } catch (Throwable $e) {}
        flash('Даалгаврын хугацаа дууссан тул хариу илгээх боломжгүй. Төлөв: Хоцорсон.', 'error');
        header('Location: homework_view.php?id=' . $daalgavarId);
        exit;
    }

    if (($homework['daalgavar_turul'] ?? 'Энгийн') === 'Тест') {
        $questions = all_rows('SELECT asuult_id, zuv_songolt FROM daalgavriin_asuult WHERE daalgavar_id=? ORDER BY asuult_id ASC', [$daalgavarId]);
        if (!$questions) {
            flash('Тестийн асуулт олдсонгүй.', 'error');
            header('Location: submission_create.php?daalgavar_id=' . $daalgavarId);
            exit;
        }

        $answers = $_POST['answer'] ?? [];
        $correct = 0;
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO daalgavriin_hariu (daalgavar_id, suragch_id, tekst_hariu, tuluv) VALUES (?, ?, ?, "Шалгагдсан")');
            $stmt->execute([$daalgavarId, $studentId, 'Тестийн хариу']);
            $hariuId = (int)$pdo->lastInsertId();

            foreach ($questions as $q) {
                $selectedChoice = (int)($answers[$q['asuult_id']] ?? 0);
                $pdo->prepare('INSERT INTO test_hariu (hariu_id, asuult_id, songolt_dugaar) VALUES (?, ?, ?)')
                    ->execute([$hariuId, $q['asuult_id'], $selectedChoice]);
                if ($selectedChoice > 0 && $selectedChoice === (int)$q['zuv_songolt']) $correct++;
            }

            $score = count($questions) > 0 ? round(((float)$homework['hamgiin_ih_onoo'] * $correct) / count($questions), 2) : 0;
            $comment = 'Тест автоматаар шалгагдсан. Зөв хариулт: ' . $correct . '/' . count($questions);
            $shalgasanBagshId = (int)($homework['bagsh_id'] ?? 0);
            if ($shalgasanBagshId <= 0) throw new RuntimeException('Тестийн даалгаварт холбогдсон багш олдсонгүй.');

            $pdo->prepare('INSERT INTO unelgee (hariu_id, shalgasan_bagsh_id, avsan_onoo, bagshiin_tailbar, unelsen_ognoo) VALUES (?, ?, ?, ?, NOW())')
                ->execute([$hariuId, $shalgasanBagshId, $score, $comment]);

            $pdo->commit();
            flash('Тестийн хариу илгээгдэж, автоматаар шалгагдлаа. Оноо: ' . format_score_with_letter($score));
            header('Location: homework_view.php?id=' . $daalgavarId);
            exit;
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash('Тестийн хариу илгээхэд алдаа гарлаа: ' . $e->getMessage(), 'error');
            header('Location: submission_create.php?daalgavar_id=' . $daalgavarId);
            exit;
        }
    }

    $text = trim($_POST['text_hariu'] ?? '');
    $video = trim($_POST['video_link'] ?? '');
    $filePath = upload_file_if_present('file');
    if ($daalgavarId && ($text || $video || $filePath)) {
        db()->prepare('INSERT INTO daalgavriin_hariu (daalgavar_id, suragch_id, tekst_hariu, file_zam, video_link, tuluv) VALUES (?, ?, ?, ?, ?, "Илгээгдсэн")')
            ->execute([$daalgavarId, $studentId, $text, $filePath, $video]);
        flash('Даалгаврын хариу амжилттай илгээгдлээ.');
        header('Location: homework_view.php?id=' . $daalgavarId);
        exit;
    }
    flash('Хариуны агуулга оруулна уу.', 'error');
}

$testQuestions = [];
if ($selectedHomework && ($selectedHomework['daalgavar_turul'] ?? 'Энгийн') === 'Тест') {
    $testQuestions = all_rows('SELECT a.asuult_id, a.asuult, s.songolt_dugaar, s.songolt_text FROM daalgavriin_asuult a JOIN daalgavriin_songolt s ON s.asuult_id=a.asuult_id WHERE a.daalgavar_id=? ORDER BY a.asuult_id ASC, s.songolt_dugaar ASC', [$selected]);
    $grouped = [];
    foreach ($testQuestions as $row) {
        $grouped[$row['asuult_id']]['asuult'] = $row['asuult'];
        $grouped[$row['asuult_id']]['songoltuud'][] = ['dugaar' => $row['songolt_dugaar'], 'text' => $row['songolt_text']];
    }
    $testQuestions = $grouped;
}

$pageTitle = 'Даалгаврын хариу илгээх';
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
.sub-stat-value{font-size:16px;font-weight:800;color:#0f172a;margin-top:4px}
.sub-stat-value.red{color:#dc2626}
.sub-tips{background:#f8fbff;border:1px solid #c7d2fe}
.sub-section-title{font-size:20px;font-weight:800;color:#0f2c8c;display:flex;align-items:center;gap:8px;margin-bottom:14px}
.sub-input,.sub-textarea,.sub-select{width:100%;border:1px solid #dbe2ea;background:#f8fafc;border-radius:12px;padding:15px 16px;font-size:16px;color:#0f172a}
.sub-textarea{min-height:140px;resize:vertical}
.sub-block{margin-bottom:28px}
.sub-file-zone{border:1px dashed #d5dbe5;border-radius:16px;background:#fbfcfe;padding:34px 20px;text-align:center;cursor:pointer;display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:190px;width:100%;box-sizing:border-box;position:relative;overflow:hidden}
.sub-file-zone:hover{background:#f8fafc}
.sub-file-zone input[type="file"]{display:none!important}
.sub-file-icon{width:56px;height:56px;border-radius:14px;background:#eef2ff;color:#1e2d9d;display:inline-flex;align-items:center;justify-content:center;font-size:28px;margin-bottom:10px}
.sub-file-note{font-size:13px;color:#64748b;margin-top:4px}
.sub-link-wrap{position:relative}
.sub-link-wrap .sub-input{padding-left:40px}
.sub-link-icon{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94a3b8}
.sub-actions{display:flex;justify-content:space-between;gap:18px;padding-top:22px;border-top:1px solid #e2e8f0}
.sub-btn{display:inline-flex;align-items:center;justify-content:center;border-radius:12px;padding:13px 26px;font-weight:700}
.sub-btn-primary{background:#162a95;color:#fff;box-shadow:0 8px 18px rgba(37,99,235,.18)}
.sub-btn-secondary{background:#fff;color:#334155;border:1px solid #cbd5e1}
.test-info{background:#fff8e6;border:1px solid #f6d98d;color:#9a5b00;border-radius:14px;padding:14px 16px;margin-bottom:18px}
.test-question{border:1px solid #e2e8f0;border-radius:16px;padding:18px 18px;background:#fff;margin-bottom:16px}
.test-choice{display:flex;align-items:center;gap:10px;padding:12px 14px;border:1px solid #e2e8f0;border-radius:12px;margin-top:10px;cursor:pointer}
.test-choice:hover{background:#f8fafc}
@media (max-width: 1024px){.submission-layout{grid-template-columns:1fr}}
</style>

<div class="space-y-6">
  <form method="post" enctype="multipart/form-data" id="submissionForm" class="space-y-6">
    <div class="sub-card sub-card-pad">
      <label class="block text-sm font-semibold text-slate-700 mb-2">Даалгавар сонгох</label>
      <select name="daalgavar_id" id="daalgavarSelect" class="sub-select" required onchange="window.location='submission_create.php?daalgavar_id='+this.value">
        <option value="">Даалгавар сонгох</option>
        <?php foreach ($homeworks as $hw): ?>
          <option value="<?= e((string)$hw['daalgavar_id']) ?>" <?= $selected === (int)$hw['daalgavar_id'] ? 'selected' : '' ?>>
            <?= e($hw['hicheel_ner'].' - '.$hw['garchig'].' / '.($hw['daalgavar_turul'] ?: 'Энгийн').' / Дуусах: '.format_dt($hw['duusah_ognoo'])) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="submission-layout">
      <div class="space-y-5">
        <div class="sub-card sub-card-pad">
          <div class="sub-assignment-head">
            <div class="sub-assignment-icon">📄</div>
            <div>
              <div class="text-2xl font-extrabold text-slate-900 leading-tight">
                <?= $selectedHomework ? e($selectedHomework['garchig']) : 'Даалгавар сонгоно уу' ?>
              </div>
              <div class="sub-muted mt-1">
                <?= $selectedHomework && !empty($selectedHomework['ogson_ognoo']) ? 'Нийтэлсэн: '.e(substr((string)$selectedHomework['ogson_ognoo'], 0, 10)) : 'Даалгаврын мэдээлэл энд харагдана' ?>
              </div>
            </div>
          </div>

          <?php if ($selectedHomework): ?>
            <div class="mt-5">
              <div class="sub-label">Тайлбар</div>
              <div class="text-slate-700 leading-8"><?= nl2br(e((string)$selectedHomework['tailbar'])) ?></div>
            </div>

            <div class="sub-stat-grid">
              <div>
                <div class="sub-stat-title">Оноо</div>
                <div class="sub-stat-value"><?= e((string)$selectedHomework['hamgiin_ih_onoo']) ?></div>
              </div>
              <div>
                <div class="sub-stat-title">Дуусах хугацаа</div>
                <div class="sub-stat-value red"><?= e(substr((string)$selectedHomework['duusah_ognoo'],0,10)) ?></div>
                <div class="text-xs text-slate-500 mt-1"><?= e(homework_time_text($selectedHomework['duusah_ognoo'])) ?></div>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <div class="sub-card sub-card-pad sub-tips">
          <div class="sub-section-title !text-lg !mb-3">Санамж</div>
          <ul class="list-disc pl-5 space-y-2 text-slate-600 text-sm">
            <li>Файлын хэмжээ 20MB-аас хэтрэхгүй байна.</li>
            <li>PDF, DOCX, ZIP зэрэг форматыг ашиглаж болно.</li>
            <li>Хугацаа дуусахаас өмнө хариугаа заавал илгээнэ үү.</li>
          </ul>
        </div>
      </div>

      <div class="sub-card sub-card-pad">
        <?php if ($selectedHomework && ($selectedHomework['daalgavar_turul'] ?? 'Энгийн') === 'Тест'): ?>
          <div class="sub-section-title">Тест бөглөх</div>
          <?php foreach ($testQuestions as $qid => $q): ?>
            <div class="test-question">
              <div class="font-bold text-slate-800 mb-2"><?= e($q['asuult']) ?></div>
              <?php foreach ($q['songoltuud'] as $choice): ?>
                <label class="test-choice">
                  <input type="radio" name="answer[<?= e((string)$qid) ?>]" value="<?= e((string)$choice['dugaar']) ?>" required>
                  <span><?= e($choice['text']) ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          <?php endforeach; ?>
          <div class="sub-actions">
            <a href="student_homeworks.php" class="sub-btn sub-btn-secondary">Буцах</a>
            <button class="sub-btn sub-btn-primary">Илгээх</button>
          </div>
        <?php else: ?>
          <div class="sub-block">
            <div class="sub-section-title">Хариу бичих</div>
            <textarea name="text_hariu" rows="6" class="sub-textarea" placeholder="Даалгаврын тайлбар эсвэл хариултаа энд бичнэ үү..."></textarea>
          </div>

          <div class="sub-block">
            <div class="sub-section-title">Файл хавсаргах</div>
            <label class="sub-file-zone" for="fileUpload">
              <div class="sub-file-icon">☁</div>
              <div class="font-bold text-slate-800">Файл чирэх эсвэл сонгох</div>
              <div class="sub-file-note">PDF, DOCX, ZIP (Max 20MB)</div>
              <input id="fileUpload" name="file" type="file" class="hidden" onchange="document.getElementById('selectedFileName').textContent = this.files[0] ? this.files[0].name : ''">
            </label>
            <div id="selectedFileName" class="text-sm text-slate-500 mt-3"></div>
          </div>

          <div class="sub-block">
            <div class="sub-section-title">Видео линк</div>
            <div class="sub-link-wrap">
              <span class="sub-link-icon">🔗</span>
              <input name="video_link" class="sub-input" placeholder="https://youtube.com/...">
            </div>
          </div>

          <div class="sub-actions justify-end">
            <button class="sub-btn sub-btn-primary">Илгээх</button>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </form>
</div>
<?php include __DIR__ . '/footer.php'; ?>
