<?php
require_once __DIR__ . '/functions.php';
require_roles(['Админ','Сургалтын алба']);

$employeeId = (int)($_GET['id'] ?? 0);
$showEditForm = (($_GET['edit'] ?? '') === '1');
if ($employeeId <= 0) {
    flash('Ажилтны мэдээлэл олдсонгүй.', 'error');
    header('Location: employee_list.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_employee_detail') {
    $ovog = trim($_POST['ovog'] ?? '');
    $ner = trim($_POST['ner'] ?? '');
    $huis = trim($_POST['huis'] ?? '');
    $utas = trim($_POST['utas'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['nuuts_ug'] ?? '');
    $erkhId = (int)($_POST['erkh_id'] ?? 0);
    $tuluv = trim($_POST['tuluv'] ?? 'Идэвхтэй');

    if (!$ovog || !$ner || !$email || !$erkhId) {
        flash('Овог, нэр, имэйл, эрхийг заавал бөглөнө үү.', 'error');
        header('Location: employee_detail.php?id=' . $employeeId . '&edit=1');
        exit;
    }

    try {
        if ($password !== '') {
            db()->prepare('UPDATE ajiltan SET ovog=?, ner=?, huis=?, utas=?, email=?, nuuts_ug=?, erkh_id=?, tuluv=? WHERE ajiltan_id=?')
                ->execute([$ovog, $ner, $huis ?: null, $utas ?: null, $email, password_hash($password, PASSWORD_DEFAULT), $erkhId, $tuluv, $employeeId]);
        } else {
            db()->prepare('UPDATE ajiltan SET ovog=?, ner=?, huis=?, utas=?, email=?, erkh_id=?, tuluv=? WHERE ajiltan_id=?')
                ->execute([$ovog, $ner, $huis ?: null, $utas ?: null, $email, $erkhId, $tuluv, $employeeId]);
        }
        flash('Ажилтны мэдээлэл амжилттай шинэчлэгдлээ.');
        header('Location: employee_detail.php?id=' . $employeeId);
        exit;
    } catch (Throwable $e) {
        flash('Мэдээлэл хадгалах үед алдаа гарлаа. Имэйл давхардсан эсэхийг шалгана уу.', 'error');
        header('Location: employee_detail.php?id=' . $employeeId . '&edit=1');
        exit;
    }
}
$employee = one_row('SELECT a.ajiltan_id, a.ovog, a.ner, a.huis, a.utas, a.email, a.tuluv, a.burtgesen_ognoo, e.erkh_ner
                     FROM ajiltan a JOIN erkh e ON e.erkh_id=a.erkh_id WHERE a.ajiltan_id=?', [$employeeId]);
if (!$employee) {
    flash('Ажилтны мэдээлэл олдсонгүй.', 'error');
    header('Location: employee_list.php');
    exit;
}
$isTeacher = $employee['erkh_ner'] === 'Багш';
$classes = $isTeacher ? all_rows('SELECT hb.hicheel_bagsh_id, a.angi_ner, a.tuvshin, h.hicheel_ner, COUNT(DISTINCT s.suragch_id) AS suragch_too, COUNT(DISTINCT gd.daalgavar_id) AS daalgavar_too
                                  FROM hicheel_bagsh hb
                                  JOIN angi a ON a.angi_id=hb.angi_id
                                  JOIN hicheel h ON h.hicheel_id=hb.hicheel_id
                                  LEFT JOIN suragch s ON s.angi_id=a.angi_id AND s.tuluv="Идэвхтэй"
                                  LEFT JOIN geriin_daalgavar gd ON gd.hicheel_bagsh_id=hb.hicheel_bagsh_id
                                  WHERE hb.bagsh_id=? GROUP BY hb.hicheel_bagsh_id ORDER BY a.angi_ner,h.hicheel_ner', [$employeeId]) : [];
$teacherStats = $isTeacher ? (one_row('SELECT COUNT(DISTINCT hb.hicheel_bagsh_id) AS class_count, COUNT(DISTINCT gd.daalgavar_id) AS homework_count, COUNT(DISTINCT dh.hariu_id) AS submission_count, ROUND(AVG(u.avsan_onoo),2) AS avg_score
                                      FROM hicheel_bagsh hb
                                      LEFT JOIN geriin_daalgavar gd ON gd.hicheel_bagsh_id=hb.hicheel_bagsh_id
                                      LEFT JOIN daalgavriin_hariu dh ON dh.daalgavar_id=gd.daalgavar_id
                                      LEFT JOIN unelgee u ON u.hariu_id=dh.hariu_id
                                      WHERE hb.bagsh_id=?', [$employeeId]) ?: []) : [];
$recentHomework = $isTeacher ? all_rows('SELECT gd.garchig, gd.duusah_ognoo, h.hicheel_ner, a.angi_ner FROM geriin_daalgavar gd JOIN hicheel_bagsh hb ON hb.hicheel_bagsh_id=gd.hicheel_bagsh_id JOIN hicheel h ON h.hicheel_id=hb.hicheel_id JOIN angi a ON a.angi_id=hb.angi_id WHERE hb.bagsh_id=? ORDER BY gd.ogson_ognoo DESC LIMIT 5', [$employeeId]) : [];
$roles = all_rows('SELECT erkh_id, erkh_ner FROM erkh ORDER BY erkh_id');

$pageTitle = 'Ажилтны дэлгэрэнгүй';
include __DIR__ . '/header.php';
?>
<style>
.staff-wrap{max-width:1180px;margin:0 auto}.staff-head{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:24px}.crumb{letter-spacing:.15em;text-transform:uppercase;color:#475569}.staff-title{font-size:42px;font-weight:900;color:#11146b;letter-spacing:-.03em}.staff-actions{display:flex;gap:12px}.staff-btn{border:1px solid #e2e8f0;background:#fff;border-radius:12px;padding:13px 20px;font-weight:800;color:#334155}.staff-btn.primary{background:#1a237e;color:#fff;border-color:#1a237e}.staff-grid{display:grid;grid-template-columns:330px 1fr;gap:28px}.staff-card{background:#fff;border:1px solid #e2e8f0;border-radius:24px;box-shadow:0 12px 32px rgba(15,23,42,.05);padding:30px}.staff-photo{width:190px;height:190px;border-radius:18px;background:linear-gradient(135deg,#e5e7eb,#f8fafc);margin:0 auto 24px;display:flex;align-items:center;justify-content:center;font-size:70px;font-weight:900;color:#1a237e;border:6px solid #eef2ff}.staff-name{text-align:center;font-size:28px;line-height:1.1;font-weight:900;color:#11146b}.staff-role{text-align:center;color:#475569;margin-top:8px}.role-pill{display:inline-flex;margin-top:12px;padding:5px 13px;border-radius:999px;background:#e0e7ff;color:#11146b;font-size:12px;font-weight:900}.stats-row{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-top:36px;text-align:center}.stat-box{min-width:0;padding:0 2px}.stat-num{font-size:30px;font-weight:900;color:#11146b;line-height:1.1}.stat-label{display:block;color:#475569;text-transform:uppercase;font-size:11px;letter-spacing:.02em;line-height:1.25;margin-top:8px;white-space:normal;word-break:keep-all}.section-card{background:#fff;border:1px solid #e2e8f0;border-radius:22px;box-shadow:0 12px 32px rgba(15,23,42,.05);padding:30px;margin-bottom:28px}.section-title{font-size:24px;font-weight:900;color:#11146b;margin-bottom:18px}.class-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.class-card{background:#f8fafc;border-radius:16px;padding:22px;border:1px solid #e5e7eb}.class-card h3{font-size:30px;font-weight:900;color:#11146b}.muted{color:#64748b}.task-list{display:grid;gap:16px}.task-item{display:grid;grid-template-columns:26px 1fr;gap:12px}.task-dot{width:10px;height:10px;border-radius:999px;background:#22c55e;margin-top:8px}.bar{height:6px;background:#e5e7eb;border-radius:999px;overflow:hidden;margin:10px 0 20px}.bar span{display:block;height:100%;background:#1a237e}.info-line{display:flex;gap:10px;align-items:center;color:#475569;margin-top:14px}.inline-edit-card{background:#fff;border:1px solid #e2e8f0;border-radius:22px;box-shadow:0 12px 32px rgba(15,23,42,.05);padding:26px;margin-bottom:28px}.inline-edit-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}.inline-edit-grid label{font-size:13px;font-weight:800;color:#334155}.inline-edit-grid input,.inline-edit-grid select{width:100%;border:1px solid #cbd5e1;border-radius:14px;padding:12px 14px;margin-top:7px}.inline-edit-actions{display:flex;gap:12px;justify-content:flex-end;margin-top:18px}.inline-primary{background:#1a237e;color:#fff;border-radius:12px;padding:12px 18px;font-weight:800}.inline-secondary{background:#e5e7eb;color:#11146b;border-radius:12px;padding:12px 18px;font-weight:800}@media(max-width:1000px){.staff-grid{grid-template-columns:1fr}.class-grid{grid-template-columns:1fr}.staff-head{flex-direction:column;align-items:flex-start}.stats-row,.inline-edit-grid{grid-template-columns:1fr}}</style>
<div class="staff-wrap">
  <div class="staff-head"><div><div class="crumb">Ажилтны дэлгэрэнгүй › <?= e((string)$employee['ajiltan_id']) ?></div><h1 class="staff-title"><?= e($employee['ovog'].' '.$employee['ner']) ?></h1></div><div class="staff-actions"><a class="staff-btn" href="employee_detail.php?id=<?= (int)$employee['ajiltan_id'] ?>&edit=1">Мэдээлэл засах</a><a class="staff-btn primary" href="reports.php">Тайлан харах</a></div></div>
  <?php if ($showEditForm): ?>
  <form method="post" class="inline-edit-card">
    <input type="hidden" name="action" value="update_employee_detail">
    <h2 class="section-title">Мэдээлэл засах</h2>
    <div class="inline-edit-grid">
      <label>Овог<input name="ovog" value="<?= e($employee['ovog']) ?>" required></label>
      <label>Нэр<input name="ner" value="<?= e($employee['ner']) ?>" required></label>
      <label>Имэйл<input type="email" name="email" value="<?= e($employee['email']) ?>" required></label>
      <label>Хүйс<select name="huis"><option value="">Сонгох</option><option value="Эр" <?= $employee['huis']==='Эр'?'selected':'' ?>>Эр</option><option value="Эм" <?= $employee['huis']==='Эм'?'selected':'' ?>>Эм</option></select></label>
      <label>Утас<input name="utas" value="<?= e((string)$employee['utas']) ?>"></label>
      <label>Эрх<select name="erkh_id" required><?php foreach($roles as $r): ?><option value="<?= (int)$r['erkh_id'] ?>" <?= $employee['erkh_ner']===$r['erkh_ner']?'selected':'' ?>><?= e($r['erkh_ner']) ?></option><?php endforeach; ?></select></label>
      <label>Төлөв<select name="tuluv"><option value="Идэвхтэй" <?= $employee['tuluv']==='Идэвхтэй'?'selected':'' ?>>Идэвхтэй</option><option value="Идэвхгүй" <?= $employee['tuluv']==='Идэвхгүй'?'selected':'' ?>>Идэвхгүй</option></select></label>
      <label>Нууц үг солих<input type="password" name="nuuts_ug" placeholder="Хоосон үлдээвэл хэвээр"></label>
    </div>
    <div class="inline-edit-actions"><a class="inline-secondary" href="employee_detail.php?id=<?= (int)$employee['ajiltan_id'] ?>">Болих</a><button class="inline-primary">Хадгалах</button></div>
  </form>
  <?php endif; ?>
  <div class="staff-grid">
    <div class="staff-card"><div class="staff-photo"><?= e(mb_substr($employee['ner'] ?: 'А',0,1)) ?></div><div class="staff-name"><?= e($employee['ovog'].' '.$employee['ner']) ?></div><div class="staff-role"><?= e($employee['erkh_ner']) ?></div><div class="text-center"><span class="role-pill"><?= e($employee['tuluv']) ?></span></div><div class="mt-8"><div class="info-line"><span class="material-symbols-outlined">alternate_email</span><?= e($employee['email']) ?></div><div class="info-line"><span class="material-symbols-outlined">call</span><?= e($employee['utas'] ?: 'Утас бүртгээгүй') ?></div><div class="info-line"><span class="material-symbols-outlined">calendar_month</span><?= e(format_dt($employee['burtgesen_ognoo'])) ?></div></div><?php if($isTeacher): ?><div class="stats-row"><div class="stat-box"><div class="stat-num"><?= e((string)($teacherStats['class_count'] ?? 0)) ?></div><span class="stat-label">Хуваарилалт</span></div><div class="stat-box"><div class="stat-num"><?= e((string)($teacherStats['homework_count'] ?? 0)) ?></div><span class="stat-label">Даалгавар</span></div><div class="stat-box"><div class="stat-num"><?= e((string)round((float)($teacherStats['avg_score'] ?? 0))) ?></div><span class="stat-label">Дундаж</span></div></div><?php endif; ?></div>
    <div>
      <?php if($isTeacher): ?>
      <div class="section-card"><h2 class="section-title">Хариуцсан ангиуд</h2><div class="class-grid"><?php foreach($classes as $c): ?><div class="class-card"><h3><?= e($c['angi_ner']) ?></h3><p class="mt-3 font-bold"><?= e($c['hicheel_ner']) ?></p><p class="muted"><?= e((string)$c['suragch_too']) ?> сурагч • <?= e((string)$c['daalgavar_too']) ?> даалгавар</p></div><?php endforeach; ?><?php if(!$classes): ?><p class="muted">Хуваарилагдсан анги байхгүй.</p><?php endif; ?></div></div>
      <div class="grid md:grid-cols-2 gap-6"><div class="section-card"><h2 class="section-title">Сүүлийн даалгавар</h2><div class="task-list"><?php foreach($recentHomework as $hw): ?><div class="task-item"><span class="task-dot"></span><div><b><?= e($hw['garchig']) ?></b><p class="muted"><?= e($hw['hicheel_ner']) ?> • <?= e($hw['angi_ner']) ?><br>Дуусах: <?= e(format_dt($hw['duusah_ognoo'])) ?></p></div></div><?php endforeach; ?><?php if(!$recentHomework): ?><p class="muted">Даалгаврын мэдээлэл алга.</p><?php endif; ?></div></div><div class="section-card"><h2 class="section-title">Гүйцэтгэлийн тойм</h2><p class="font-bold">Шалгасан хариу</p><div class="bar"><span style="width:<?= min(100,(int)($teacherStats['submission_count'] ?? 0)*10) ?>%"></span></div><p class="muted">Нийт <?= e((string)($teacherStats['submission_count'] ?? 0)) ?> хариу бүртгэгдсэн.</p><p class="font-bold mt-6">Дундаж оноо</p><div class="bar"><span style="width:<?= min(100,(float)($teacherStats['avg_score'] ?? 0)) ?>%;background:#16a34a"></span></div><p class="muted">Сурагчдын дундаж оноо: <?= e((string)($teacherStats['avg_score'] ?? 0)) ?></p></div></div>
      <?php else: ?>
      <div class="section-card"><h2 class="section-title">Ажилтны мэдээлэл</h2><p class="muted">Энэ ажилтан нь <?= e($employee['erkh_ner']) ?> эрхтэй хэрэглэгч байна.</p><p class="mt-4"><b>Имэйл:</b> <?= e($employee['email']) ?></p><p class="mt-2"><b>Төлөв:</b> <?= e($employee['tuluv']) ?></p><p class="mt-2"><b>Бүртгэсэн огноо:</b> <?= e(format_dt($employee['burtgesen_ognoo'])) ?></p></div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php include __DIR__ . '/footer.php'; ?>
