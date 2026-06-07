<?php
require_once __DIR__ . '/functions.php';
require_roles(['Админ']);
$pageTitle = 'Админы хяналтын самбар';

$totalEmployees = count_value('SELECT COUNT(*) FROM ajiltan');
$totalTeachers = count_value("SELECT COUNT(*) FROM ajiltan a JOIN erkh e ON e.erkh_id=a.erkh_id WHERE e.erkh_ner='Багш'");
$totalTraining = count_value("SELECT COUNT(*) FROM ajiltan a JOIN erkh e ON e.erkh_id=a.erkh_id WHERE e.erkh_ner='Сургалтын алба'");
$totalStudents = count_value('SELECT COUNT(*) FROM suragch');
$totalClasses = count_value('SELECT COUNT(*) FROM angi');

$recentEmployees = all_rows(
    "SELECT a.ajiltan_id, a.ovog, a.ner, e.erkh_ner, a.email, a.burtgesen_ognoo
     FROM ajiltan a
     JOIN erkh e ON e.erkh_id = a.erkh_id
     ORDER BY a.ajiltan_id DESC
     LIMIT 6"
);

$roleCards = [
    ['Админ', count_value("SELECT COUNT(*) FROM ajiltan a JOIN erkh e ON e.erkh_id=a.erkh_id WHERE e.erkh_ner='Админ'"), 'admin_panel_settings', 'employee_list.php'],
    ['Сургалтын алба', $totalTraining, 'supervisor_account', 'employee_list.php'],
    ['Багш', $totalTeachers, 'school', 'employee_list.php'],
];

$dbConnected = false;
try {
    db()->query('SELECT 1');
    $dbConnected = true;
} catch (Throwable $e) {
    $dbConnected = false;
}

include __DIR__ . '/header.php';
?>

<div class="dashboard-hero">
  <div>
  </div>
  <div class="dashboard-action-row">
    <a href="employee_list.php" class="dash-btn dash-btn-primary"><span class="material-symbols-outlined">add</span>Ажилтан нэмэх</a>
  </div>
</div>

<div class="dashboard-stats">
  <div class="dashboard-stat-card">
    <div class="stat-topline">
      <span class="stat-icon"><span class="material-symbols-outlined">badge</span></span>
    </div>
    <div class="stat-value"><?= e((string)$totalEmployees) ?></div>
    <div class="stat-label">Нийт ажилтан</div>
  </div>
  <div class="dashboard-stat-card">
    <div class="stat-topline">
      <span class="stat-icon yellow"><span class="material-symbols-outlined">school</span></span>
    </div>
    <div class="stat-value"><?= e((string)$totalTeachers) ?></div>
    <div class="stat-label">Нийт багш</div>
  </div>
  <div class="dashboard-stat-card">
    <div class="stat-topline">
      <span class="stat-icon sky"><span class="material-symbols-outlined">apartment</span></span>
    </div>
    <div class="stat-value"><?= e((string)$totalTraining) ?></div>
    <div class="stat-label">Сургалтын алба</div>
  </div>
  <div class="dashboard-stat-card">
    <div class="stat-topline">
      <span class="stat-icon green"><span class="material-symbols-outlined">groups</span></span>
      <span class="stat-trend"><?= e((string)$totalClasses) ?> анги</span>
    </div>
    <div class="stat-value"><?= e((string)$totalStudents) ?></div>
    <div class="stat-label">Нийт сурагч</div>
  </div>
</div>

<div class="dashboard-grid-2 mb-6">
  <section class="dashboard-panel">
    <div class="panel-head">
      <h3>Сүүлийн үеийн үйл ажиллагаа</h3>
      <a href="employee_list.php">Бүгдийг харах</a>
    </div>
    <div class="overflow-auto">
      <table class="dashboard-table">
        <thead>
          <tr>
            <th>Ажилтан</th>
            <th>Үүрэг</th>
            <th>Үйлдэл</th>
            <th>Хугацаа</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentEmployees as $row): ?>
          <tr>
            <td>
              <div class="mini-user">
                <span class="avatar-soft"><?= e(mb_substr(trim(($row['ovog'] ?? '') . ' ' . ($row['ner'] ?? '')), 0, 1)) ?></span>
                <div>
                  <div class="font-bold text-slate-900"><?= e(trim(($row['ovog'] ?? '') . ' ' . ($row['ner'] ?? ''))) ?></div>
                  <div class="text-slate-500 text-sm"><?= e($row['email']) ?></div>
                </div>
              </div>
            </td>
            <td>
              <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium
                <?= $row['erkh_ner']==='Багш' ? 'bg-blue-100 text-blue-700' :
                   ($row['erkh_ner']==='Админ' ? 'bg-purple-100 text-purple-700' : 'bg-amber-100 text-amber-700') ?>">
                <?= e($row['erkh_ner']) ?>
              </span>
            </td>
            <td class="text-slate-600">Шинэ хэрэглэгч бүртгэгдсэн</td>
            <td class="text-slate-600"><?= e(format_dt($row['burtgesen_ognoo'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <div class="space-y-6">
    <section class="dashboard-panel">
      <div class="panel-head">
        <h3>Эрхийн хуваарьлалт</h3>
      </div>
      <div class="link-card-list">
        <?php foreach ($roleCards as [$label, $count, $icon, $href]): ?>
          <a href="<?= e($href) ?>" class="link-card">
            <div class="flex items-center gap-3">
              <span class="iconwrap"><span class="material-symbols-outlined"><?= e($icon) ?></span></span>
              <div>
                <div class="font-extrabold text-primary"><?= e($label) ?></div>
                <div class="text-slate-500 text-sm"><?= e((string)$count) ?> хэрэглэгч</div>
              </div>
            </div>
            <span class="material-symbols-outlined text-slate-400">arrow_forward_ios</span>
          </a>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="dashboard-panel status-card-strong">
      <div class="panel-head border-white/10">
        <h3>Системийн төлөв</h3>
      </div>
      <div class="p-6 space-y-4">
        <div class="rounded-2xl bg-white/10 border border-white/15 p-5 flex items-center gap-4">
          <span class="w-12 h-12 rounded-2xl bg-green-100 text-green-700 inline-flex items-center justify-center">
            <span class="material-symbols-outlined">check_circle</span>
          </span>
          <div>
            <div class="font-extrabold text-lg">Өгөгдлийн сангийн холболт</div>
            <div class="text-sm opacity-90"><?= $dbConnected ? 'Өгөгдлийн санд амжилттай холбогдсон байна.' : 'Өгөгдлийн сантай холбогдоход алдаа гарлаа.' ?></div>
          </div>
        </div>
        <div class="flex items-center gap-2 text-sm font-bold">
          <span class="w-2.5 h-2.5 rounded-full <?= $dbConnected ? 'bg-green-300' : 'bg-red-300' ?>"></span>
          <span><?= $dbConnected ? 'Connected' : 'Disconnected' ?></span>
        </div>
      </div>
    </section>
  </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
