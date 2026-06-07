<?php
require_once __DIR__ . '/functions.php';
require_roles(['Багш']);
$pageTitle = 'Багшийн хяналтын самбар';

$teacherId = current_user()['id'];
$myCourses = count_value('SELECT COUNT(*) FROM hicheel_bagsh WHERE bagsh_id = ?', [$teacherId]);
$activeHomework = count_value("SELECT COUNT(*) FROM geriin_daalgavar gd JOIN hicheel_bagsh hb ON hb.hicheel_bagsh_id=gd.hicheel_bagsh_id WHERE hb.bagsh_id = ? AND gd.tuluv='Идэвхтэй'", [$teacherId]);
$submissionCount = count_value('SELECT COUNT(*) FROM daalgavriin_hariu dh JOIN geriin_daalgavar gd ON gd.daalgavar_id=dh.daalgavar_id JOIN hicheel_bagsh hb ON hb.hicheel_bagsh_id=gd.hicheel_bagsh_id WHERE hb.bagsh_id = ?', [$teacherId]);
$pendingGrades = count_value('SELECT COUNT(*) FROM daalgavriin_hariu dh JOIN geriin_daalgavar gd ON gd.daalgavar_id=dh.daalgavar_id JOIN hicheel_bagsh hb ON hb.hicheel_bagsh_id=gd.hicheel_bagsh_id LEFT JOIN unelgee u ON u.hariu_id=dh.hariu_id WHERE hb.bagsh_id = ? AND u.unelgee_id IS NULL', [$teacherId]);

$teacherCourses = all_rows(
    "SELECT hb.hicheel_bagsh_id, h.hicheel_id, h.hicheel_ner, a.angi_ner,
            COUNT(DISTINCT s.suragch_id) AS student_count,
            COUNT(DISTINCT CASE WHEN gd.tuluv='Идэвхтэй' THEN gd.daalgavar_id END) AS active_homework,
            COUNT(DISTINCT dh.hariu_id) AS submission_count
     FROM hicheel_bagsh hb
     JOIN hicheel h ON h.hicheel_id = hb.hicheel_id
     JOIN angi a ON a.angi_id = hb.angi_id
     LEFT JOIN suragch s ON s.angi_id = hb.angi_id AND s.tuluv='Идэвхтэй'
     LEFT JOIN geriin_daalgavar gd ON gd.hicheel_bagsh_id = hb.hicheel_bagsh_id
     LEFT JOIN daalgavriin_hariu dh ON dh.daalgavar_id = gd.daalgavar_id
     WHERE hb.bagsh_id = ?
     GROUP BY hb.hicheel_bagsh_id, h.hicheel_id, h.hicheel_ner, a.angi_ner
     ORDER BY hb.hicheel_bagsh_id DESC
     LIMIT 4",
    [$teacherId]
);

$latestSubmissions = all_rows(
    "SELECT dh.hariu_id, gd.daalgavar_id, gd.garchig, dh.ilgeesen_ognoo,
            s.ovog, s.ner, h.hicheel_ner, a.angi_ner,
            u.unelgee_id
     FROM daalgavriin_hariu dh
     JOIN geriin_daalgavar gd ON gd.daalgavar_id = dh.daalgavar_id
     JOIN hicheel_bagsh hb ON hb.hicheel_bagsh_id = gd.hicheel_bagsh_id
     JOIN hicheel h ON h.hicheel_id = hb.hicheel_id
     JOIN angi a ON a.angi_id = hb.angi_id
     JOIN suragch s ON s.suragch_id = dh.suragch_id
     LEFT JOIN unelgee u ON u.hariu_id = dh.hariu_id
     WHERE hb.bagsh_id = ?
     ORDER BY dh.ilgeesen_ognoo DESC
     LIMIT 5",
    [$teacherId]
);

include __DIR__ . '/header.php';
?>

