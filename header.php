<?php
require_once __DIR__ . '/functions.php';
$pageTitle = $pageTitle ?? APP_NAME;
$user = current_user();
$role = current_role();
$flash = flash();
$current = basename($_SERVER['PHP_SELF'] ?? '');

function nav_item(string $href, string $label, string $icon, string $current, array $matches = []): string {
    $isActive = $current === $href || in_array($current, $matches, true);
    $classes = $isActive
        ? 'bg-white dark:bg-indigo-900/20 text-[#1A237E] dark:text-indigo-300 font-bold shadow-sm'
        : 'text-[#526069] dark:text-slate-400 hover:text-[#1A237E] hover:bg-[#F2F4F7] dark:hover:bg-slate-800';
    return '<a class="'.$classes.' rounded-xl transition-all flex items-center gap-3 px-4 py-3 transform hover:translate-x-1 duration-300" href="'.e($href).'"><span class="material-symbols-outlined">'.e($icon).'</span><span class="text-[0.875rem]">'.e($label).'</span></a>';
}
?>
<!DOCTYPE html>
<html lang="mn">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            primary: '#1A237E',
            background: '#F7F9FC',
            surface: '#FFFFFF',
            secondary: '#526069',
            borderlite: '#E2E8F0'
          },
          fontFamily: {
            headline: ['Manrope', 'sans-serif'],
            body: ['Inter', 'sans-serif']
          },
          boxShadow: {
            panel: '4px 0 24px rgba(26,35,126,0.04)',
            card: '0 10px 30px rgba(15, 23, 42, 0.06)'
          }
        }
      }
    }
  </script>
  <link rel="stylesheet" href="assets/styles.css?v=dashboard_fix_20260507">

  <style>
    /* Dashboard critical CSS: браузер CSS cache хийсэн үед ч dashboard эвдрэхгүй */
    .dashboard-stats{display:grid!important;grid-template-columns:repeat(4,minmax(0,1fr))!important;gap:1.25rem!important;margin-bottom:1.75rem!important}.dashboard-stat-card{background:#fff!important;border:1px solid #e2e8f0!important;border-radius:1.35rem!important;box-shadow:0 10px 30px rgba(15,23,42,.06)!important;padding:1.35rem!important;position:relative!important;overflow:hidden!important}.dashboard-stat-card:after{content:"";position:absolute;right:-22px;bottom:-22px;width:94px;height:94px;border-radius:999px;background:rgba(99,102,241,.08)}.stat-topline{display:flex!important;align-items:center!important;justify-content:space-between!important;gap:.75rem!important;margin-bottom:1rem!important}.stat-icon{width:3.15rem;height:3.15rem;border-radius:1rem;display:inline-flex!important;align-items:center!important;justify-content:center!important;background:#eef2ff;color:#1a237e}.stat-icon.green{background:#dcfce7;color:#166534}.stat-icon.yellow{background:#fef3c7;color:#b45309}.stat-icon.red{background:#fee2e2;color:#b91c1c}.stat-icon.sky{background:#e0f2fe;color:#0369a1}.stat-trend{font-size:.82rem;font-weight:700;padding:.42rem .68rem;border-radius:.7rem;background:#dcfce7;color:#166534}.stat-trend.neutral{background:#e2e8f0;color:#475569}.stat-trend.alert{background:#fee2e2;color:#b91c1c}.stat-value{font-size:2.15rem!important;line-height:1!important;font-weight:800!important;color:#1a237e!important;margin-bottom:.45rem!important}.stat-label{font-size:.98rem;color:#526069;font-weight:700;letter-spacing:.02em}.dashboard-grid-2{display:grid!important;grid-template-columns:minmax(0,2fr) minmax(280px,360px)!important;gap:1.5rem!important}.dashboard-panel{background:#fff!important;border:1px solid #e2e8f0!important;border-radius:1.4rem!important;box-shadow:0 10px 30px rgba(15,23,42,.06)!important;overflow:hidden!important}.panel-head{display:flex!important;align-items:center!important;justify-content:space-between!important;gap:.75rem!important;padding:1.35rem 1.55rem!important;border-bottom:1px solid #edf2f7}.panel-head h3{font-size:1.05rem;font-weight:800;color:#1a237e}.course-tile-grid{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:1rem!important}.course-tile{border:1px solid #e5e7eb!important;border-radius:1.2rem!important;padding:1.2rem!important;background:#fff!important;display:grid!important;grid-template-columns:auto 1fr!important;gap:1rem!important}.course-tile .tile-icon{width:3.8rem;height:3.8rem;border-radius:1rem;display:inline-flex!important;align-items:center!important;justify-content:center!important;background:#eef2ff;color:#1a237e}.course-tile .course-title{color:#1a237e!important;font-weight:800!important;font-size:1rem!important;margin-bottom:.28rem!important}.muted-lines{color:#64748b;font-size:.92rem}.progress-rail{width:100%;height:.55rem;border-radius:999px;background:rgba(255,255,255,.18);overflow:hidden}.progress-rail.light{background:#e5e7eb}.progress-bar{height:100%;border-radius:inherit;background:#8df06c}.progress-bar.blue{background:#1a237e}.status-card-strong{background:linear-gradient(135deg,#1a237e 0%,#2c3696 100%)!important;color:#fff!important}.status-card-strong h3,.status-card-strong a{color:#fff}.list-plain{padding:1.35rem;display:grid;gap:1rem}.list-plain-item{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;padding-bottom:1rem;border-bottom:1px solid #edf2f7}.list-plain-item:last-child{border-bottom:0;padding-bottom:0}.avatar-soft{width:2.85rem;height:2.85rem;border-radius:1rem;background:linear-gradient(135deg,#eef2ff,#e2e8f0);color:#1a237e;font-weight:800;display:inline-flex;align-items:center;justify-content:center}.dash-btn{display:inline-flex!important;align-items:center!important;gap:.55rem;border-radius:1rem;padding:.95rem 1.3rem;font-weight:700;border:1px solid #dbe2ea;background:#fff;color:#1a237e;box-shadow:0 6px 18px rgba(15,23,42,.05)}.dash-btn-primary{background:#1a237e!important;color:#fff!important;border-color:#1a237e}.dashboard-table{width:100%}.dashboard-table thead{background:#f8fafc}.dashboard-table th,.dashboard-table td{padding:1rem 1.15rem;font-size:.95rem;text-align:left;vertical-align:top}.link-card-list{padding:1.3rem;display:grid;gap:.95rem}.link-card{display:flex;align-items:center;justify-content:space-between;gap:.8rem;padding:1rem 1.05rem;border-radius:1rem;background:#f8fafc;border:1px solid #e6edf5;color:#0f172a}.link-card .iconwrap{width:2.85rem;height:2.85rem;border-radius:.9rem;display:inline-flex;align-items:center;justify-content:center;background:#eef2ff;color:#1a237e}.quick-pill{display:inline-flex;padding:.33rem .75rem;border-radius:999px;background:#eef2ff;color:#1a237e;font-size:.76rem;font-weight:700}.action-chip{display:inline-flex;padding:.4rem .75rem;border-radius:.8rem;font-weight:700;font-size:.78rem}.action-chip.info{background:#dbeafe;color:#1d4ed8}.action-chip.green{background:#dcfce7;color:#166534}.action-chip.red{background:#fee2e2;color:#b91c1c}.action-chip.warn{background:#fef3c7;color:#92400e}.dashboard-quick-actions{display:flex;flex-wrap:wrap;gap:.85rem;padding:1.2rem 1.4rem 1.4rem}.dashboard-quick-actions a{display:inline-flex;align-items:center;justify-content:center;gap:.5rem;padding:.9rem 1.15rem;border-radius:999px;border:1px solid #dbe2ea;background:#fff;color:#1a237e;font-weight:700}@media(max-width:1280px){.dashboard-stats{grid-template-columns:repeat(2,minmax(0,1fr))!important}.dashboard-grid-2{grid-template-columns:1fr!important}}@media(max-width:768px){.dashboard-stats,.course-tile-grid{grid-template-columns:1fr!important}}
  </style>
</head>
<body class="bg-background text-slate-900 font-body">
<?php if ($user): ?>
<div class="min-h-screen bg-background">
  <aside class="h-screen w-72 fixed left-0 top-0 overflow-y-auto z-50 bg-[#F7F9FC] flex flex-col p-4 gap-y-2 shadow-panel border-r border-slate-200/60">
    <div class="px-4 py-6 mb-4">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-primary flex items-center justify-center text-white shadow-lg">
          <span class="material-symbols-outlined">school</span>
        </div>
        <div>
          <h1 class="text-lg font-black text-primary leading-none font-headline"><?= e(APP_NAME) ?></h1>
        </div>
      </div>
    </div>

    <nav class="flex-1 space-y-1">
      <?= nav_item(dashboard_url_by_role($role), 'Хяналтын самбар', 'dashboard', $current, ['index.php']) ?>

      <?php if (in_array($role, ['Админ', 'Сургалтын алба'], true)): ?>
        <?= nav_item('employee_list.php', 'Ажилтан', 'badge', $current) ?>
      <?php endif; ?>

      <?php if (in_array($role, ['Админ', 'Сургалтын алба', 'Багш'], true)): ?>
        <?= nav_item('student_list.php', 'Сурагч', 'school', $current) ?>
      <?php endif; ?>

      <?php if (in_array($role, ['Админ', 'Сургалтын алба'], true)): ?>
        <?= nav_item('class_list.php', 'Анги', 'groups', $current) ?>
      <?php endif; ?>

      <?php if ($role === 'Сурагч'): ?>
        <?= nav_item('student_dashboard.php', 'Сурагчийн самбар', 'school', $current) ?>
        <?= nav_item('student_courses.php', 'Хичээлүүд', 'menu_book', $current, ['student_course_detail.php']) ?>
      <?php endif; ?>

      <?php if (in_array($role, ['Админ','Сургалтын алба','Багш'], true)): ?>
        <?= nav_item('course_list.php', 'Хичээлүүд', 'menu_book', $current, ['course_detail.php']) ?>
      <?php endif; ?>

      <?php if ($role === 'Сургалтын алба'): ?>
        <?= nav_item('course_create.php', 'Хичээл бүртгэх', 'library_add', $current) ?>
      <?php endif; ?>

      <?php if (in_array($role, ['Админ','Сургалтын алба'], true)): ?>
        <?= nav_item('course_assignment.php', 'Багш, ангид хуваарилах', 'fact_check', $current) ?>
      <?php endif; ?>

      <?php if ($role === 'Багш'): ?>
        <?= nav_item('teacher_homeworks.php', 'Гэрийн даалгавар', 'assignment', $current, ['homework_create.php','homework_edit.php','test_question_manage.php']) ?>
        <?= nav_item('submission_review.php', 'Илгээсэн хариу шалгах', 'rate_review', $current) ?>
      <?php endif; ?>

      <?php if ($role === 'Сурагч'): ?>
        <?= nav_item('student_homeworks.php', 'Гэрийн даалгавар', 'assignment', $current) ?>
        <?= nav_item('submission_create.php', 'Хариу илгээх', 'send', $current) ?>
      <?php endif; ?>

      <?= nav_item('reports.php', $role === 'Сурагч' ? 'Миний үнэлгээ' : 'Тайлан', 'assessment', $current) ?>
    </nav>

    <div class="mt-auto border-t border-slate-200/60 pt-4 space-y-1">
      <div class="px-4 py-3 rounded-xl bg-white shadow-sm border border-slate-200/70">
        <p class="font-semibold text-sm text-slate-900"><?= e($user['name']) ?></p>
        <p class="text-xs text-slate-500 mt-1">
          <?= e($role) ?><?php if ($role === 'Сурагч' && !empty($user['angi_ner'])): ?><span class="opacity-70"> · <?= e($user['angi_ner']) ?></span><?php endif; ?>
        </p>
      </div>
      <a class="text-[#526069] flex items-center gap-3 px-4 py-3 hover:bg-[#F2F4F7] rounded-xl transition-all" href="logout.php">
        <span class="material-symbols-outlined">logout</span>
        <span class="text-[0.875rem]">Гарах</span>
      </a>
    </div>
  </aside>

  <main class="ml-72 min-h-screen">
    <header class="sticky top-0 w-full flex justify-between items-center px-8 h-20 bg-white/80 backdrop-blur-xl border-b border-slate-200/60 z-40">
      <div>
        <h2 class="font-headline text-2xl font-extrabold text-primary"><?= e($pageTitle) ?></h2>
        <p class="text-sm text-slate-500 mt-1">Сургуульд зориулсан даалгавар, үнэлгээ, тайлангийн систем</p>
      </div>
      <div class="flex items-center gap-4">
        <div class="hidden md:flex items-center gap-2 rounded-full bg-slate-100 px-4 py-2 text-sm text-slate-500">
          <span class="material-symbols-outlined text-base">schedule</span>
          <span><?= date('Y-m-d H:i') ?></span>
        </div>
        <div class="w-11 h-11 rounded-full bg-primary text-white flex items-center justify-center font-bold shadow-lg">
          <?= e(mb_substr($user['name'] ?: 'Х', 0, 1)) ?>
        </div>
      </div>
    </header>

    <div class="p-8">
      <?php if ($flash): ?>
        <div class="mb-6 rounded-2xl px-5 py-4 border <?= $flash['type']==='error' ? 'bg-red-50 text-red-700 border-red-200' : 'bg-green-50 text-green-700 border-green-200' ?>">
          <?= e($flash['message']) ?>
        </div>
      <?php endif; ?>
<?php endif; ?>
