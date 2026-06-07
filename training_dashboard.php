<?php
require_once __DIR__ . '/functions.php';
require_roles(['Сургалтын алба']);
$pageTitle = 'Сургалтын албаны хяналтын самбар';

$totalCourses = count_value('SELECT COUNT(*) FROM hicheel');
$activeCourses = count_value("SELECT COUNT(*) FROM hicheel WHERE tuluv='Идэвхтэй'");
$teacherAssignments = count_value('SELECT COUNT(*) FROM hicheel_bagsh');
$assignedTeachers = count_value('SELECT COUNT(DISTINCT bagsh_id) FROM hicheel_bagsh');
$totalReports = count_value('SELECT COUNT(*) FROM unelgee');
$totalTeachers = count_value("SELECT COUNT(*) FROM ajiltan a JOIN erkh e ON e.erkh_id=a.erkh_id WHERE e.erkh_ner='Багш'");

$recentCourses = all_rows(
    "SELECT h.hicheel_id, h.hicheel_ner, h.tuluv, h.uusgesen_ognoo,
            COALESCE(MIN(CONCAT(a.ovog, ' ', a.ner)), 'Хуваарилагдаагүй') AS bagsh_ner
     FROM hicheel h
     LEFT JOIN hicheel_bagsh hb ON hb.hicheel_id = h.hicheel_id
     LEFT JOIN ajiltan a ON a.ajiltan_id = hb.bagsh_id
     GROUP BY h.hicheel_id, h.hicheel_ner, h.tuluv, h.uusgesen_ognoo
     ORDER BY h.hicheel_id DESC
     LIMIT 6"
);

$assignmentProgress = $totalCourses > 0 ? min(100, (int)round(($teacherAssignments / $totalCourses) * 100)) : 0;
$teacherProgress = $totalTeachers > 0 ? min(100, (int)round(($assignedTeachers / $totalTeachers) * 100)) : 0;

include __DIR__ . '/header.php';
?>

<div class="dashboard-hero">
  <div>
  </div>
  <div class="dashboard-action-row">
    <a href="course_create.php" class="dash-btn dash-btn-primary"><span class="material-symbols-outlined">add</span>Шинэ хичээл бүртгэх</a>
  </div>
</div>

<div class="dashboard-stats">
  <div class="dashboard-stat-card">
    <div class="stat-topline">
      <span class="stat-icon"><span class="material-symbols-outlined">menu_book</span></span>
      
    </div>
    <div class="stat-label mb-2">Нийт хичээл</div>
    <div class="stat-value"><?= e((string)$totalCourses) ?></div>
  </div>
  <div class="dashboard-stat-card">
    <div class="stat-topline">
      <span class="stat-icon yellow"><span class="material-symbols-outlined">schedule</span></span>
      
    </div>
    <div class="stat-label mb-2">Идэвхтэй хичээлүүд</div>
    <div class="stat-value"><?= e((string)$activeCourses) ?></div>
  </div>
  <div class="dashboard-stat-card">
    <div class="stat-topline">
      <span class="stat-icon sky"><span class="material-symbols-outlined">person_search</span></span>
      <span class="stat-trend">+<?= e((string)$assignedTeachers) ?></span>
    </div>
    <div class="stat-label mb-2">Хуваарилагдсан багш нар</div>
    <div class="stat-value"><?= e((string)$assignedTeachers) ?></div>
  </div>
  <div class="dashboard-stat-card">
    <div class="stat-topline">
      <span class="stat-icon green"><span class="material-symbols-outlined">bar_chart</span></span>
      
    </div>
    <div class="stat-label mb-2">Нийт үнэлгээ</div>
    <div class="stat-value"><?= e((string)$totalReports) ?></div>
  </div>
</div>

<div class="dashboard-grid-2 mb-6">
  <section class="dashboard-panel">
    <div class="panel-head">
      <h3>Сүүлийн хичээл бүртгэлүүд</h3>
      <a href="course_list.php">Бүгдийг үзэх</a>
    </div>
    <div class="overflow-auto">
      <table class="dashboard-table">
        <thead>
          <tr>
            <th>Хичээлийн нэр</th>
            <th>Багш</th>
            <th>Огноо</th>
            <th>Төлөв</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentCourses as $row): ?>
          <tr>
            <td>
              <a href="course_detail.php?id=<?= e((string)$row['hicheel_id']) ?>" class="font-bold text-primary hover:underline">
                <?= e($row['hicheel_ner']) ?>
              </a>
            </td>
            <td class="text-slate-600"><?= e($row['bagsh_ner']) ?></td>
            <td class="text-slate-600"><?= e(format_dt($row['uusgesen_ognoo'])) ?></td>
            <td>
              <?php $tuluv = $row['tuluv'] ?? ''; ?>
              <span class="action-chip <?= $tuluv === 'Идэвхтэй' ? 'green' : 'warn' ?>"><?= e($tuluv ?: 'Хүлээгдэж буй') ?></span>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <div class="space-y-6">
    <section class="dashboard-panel">
      <div class="panel-head">
        <h3>Шуурхай холбоос</h3>
      </div>
      <div class="link-card-list">
        <a href="course_create.php" class="link-card">
          <div class="flex items-center gap-3">
            <span class="iconwrap"><span class="material-symbols-outlined">post_add</span></span>
            <div><div class="font-extrabold text-primary">Хичээл бүртгэх</div></div>
          </div>
          <span class="material-symbols-outlined text-slate-400">arrow_forward_ios</span>
        </a>
        <a href="reports.php" class="link-card">
          <div class="flex items-center gap-3">
            <span class="iconwrap"><span class="material-symbols-outlined">analytics</span></span>
            <div><div class="font-extrabold text-primary">Тайлан гаргах</div></div>
          </div>
          <span class="material-symbols-outlined text-slate-400">arrow_forward_ios</span>
        </a>
        <a href="course_assignment.php" class="link-card">
          <div class="flex items-center gap-3">
            <span class="iconwrap"><span class="material-symbols-outlined">calendar_month</span></span>
            <div><div class="font-extrabold text-primary">Хуваарьлалт шинэчлэх</div></div>
          </div>
          <span class="material-symbols-outlined text-slate-400">arrow_forward_ios</span>
        </a>
      </div>
    </section>

    <section class="dashboard-panel status-card-strong">
      <div class="panel-head border-white/10">
        <h3>Бүртгэлийн явц</h3>
      </div>
      <div class="p-6 space-y-5">
        <div>
          <div class="flex items-center justify-between text-sm mb-2"><span>Хичээл хуваарилалт</span><strong><?= e((string)$assignmentProgress) ?>%</strong></div>
          <div class="progress-rail"><div class="progress-bar" style="width: <?= e((string)$assignmentProgress) ?>%"></div></div>
        </div>
        <div>
          <div class="flex items-center justify-between text-sm mb-2"><span>Багшийн бүртгэл</span><strong><?= e((string)$teacherProgress) ?>%</strong></div>
          <div class="progress-rail"><div class="progress-bar blue" style="width: <?= e((string)$teacherProgress) ?>%"></div></div>
        </div>
        <a href="course_assignment.php" class="block rounded-2xl bg-white/10 border border-white/15 px-5 py-4 text-center font-bold">Дэлгэрэнгүй үзэх</a>
      </div>
    </section>
  </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