<style>
.course-tile-link{color:inherit;text-decoration:none;transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease}
.course-tile-link:hover{transform:translateY(-2px);box-shadow:0 14px 28px rgba(15,23,42,.08);border-color:#c7d2fe}
.status-card-strong .quick-report-btn{background:#fff!important;color:#1a237e!important}
</style>

<div class="dashboard-stats">
  <div class="dashboard-stat-card">
    <div class="stat-topline">
      <span class="stat-icon"><span class="material-symbols-outlined">book_2</span></span>
    </div>
    <div class="stat-label mb-2">Миний хичээлүүд</div>
    <div class="stat-value"><?= e((string)$myCourses) ?></div>
  </div>
  <div class="dashboard-stat-card">
    <div class="stat-topline">
      <span class="stat-icon sky"><span class="material-symbols-outlined">assignment</span></span>
    </div>
    <div class="stat-label mb-2">Идэвхтэй даалгаврууд</div>
    <div class="stat-value"><?= e((string)$activeHomework) ?></div>
  </div>
  <div class="dashboard-stat-card">
    <div class="stat-topline">
      <span class="stat-icon green"><span class="material-symbols-outlined">task_alt</span></span>
    </div>
    <div class="stat-label mb-2">Илгээсэн хариунууд</div>
    <div class="stat-value"><?= e((string)$submissionCount) ?></div>
  </div>
  <div class="dashboard-stat-card border-red-200 bg-red-50/40">
    <div class="stat-topline">
      <span class="stat-icon red"><span class="material-symbols-outlined">pending_actions</span></span>
    </div>
    <div class="stat-label mb-2">Засагдаагүй даалгавар</div>
    <div class="stat-value text-red-600"><?= e((string)$pendingGrades) ?></div>
  </div>
</div>

<div class="dashboard-grid-2 mb-6" style="grid-template-columns:minmax(0,2fr) minmax(300px,340px);">
  <section>
    <div class="panel-head !px-0 !pt-0 !pb-4 border-0">
      <h3 class="!text-2xl !font-extrabold">Одоогийн хичээлүүд</h3>
      <a href="course_list.php">Бүгдийг харах</a>
    </div>
    <div class="course-tile-grid mb-6">
      <?php 
      $tileIcons = ['functions','public','history','science'];
      $tileClasses = ['','green','sky','red'];
      foreach ($teacherCourses as $i => $row):
        $students = (int)$row['student_count'];
        $active = (int)$row['active_homework'];
        $submissions = (int)$row['submission_count'];
        $target = max(1, $students * max(1, $active));
        $progress = min(100, (int)round(($submissions / $target) * 100));
      ?>
      <a class="course-tile course-tile-link" href="course_detail.php?id=<?= e((string)$row['hicheel_id']) ?>&hb=<?= e((string)$row['hicheel_bagsh_id']) ?>">
        <span class="tile-icon <?= e($tileClasses[$i] ?? '') ?>"><span class="material-symbols-outlined"><?= e($tileIcons[$i] ?? 'book_2') ?></span></span>
        <div>
          <div class="course-title"><?= e($row['hicheel_ner']) ?> - <?= e($row['angi_ner']) ?></div>
          <div class="muted-lines"><?= e((string)$students) ?> сурагч • <?= e((string)$active) ?> идэвхтэй даалгавар</div>
          <div class="mt-3"><div class="progress-rail light"><div class="progress-bar blue" style="width: <?= e((string)$progress) ?>%"></div></div></div>
        </div>
      </a>
      <?php endforeach; ?>
      <?php if (!$teacherCourses): ?>
      <div class="dashboard-panel p-6 text-slate-500 col-span-2">Одоогоор хуваарилагдсан хичээл алга.</div>
      <?php endif; ?>
    </div>

    <section class="dashboard-panel">
      <div class="panel-head">
        <h3>Сүүлд ирсэн хариунууд</h3>
        <div class="flex items-center gap-2">
          <span class="quick-pill">Шинэ</span>
        </div>
      </div>
      <div class="list-plain">
        <?php foreach ($latestSubmissions as $row): ?>
          <div class="list-plain-item">
            <div class="flex gap-4">
              <span class="avatar-soft"><?= e(mb_substr($row['ovog'] ?? 'С', 0, 1)) ?></span>
              <div>
                <h4><?= e(trim(($row['ovog'] ?? '') . ' ' . ($row['ner'] ?? ''))) ?></h4>
                <p><?= e($row['hicheel_ner']) ?> · <?= e($row['angi_ner']) ?></p>
                <p><?= e($row['garchig']) ?> · <?= e(format_dt($row['ilgeesen_ognoo'])) ?></p>
              </div>
            </div>
            <div class="text-right">
              <div class="mb-3"><span class="action-chip <?= !empty($row['unelgee_id']) ? 'green' : 'info' ?>"><?= !empty($row['unelgee_id']) ? 'Шалгасан' : 'Хүлээгдэж буй' ?></span></div>
              <a href="submission_review.php?daalgavar_id=<?= e((string)$row['daalgavar_id']) ?>" class="dash-btn dash-btn-primary !px-4 !py-2 !rounded-xl !text-sm !shadow-none">Засах</a>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (!$latestSubmissions): ?>
          <div class="text-slate-500">Илгээсэн хариу одоогоор алга байна.</div>
        <?php endif; ?>
      </div>
    </section>
  </section>

  <div class="space-y-6">
    <section class="dashboard-panel status-card-strong">
      <div class="panel-head border-white/10">
        <h3>Шуурхай үйлдэл</h3>
      </div>
      <div class="p-6 space-y-4">
        <a href="homework_create.php" class="block rounded-2xl bg-white/10 border border-white/10 px-5 py-4 font-bold">Даалгавар нэмэх</a>
        <a href="reports.php" class="block rounded-2xl bg-white px-5 py-4 font-bold" style="color:#1a237e;">Тайлан гаргах</a>
      </div>
    </section>

      </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
