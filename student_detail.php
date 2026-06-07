<?php
require_once __DIR__ . '/functions.php';
require_roles(['Админ','Сургалтын алба','Багш']);

$role = current_role();
$studentId = (int)($_GET['id'] ?? 0);
$canEditStudent = in_array($role, ['Админ','Сургалтын алба'], true);
$showEditForm = $canEditStudent && (($_GET['edit'] ?? '') === '1');
if ($studentId <= 0) {
    flash('Сурагчийн мэдээлэл олдсонгүй.', 'error');
    header('Location: student_list.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_student_detail') {
    if (!$canEditStudent) {
        flash('Сурагчийн мэдээлэл засах эрхгүй байна.', 'error');
        header('Location: student_detail.php?id=' . $studentId);
        exit;
    }

    $ovog = trim($_POST['ovog'] ?? '');
    $ner = trim($_POST['ner'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['nuuts_ug'] ?? '');
    $register = trim($_POST['register_dugaar'] ?? '');
    $huis = trim($_POST['huis'] ?? '');
    $torsonOgnoo = trim($_POST['torson_ognoo'] ?? '');
    $utas = trim($_POST['utas'] ?? '');
    $angiId = (int)($_POST['angi_id'] ?? 0);
    $tuluv = trim($_POST['tuluv'] ?? 'Идэвхтэй');

    if (!$ovog || !$ner || !$email || !$angiId) {
        flash('Овог, нэр, имэйл, анги зэрэг шаардлагатай талбаруудыг бөглөнө үү.', 'error');
        header('Location: student_detail.php?id=' . $studentId . '&edit=1');
        exit;
    }

    try {
        if ($password !== '') {
            db()->prepare('UPDATE suragch SET ovog=?, ner=?, email=?, nuuts_ug=?, register_dugaar=?, huis=?, torson_ognoo=?, utas=?, angi_id=?, tuluv=? WHERE suragch_id=?')
                ->execute([$ovog, $ner, $email, password_hash($password, PASSWORD_DEFAULT), $register ?: null, $huis ?: null, $torsonOgnoo ?: null, $utas ?: null, $angiId, $tuluv, $studentId]);
        } else {
            db()->prepare('UPDATE suragch SET ovog=?, ner=?, email=?, register_dugaar=?, huis=?, torson_ognoo=?, utas=?, angi_id=?, tuluv=? WHERE suragch_id=?')
                ->execute([$ovog, $ner, $email, $register ?: null, $huis ?: null, $torsonOgnoo ?: null, $utas ?: null, $angiId, $tuluv, $studentId]);
        }
        flash('Сурагчийн мэдээлэл амжилттай шинэчлэгдлээ.');
        header('Location: student_detail.php?id=' . $studentId);
        exit;
    } catch (Throwable $e) {
        flash('Мэдээлэл хадгалах үед алдаа гарлаа. Имэйл эсвэл регистр давхардсан эсэхийг шалгана уу.', 'error');
        header('Location: student_detail.php?id=' . $studentId . '&edit=1');
        exit;
    }
}

$params = [$studentId];
$sql = 'SELECT s.suragch_id, s.ovog, s.ner, s.email, s.utas, s.register_dugaar, s.huis, s.torson_ognoo, s.tuluv, s.burtgesen_ognoo,
               a.angi_id, a.angi_ner, a.tuvshin, a.surgaltiin_jil
        FROM suragch s
        LEFT JOIN angi a ON a.angi_id=s.angi_id
        WHERE s.suragch_id=?';
$student = one_row($sql, $params);
if (!$student) {
    flash('Сурагчийн мэдээлэл олдсонгүй.', 'error');
    header('Location: student_list.php');
    exit;
}

if ($role === 'Багш') {
    $allowed = count_value('SELECT COUNT(*) FROM hicheel_bagsh WHERE angi_id=? AND bagsh_id=?', [(int)$student['angi_id'], (int)current_user()['id']]);
    if ($allowed <= 0) {
        flash('Энэ сурагчийн дэлгэрэнгүй мэдээллийг харах эрхгүй байна.', 'error');
        header('Location: student_list.php');
        exit;
    }
}

$courseRows = all_rows('SELECT hb.hicheel_bagsh_id, h.hicheel_ner, h.hicheel_kod, aj.ovog AS bagsh_ovog, aj.ner AS bagsh_ner,
                              COUNT(DISTINCT gd.daalgavar_id) AS niit_daalgavar,
                              COUNT(DISTINCT dh.hariu_id) AS ilgeesen,
                              ROUND(AVG(u.avsan_onoo),2) AS dundaj_onoo
                       FROM hicheel_bagsh hb
                       JOIN hicheel h ON h.hicheel_id=hb.hicheel_id
                       LEFT JOIN ajiltan aj ON aj.ajiltan_id=hb.bagsh_id
                       LEFT JOIN geriin_daalgavar gd ON gd.hicheel_bagsh_id=hb.hicheel_bagsh_id
                       LEFT JOIN daalgavriin_hariu dh ON dh.daalgavar_id=gd.daalgavar_id AND dh.suragch_id=?
                       LEFT JOIN unelgee u ON u.hariu_id=dh.hariu_id
                       WHERE hb.angi_id=?
                       GROUP BY hb.hicheel_bagsh_id
                       ORDER BY h.hicheel_ner', [$studentId, (int)$student['angi_id']]);

$stats = one_row('SELECT COUNT(DISTINCT dh.hariu_id) AS ilgeesen,
                         COUNT(DISTINCT u.unelgee_id) AS unelgee_too,
                         ROUND(AVG(u.avsan_onoo),2) AS dundaj_onoo
                  FROM daalgavriin_hariu dh
                  LEFT JOIN unelgee u ON u.hariu_id=dh.hariu_id
                  WHERE dh.suragch_id=?', [$studentId]) ?: [];
$totalHomework = count_value('SELECT COUNT(*) FROM geriin_daalgavar gd JOIN hicheel_bagsh hb ON hb.hicheel_bagsh_id=gd.hicheel_bagsh_id WHERE hb.angi_id=?', [(int)$student['angi_id']]);
$submitted = (int)($stats['ilgeesen'] ?? 0);
$avgScore = (float)($stats['dundaj_onoo'] ?? 0);
$completion = $totalHomework > 0 ? min(100, round(($submitted / $totalHomework) * 100)) : 0;

$recentGrades = all_rows('SELECT gd.garchig, gd.daalgavar_turul, h.hicheel_ner, u.avsan_onoo, u.bagshiin_tailbar, u.unelsen_ognoo
                         FROM unelgee u
                         JOIN daalgavriin_hariu dh ON dh.hariu_id=u.hariu_id
                         JOIN geriin_daalgavar gd ON gd.daalgavar_id=dh.daalgavar_id
                         JOIN hicheel_bagsh hb ON hb.hicheel_bagsh_id=gd.hicheel_bagsh_id
                         JOIN hicheel h ON h.hicheel_id=hb.hicheel_id
                         WHERE dh.suragch_id=?
                         ORDER BY u.unelsen_ognoo DESC LIMIT 5', [$studentId]);

$homeworkRows = all_rows('SELECT gd.garchig, gd.daalgavar_turul, gd.duusah_ognoo, h.hicheel_ner, dh.ilgeesen_ognoo, dh.tuluv AS hariu_tuluv, u.avsan_onoo
                         FROM geriin_daalgavar gd
                         JOIN hicheel_bagsh hb ON hb.hicheel_bagsh_id=gd.hicheel_bagsh_id
                         JOIN hicheel h ON h.hicheel_id=hb.hicheel_id
                         LEFT JOIN daalgavriin_hariu dh ON dh.daalgavar_id=gd.daalgavar_id AND dh.suragch_id=?
                         LEFT JOIN unelgee u ON u.hariu_id=dh.hariu_id
                         WHERE hb.angi_id=?
                         ORDER BY gd.duusah_ognoo DESC LIMIT 8', [$studentId, (int)$student['angi_id']]);

function detail_grade_letter(float $score): string {
    if ($score >= 90) return 'A';
    if ($score >= 80) return 'B';
    if ($score >= 70) return 'C';
    if ($score >= 60) return 'D';
    return 'F';
}
function detail_status_for_homework(array $row): array {
    if (!empty($row['avsan_onoo'])) return ['Дүгнэгдсэн','bg-green-100 text-green-700'];
    if (!empty($row['ilgeesen_ognoo'])) return ['Илгээсэн','bg-blue-100 text-blue-700'];
    try {
        if (!empty($row['duusah_ognoo']) && new DateTime($row['duusah_ognoo']) < new DateTime()) return ['Хугацаа дууссан','bg-red-100 text-red-700'];
    } catch (Throwable $e) {}
    return ['Хүлээгдэж буй','bg-slate-100 text-slate-600'];
}

$classes = $canEditStudent ? all_rows('SELECT angi_id, angi_ner FROM angi ORDER BY tuvshin, angi_ner') : [];

$pageTitle = 'Сурагчийн дэлгэрэнгүй';
include __DIR__ . '/header.php';
?>
<style>
.detail-wrap{max-width:1180px;margin:0 auto}
.detail-hero-wrap{background:#fff;border:1px solid #e2e8f0;border-radius:28px;padding:28px 28px 22px;box-shadow:0 16px 40px rgba(15,23,42,.05);margin-bottom:28px}
.detail-hero-top{display:grid;grid-template-columns:180px 1fr 180px;gap:28px;align-items:center}
.avatar-box{width:170px;height:170px;border-radius:28px;background:linear-gradient(135deg,#e0e7ff,#eef2ff);display:flex;align-items:center;justify-content:center;font-size:72px;line-height:1;font-weight:900;color:#1a237e;border:6px solid #fff;box-shadow:0 10px 25px rgba(15,23,42,.08)}
.detail-badge{display:inline-flex;align-items:center;padding:7px 16px;border-radius:999px;font-size:12px;font-weight:800;background:#dcfce7;color:#166534;text-transform:uppercase;margin-bottom:12px}
.detail-name{font-size:46px;line-height:1.05;font-weight:900;color:#11146b;letter-spacing:-.04em;margin:0 0 12px}
.detail-main-info{display:flex;flex-direction:column;gap:12px}
.detail-main-row{display:flex;align-items:center;gap:12px;color:#475569;font-size:17px;font-weight:500}
.detail-main-row .material-symbols-outlined{font-size:26px;color:#475569}
.detail-action{display:flex;flex-direction:column;gap:14px;align-self:start;margin-top:22px}
.detail-btn{display:flex;align-items:center;justify-content:center;border-radius:14px;padding:15px 16px;font-weight:800;background:#1a237e;color:#fff;white-space:nowrap}
.detail-btn.secondary{background:#e5e7eb;color:#11146b}
.detail-extra-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-top:24px}
.detail-mini-card{background:#f8fafc;border:1px solid #dbe4f0;border-radius:18px;padding:18px 18px;display:flex;align-items:flex-start;gap:14px}
.detail-mini-icon{width:40px;height:40px;border-radius:12px;background:#eef2ff;color:#1a237e;display:flex;align-items:center;justify-content:center;flex:0 0 40px}
.detail-mini-icon .material-symbols-outlined{font-size:24px}
.detail-mini-label{font-size:12px;line-height:1.2;color:#64748b;font-weight:800;text-transform:uppercase;letter-spacing:.05em}
.detail-mini-value{font-size:16px;line-height:1.35;font-weight:800;color:#0f172a;margin-top:6px}
.detail-grid{display:grid;grid-template-columns:1fr 1fr 300px;gap:26px;margin-top:0}
.metric-card,.panel-card{background:#fff;border:1px solid #e2e8f0;border-radius:22px;box-shadow:0 12px 32px rgba(15,23,42,.05);padding:26px}
.metric-title{letter-spacing:.08em;text-transform:uppercase;color:#64748b;font-weight:700}.metric-value{font-size:30px;font-weight:900;color:#11146b;margin-top:8px}.progress-line{height:7px;border-radius:999px;background:#e5e7eb;margin-top:58px;overflow:hidden}.progress-line span{display:block;height:100%;background:#1a237e}.panel-title{font-size:22px;font-weight:900;color:#11146b;margin-bottom:16px}.activity-item{display:grid;grid-template-columns:28px 1fr;gap:12px;margin-bottom:18px}.activity-dot{width:28px;height:28px;border-radius:999px;background:#eef2ff;color:#1a237e;display:flex;align-items:center;justify-content:center}.wide-panel{background:#fff;border:1px solid #e2e8f0;border-radius:28px;margin-top:28px;padding:28px;box-shadow:0 14px 36px rgba(15,23,42,.05)}.clean-table{width:100%;font-size:14px}.clean-table th{text-align:left;color:#334155;text-transform:uppercase;letter-spacing:.08em;font-size:12px;padding:14px}.clean-table td{padding:14px;border-top:1px solid #e5e7eb}.pill{display:inline-flex;border-radius:999px;padding:6px 12px;font-weight:800;font-size:12px}.course-grid-mini{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.course-mini{background:#f8fafc;border-radius:16px;padding:18px;border:1px solid #e5e7eb}.course-mini h4{font-size:18px;font-weight:900;color:#11146b}.muted{color:#64748b}.inline-edit-card{background:#fff;border:1px solid #e2e8f0;border-radius:24px;padding:26px;margin-top:28px;box-shadow:0 12px 32px rgba(15,23,42,.05)}.inline-edit-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}.inline-edit-grid label{font-size:13px;font-weight:800;color:#334155}.inline-edit-grid input,.inline-edit-grid select{width:100%;border:1px solid #cbd5e1;border-radius:14px;padding:12px 14px;margin-top:7px}.inline-edit-actions{display:flex;gap:12px;justify-content:flex-end;margin-top:18px}.inline-primary{background:#1a237e;color:#fff;border-radius:12px;padding:12px 18px;font-weight:800}.inline-secondary{background:#e5e7eb;color:#11146b;border-radius:12px;padding:12px 18px;font-weight:800}
@media(max-width:1100px){.detail-hero-top,.detail-grid{grid-template-columns:1fr}.detail-extra-grid,.course-grid-mini,.inline-edit-grid{grid-template-columns:1fr 1fr}.avatar-box{width:140px;height:140px;font-size:58px}.detail-name{font-size:36px}.detail-action{margin-top:0}}
@media(max-width:700px){.detail-extra-grid,.course-grid-mini,.inline-edit-grid{grid-template-columns:1fr}.detail-hero-wrap{padding:20px}.detail-main-row{font-size:15px}.detail-name{font-size:30px}}
</style>
<div class="detail-wrap">
  <div class="detail-hero-wrap">
    <div class="detail-hero-top">
      <div class="avatar-box"><?= e(mb_substr($student['ner'] ?: 'С',0,1)) ?></div>
      <div>
        
        <h1 class="detail-name"><?= e($student['ovog'].' '.$student['ner']) ?></h1>
        <div class="detail-main-info">
          <div class="detail-main-row"><span class="material-symbols-outlined">star</span> <span><b>Анги:</b> <?= e((string)$student['angi_ner']) ?></span></div>
          <div class="detail-main-row"><span class="material-symbols-outlined">alternate_email</span> <span><?= e((string)$student['email']) ?></span></div>
          <div class="detail-main-row"><span class="material-symbols-outlined">badge</span> <span><b>Регистр:</b> <?= e((string)$student['register_dugaar']) ?></span></div>
        </div>
      </div>
      <div class="detail-action">
        <?php if ($canEditStudent): ?><a class="detail-btn" href="student_detail.php?id=<?= (int)$student['suragch_id'] ?>&edit=1">Профайл засах</a><?php endif; ?>
        <a class="detail-btn secondary" href="student_list.php">Жагсаалт руу</a>
      </div>
    </div>

    <div class="detail-extra-grid">
      <div class="detail-mini-card">
        <div class="detail-mini-icon"><span class="material-symbols-outlined">call</span></div>
        <div><div class="detail-mini-label">Утасны дугаар</div><div class="detail-mini-value"><?= e((string)($student['utas'] ?: 'Бүртгэгдээгүй')) ?></div></div>
      </div>
      <div class="detail-mini-card">
        <div class="detail-mini-icon"><span class="material-symbols-outlined">wc</span></div>
        <div><div class="detail-mini-label">Хүйс</div><div class="detail-mini-value"><?= e((string)($student['huis'] ?: 'Бүртгэгдээгүй')) ?></div></div>
      </div>
      <div class="detail-mini-card">
        <div class="detail-mini-icon"><span class="material-symbols-outlined">cake</span></div>
        <div><div class="detail-mini-label">Төрсөн огноо</div><div class="detail-mini-value"><?= e((string)($student['torson_ognoo'] ?: 'Бүртгэгдээгүй')) ?></div></div>
      </div>
      <div class="detail-mini-card">
        <div class="detail-mini-icon"><span class="material-symbols-outlined">verified_user</span></div>
        <div><div class="detail-mini-label">Төлөв</div><div class="detail-mini-value"><?= e((string)$student['tuluv']) ?></div></div>
      </div>
    </div>
  </div>
<?php if ($showEditForm): ?>
  <form method="post" class="inline-edit-card">
    <input type="hidden" name="action" value="update_student_detail">
    <h3 class="panel-title">Профайл засах</h3>
    <div class="inline-edit-grid">
      <label>Овог<input name="ovog" value="<?= e($student['ovog']) ?>" required></label>
      <label>Нэр<input name="ner" value="<?= e($student['ner']) ?>" required></label>
      <label>Имэйл<input type="email" name="email" value="<?= e($student['email']) ?>" required></label>
      <label>Регистр<input name="register_dugaar" value="<?= e((string)$student['register_dugaar']) ?>"></label>
      <label>Хүйс<select name="huis"><option value="">Сонгох</option><option value="Эр" <?= $student['huis']==='Эр'?'selected':'' ?>>Эр</option><option value="Эм" <?= $student['huis']==='Эм'?'selected':'' ?>>Эм</option></select></label>
      <label>Төрсөн огноо<input type="date" name="torson_ognoo" value="<?= e((string)$student['torson_ognoo']) ?>"></label>
      <label>Утас<input name="utas" value="<?= e((string)$student['utas']) ?>"></label>
      <label>Анги<select name="angi_id" required><?php foreach($classes as $c): ?><option value="<?= (int)$c['angi_id'] ?>" <?= (int)$student['angi_id']===(int)$c['angi_id']?'selected':'' ?>><?= e($c['angi_ner']) ?></option><?php endforeach; ?></select></label>
      <label>Төлөв<select name="tuluv"><option value="Идэвхтэй" <?= $student['tuluv']==='Идэвхтэй'?'selected':'' ?>>Идэвхтэй</option><option value="Идэвхгүй" <?= $student['tuluv']==='Идэвхгүй'?'selected':'' ?>>Идэвхгүй</option><option value="Төгссөн" <?= $student['tuluv']==='Төгссөн'?'selected':'' ?>>Төгссөн</option><option value="Шилжсэн" <?= $student['tuluv']==='Шилжсэн'?'selected':'' ?>>Шилжсэн</option></select></label>
      <label>Нууц үг солих<input type="password" name="nuuts_ug" placeholder="Хоосон үлдээвэл хэвээр"></label>
    </div>
    <div class="inline-edit-actions"><a class="inline-secondary" href="student_detail.php?id=<?= (int)$student['suragch_id'] ?>">Болих</a><button class="inline-primary">Хадгалах</button></div>
  </form>
  <?php endif; ?>

  <div class="detail-grid">
    <div class="metric-card"><div class="metric-title">Дундаж оноо</div><div class="metric-value"><?= e(number_format($avgScore,2)) ?></div><div class="progress-line"><span style="width:<?= min(100,$avgScore) ?>%"></span></div><p class="muted mt-6">Үсгэн үнэлгээ: <?= e(detail_grade_letter($avgScore)) ?></p></div>
    <div class="metric-card"><div class="metric-title">Гүйцэтгэл</div><div class="metric-value"><?= e((string)$completion) ?>%</div><div class="progress-line"><span style="width:<?= $completion ?>%;background:#16a34a"></span></div><p class="muted mt-6">Нийт <?= e((string)$totalHomework) ?> даалгавраас <?= e((string)$submitted) ?> илгээсэн.</p></div>
    <div class="panel-card"><h3 class="panel-title">Сүүлд авсан үнэлгээ</h3><?php if($recentGrades): foreach($recentGrades as $g): ?><div class="activity-item"><div class="activity-dot"><span class="material-symbols-outlined text-base">check</span></div><div><b><?= e($g['garchig']) ?></b><div class="muted"><?= e($g['hicheel_ner']) ?> • <?= e(number_format((float)$g['avsan_onoo'],0)) ?>/100</div></div></div><?php endforeach; else: ?><p class="muted">Одоогоор үнэлгээний мэдээлэл алга.</p><?php endif; ?></div>
  </div>

  <div class="wide-panel">
    <h3 class="panel-title">Хичээлүүд</h3>
    <div class="course-grid-mini">
      <?php foreach($courseRows as $c): ?>
        <div class="course-mini"><h4><?= e($c['hicheel_ner']) ?></h4><p class="muted mt-1"><?= e($c['hicheel_kod'] ?? '') ?> • Багш: <?= e(trim(($c['bagsh_ovog'] ?? '').' '.($c['bagsh_ner'] ?? ''))) ?></p><p class="mt-3"><b><?= e((string)$c['ilgeesen']) ?></b> / <?= e((string)$c['niit_daalgavar']) ?> даалгавар • Дундаж: <?= e((string)($c['dundaj_onoo'] ?? 0)) ?></p></div>
      <?php endforeach; ?>
      <?php if(!$courseRows): ?><p class="muted">Хичээлийн мэдээлэл алга.</p><?php endif; ?>
    </div>
  </div>

  <div class="wide-panel">
    <h3 class="panel-title">Хичээлийн даалгаврууд</h3>
    <table class="clean-table"><thead><tr><th>Даалгаврын нэр</th><th>Хичээл</th><th>Хугацаа</th><th>Төлөв</th><th>Үнэлгээ</th></tr></thead><tbody>
    <?php foreach($homeworkRows as $hw): [$st,$cl]=detail_status_for_homework($hw); ?><tr><td><b><?= e($hw['garchig']) ?></b></td><td><?= e($hw['hicheel_ner']) ?></td><td><?= e(format_dt($hw['duusah_ognoo'])) ?></td><td><span class="pill <?= e($cl) ?>"><?= e($st) ?></span></td><td><?= $hw['avsan_onoo']!==null ? e(number_format((float)$hw['avsan_onoo'],0)).'/100' : '-' ?></td></tr><?php endforeach; ?>
    <?php if(!$homeworkRows): ?><tr><td colspan="5" class="muted">Даалгаврын мэдээлэл алга.</td></tr><?php endif; ?></tbody></table>
  </div>
</div>
<?php include __DIR__ . '/footer.php'; ?>
