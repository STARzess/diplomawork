<?php
require_once __DIR__ . '/functions.php';
require_roles(['Админ','Сургалтын алба','Багш']);

$pageTitle  = 'Хичээлүүд';
$isBagsh    = current_role() === 'Багш';
$bagshId    = $isBagsh ? (int)current_user()['id'] : 0;
$q = trim($_GET['q'] ?? '');
$filterAngi  = (int)($_GET['filter_angi'] ?? 0);
$filterTuluv = trim($_GET['filter_tuluv'] ?? '');

if ($isBagsh) {
    $sql = 'SELECT h.hicheel_id, h.hicheel_kod, h.hicheel_ner, h.tuluv, h.tailbar,
                   hb.hicheel_bagsh_id, hb.surgaltiin_jil,
                   a.angi_id, a.angi_ner,
                   CONCAT(COALESCE(aj.ovog,""), CASE WHEN aj.ovog IS NOT NULL AND aj.ner IS NOT NULL THEN " " ELSE "" END, COALESCE(aj.ner,"")) AS bagsh_ner,
                   COUNT(DISTINCT gd.daalgavar_id) AS daalgavar_too,
                   COUNT(DISTINCT dh.hariu_id) AS ilgeesen_too,
                   COUNT(DISTINCT s.suragch_id) AS suragch_too
            FROM hicheel_bagsh hb
            JOIN hicheel h ON h.hicheel_id = hb.hicheel_id
            JOIN angi a ON a.angi_id = hb.angi_id
            LEFT JOIN ajiltan aj ON aj.ajiltan_id = hb.bagsh_id
            LEFT JOIN geriin_daalgavar gd ON gd.hicheel_bagsh_id = hb.hicheel_bagsh_id
            LEFT JOIN daalgavriin_hariu dh ON dh.daalgavar_id = gd.daalgavar_id
            LEFT JOIN suragch s ON s.angi_id = hb.angi_id AND s.tuluv = "Идэвхтэй"
            WHERE hb.bagsh_id = ?';
    $params = [$bagshId];

    if ($q !== '') {
        $sql .= ' AND (h.hicheel_ner LIKE ? OR h.hicheel_kod LIKE ? OR a.angi_ner LIKE ?)';
        $like = "%{$q}%";
        array_push($params, $like, $like, $like);
    }
    if ($filterAngi > 0) {
        $sql .= ' AND a.angi_id = ?';
        $params[] = $filterAngi;
    }
    if ($filterTuluv !== '') {
        $sql .= ' AND h.tuluv = ?';
        $params[] = $filterTuluv;
    }
    $sql .= ' GROUP BY hb.hicheel_bagsh_id ORDER BY a.angi_ner, h.hicheel_ner';
    $rows = all_rows($sql, $params);
} else {
    $sql = 'SELECT h.hicheel_id, h.hicheel_kod, h.hicheel_ner, h.tuluv, h.tailbar,
                   COUNT(DISTINCT hb.hicheel_bagsh_id) AS bagsh_too,
                   COUNT(DISTINCT gd.daalgavar_id) AS daalgavar_too
            FROM hicheel h
            LEFT JOIN hicheel_bagsh hb ON hb.hicheel_id = h.hicheel_id
            LEFT JOIN geriin_daalgavar gd ON gd.hicheel_bagsh_id = hb.hicheel_bagsh_id';
    $params = [];
    $wheres = [];

    if ($q !== '') {
        $wheres[] = '(h.hicheel_ner LIKE ? OR h.hicheel_kod LIKE ?)';
        $like = "%{$q}%";
        array_push($params, $like, $like);
    }
    if ($filterTuluv !== '') {
        $wheres[] = 'h.tuluv = ?';
        $params[] = $filterTuluv;
    }
    if ($wheres) {
        $sql .= ' WHERE ' . implode(' AND ', $wheres);
    }
    $sql .= ' GROUP BY h.hicheel_id ORDER BY h.hicheel_id DESC';
    $rows = all_rows($sql, $params);
}

$hasFilter = $q !== '' || $filterAngi > 0 || $filterTuluv !== '';
$myClasses = $isBagsh
    ? all_rows('SELECT DISTINCT a.angi_id, a.angi_ner FROM hicheel_bagsh hb JOIN angi a ON a.angi_id=hb.angi_id WHERE hb.bagsh_id=? ORDER BY a.angi_ner', [$bagshId])
    : [];

$thumbStyles = [
    'background-color:#9fe7e2;background-image:radial-gradient(circle at 18% 24%, rgba(24,128,128,.16) 0 18px, transparent 19px),radial-gradient(circle at 40% 18%, rgba(26,188,156,.16) 0 16px, transparent 17px),radial-gradient(circle at 68% 26%, rgba(45,212,191,.18) 0 17px, transparent 18px),radial-gradient(circle at 82% 48%, rgba(20,184,166,.16) 0 18px, transparent 19px),radial-gradient(circle at 30% 70%, rgba(14,116,144,.15) 0 17px, transparent 18px),radial-gradient(circle at 58% 72%, rgba(8,145,178,.14) 0 16px, transparent 17px),linear-gradient(135deg,#9fe7e2,#6fd3d4);',
    'background-color:#efc86b;background-image:linear-gradient(30deg, rgba(180,120,24,.16) 12%, transparent 12.5%, transparent 87%, rgba(180,120,24,.16) 87.5%),linear-gradient(150deg, rgba(180,120,24,.16) 12%, transparent 12.5%, transparent 87%, rgba(180,120,24,.16) 87.5%),linear-gradient(90deg, rgba(180,120,24,.10) 2%, transparent 2.5%, transparent 97%, rgba(180,120,24,.10) 97.5%),linear-gradient(135deg,#f1ce79,#e6b958);background-size:28px 48px,28px 48px,28px 48px,cover;background-position:0 0,0 0,0 0,center;',
    'background-color:#cdd7dd;background-image:linear-gradient(135deg, rgba(100,116,139,.12) 25%, transparent 25%),linear-gradient(225deg, rgba(100,116,139,.12) 25%, transparent 25%),linear-gradient(315deg, rgba(100,116,139,.12) 25%, transparent 25%),linear-gradient(45deg, rgba(100,116,139,.12) 25%, transparent 25%),linear-gradient(135deg,#d7e0e5,#b9c6cd);background-size:32px 32px,32px 32px,32px 32px,32px 32px,cover;background-position:16px 0,16px 0,0 0,0 0,center;',
    'background-color:#7f6cf1;background-image:linear-gradient(60deg, rgba(255,255,255,.10) 25%, transparent 25.5%, transparent 75%, rgba(255,255,255,.10) 75%),linear-gradient(120deg, rgba(67,56,202,.16) 25%, transparent 25.5%, transparent 75%, rgba(67,56,202,.16) 75%),linear-gradient(135deg,#8978ff,#6754e2);background-size:36px 62px,36px 62px,cover;background-position:0 0,0 0,center;',
    'background-color:#a7d8ff;background-image:radial-gradient(circle at 22% 28%, rgba(37,99,235,.14) 0 16px, transparent 17px),radial-gradient(circle at 54% 40%, rgba(14,165,233,.13) 0 18px, transparent 19px),radial-gradient(circle at 76% 68%, rgba(3,105,161,.12) 0 17px, transparent 18px),linear-gradient(135deg,#b7e0ff,#8bc3f8);',
    'background-color:#f4b9c8;background-image:radial-gradient(circle at 26% 25%, rgba(219,39,119,.14) 0 17px, transparent 18px),radial-gradient(circle at 58% 38%, rgba(244,63,94,.12) 0 19px, transparent 20px),radial-gradient(circle at 76% 74%, rgba(190,24,93,.12) 0 16px, transparent 17px),linear-gradient(135deg,#ffd0db,#ee9fb7);',
];

include __DIR__ . '/header.php';
?>
<?php if ($isBagsh): ?>
<style>
  .course-overview-wrap{background:#fff;border:1px solid #e2e8f0;border-radius:16px;box-shadow:0 10px 30px rgba(15,23,42,.06);overflow:hidden}
  .course-overview-header{padding:24px 28px 18px;border-bottom:1px solid #e5e7eb;background:#fafbfc}
  .course-overview-title{font-size:17px;font-weight:800;color:#334155}
  .course-search-wrap{padding:22px 28px 14px;border-bottom:1px solid #edf2f7}
  .course-list-wrap{padding:0 16px 6px}
  .teacher-course-item{display:flex;align-items:center;gap:16px;position:relative;padding:12px 10px 12px 6px;min-height:112px;border-bottom:1px solid #d9dee7;text-decoration:none}
  .teacher-course-item:hover{background:#fbfcfd}
  .teacher-course-thumb{width:152px;height:100px;border-radius:4px;flex-shrink:0;overflow:hidden;background-size:cover;background-position:center}
  .teacher-course-meta{font-size:13px;color:#7c8798;line-height:1.4}
  .teacher-course-title{margin-top:2px;font-size:17px;line-height:1.25;font-weight:700;color:#253041}
  .teacher-course-sub{margin-top:6px;font-size:13px;color:#7c8798}
  .teacher-course-desc{margin-top:6px;font-size:13px;color:#94a3b8}
  .teacher-course-badges{display:flex;flex-wrap:wrap;gap:8px;margin-top:8px}
  .teacher-course-badge{display:inline-flex;align-items:center;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:700}
  .teacher-course-badge-indigo{background:#eef2ff;color:#4338ca}
  .teacher-course-badge-green{background:#ecfdf5;color:#047857}
  .teacher-course-badge-red{background:#fef2f2;color:#dc2626}
  @media (max-width:768px){
    .course-overview-header,.course-search-wrap{padding-left:18px;padding-right:18px}
    .teacher-course-item{gap:12px;min-height:98px}
    .teacher-course-thumb{width:110px;height:74px}
    .teacher-course-title{font-size:15px}
    .teacher-course-meta,.teacher-course-sub,.teacher-course-desc{font-size:12px}
  }
</style>
<div class="course-overview-wrap">
  <div class="course-overview-header">
    <div class="course-overview-title">Хичээлийн тойм</div>
  </div>

  <div class="course-search-wrap">
    <form method="get" class="flex flex-col lg:flex-row lg:items-end gap-3">
      <div class="w-full lg:max-w-md">
        <label class="block text-sm font-medium text-slate-600 mb-2">Хайх</label>
        <input name="q" value="<?= e($q) ?>" class="w-full rounded-xl border-slate-300 px-4 py-3 text-sm bg-white" placeholder="Хичээлийн нэр, код эсвэл ангиар хайх">
      </div>
      <div class="flex gap-2">
        <button class="rounded-xl bg-slate-900 text-white px-5 py-3 text-sm font-medium">Хайх</button>
        <?php if ($q !== ''): ?>
          <a href="course_list.php" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm text-slate-700">Цэвэрлэх</a>
        <?php endif; ?>
      </div>
    </form>
    <p class="text-xs text-slate-400 mt-3">Нийт <?= count($rows) ?> хичээл</p>
  </div>

  <div class="course-list-wrap">
    <?php if ($rows): ?>
      <?php foreach ($rows as $row): ?>
        <?php
          $thumbStyle = $thumbStyles[abs(crc32((string)$row['hicheel_bagsh_id'])) % count($thumbStyles)];
          $daalgavarToo = (int)$row['daalgavar_too'];
          $ilgeesenToo = (int)$row['ilgeesen_too'];
          $suragchToo = (int)($row['suragch_too'] ?? 0);
          $niitBolomjitHariu = $daalgavarToo * $suragchToo;
          $progress = $niitBolomjitHariu > 0 ? min(100, round(($ilgeesenToo / $niitBolomjitHariu) * 100)) : 0;
        ?>
        <a href="course_detail.php?id=<?= (int)$row['hicheel_bagsh_id'] ?>&mode=assignment" class="teacher-course-item">
          <div class="teacher-course-thumb" style="<?= e($thumbStyle) ?>"></div>
          <div class="min-w-0 flex-1">
            <div class="teacher-course-meta">
              <?= e((string)$row['hicheel_kod']) ?> - <?= e($row['angi_ner']) ?>
              <?php if (!empty($row['bagsh_ner'])): ?>
                (<?= e($row['bagsh_ner']) ?>)
              <?php endif; ?>
            </div>
            <div class="teacher-course-title"><?= e($row['hicheel_ner']) ?></div>
            <div class="teacher-course-sub"><?= e($row['surgaltiin_jil']) ?> оны хичээл</div>
            <div class="teacher-course-sub"><?= $progress ?>% гүйцэтгэл</div>
            <div class="teacher-course-badges">
              <span class="teacher-course-badge teacher-course-badge-indigo"><?= e($row['angi_ner']) ?></span>
              <span class="teacher-course-badge <?= ($row['tuluv'] ?? '') === 'Идэвхтэй' ? 'teacher-course-badge-green' : 'teacher-course-badge-red' ?>">
                <?= e($row['tuluv']) ?>
              </span>
              <span class="teacher-course-badge teacher-course-badge-indigo"><?= $daalgavarToo ?> даалгавар</span>
              <span class="teacher-course-badge teacher-course-badge-indigo"><?= $suragchToo ?> сурагч</span>
            </div>
            <?php if (!empty($row['tailbar'])): ?>
              <div class="teacher-course-desc"><?= e((string)$row['tailbar']) ?></div>
            <?php endif; ?>
          </div>
        </a>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="empty-state text-base py-12">
        <?= $q !== '' ? 'Хайлтад тохирох хичээл олдсонгүй.' : 'Танд хуваарилагдсан хичээл олдсонгүй.' ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php else: ?>
<div class="table-shell">
  <div class="p-5 border-b space-y-3">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
      <div>
        <h3 class="font-headline text-xl font-extrabold text-primary">Хичээлүүд</h3>
        <p class="page-subtitle mt-1 text-sm text-slate-500">Хичээл хайх, жагсаалт харах, дэлгэрэнгүй мэдээлэл шалгах хэсэг</p>
      </div>
      <div class="flex items-center gap-3">
        <?php if ($hasFilter): ?>
          <a href="course_list.php" class="text-sm text-red-500 hover:underline">✕ Цэвэрлэх</a>
        <?php endif; ?>
        <?php if (current_role() === 'Сургалтын алба'): ?>
          <a href="course_create.php" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-primary text-white px-4 py-2.5 font-medium">Хичээл бүртгэх</a>
        <?php endif; ?>
      </div>
    </div>

    <form method="get" class="grid grid-cols-2 md:grid-cols-4 gap-2">
      <div class="col-span-2 md:col-span-3">
        <input name="q" value="<?= e($q) ?>" class="w-full rounded-2xl border-slate-300 px-4 py-2.5 text-sm bg-white" placeholder="Хичээлийн нэр, код хайх…">
      </div>
      <select name="filter_tuluv" class="rounded-2xl border-slate-300 px-3 py-2.5 text-sm bg-white">
        <option value="">Бүх төлөв</option>
        <option value="Идэвхтэй" <?= $filterTuluv==='Идэвхтэй' ? 'selected' : '' ?>>Идэвхтэй</option>
        <option value="Идэвхгүй" <?= $filterTuluv==='Идэвхгүй' ? 'selected' : '' ?>>Идэвхгүй</option>
      </select>
      <button class="col-span-2 md:col-span-1 bg-slate-900 text-white rounded-2xl px-4 py-2.5 font-medium text-sm">Шүүх</button>
    </form>

    <p class="text-xs text-slate-400"><?= count($rows) ?> үр дүн</p>
  </div>

  <div class="overflow-auto">
    <table class="text-sm w-full">
      <thead>
        <tr>
          <th class="text-left p-4">Код</th>
          <th class="text-left p-4">Хичээл</th>
          <th class="text-left p-4">Хуваарилалт</th>
          <th class="text-left p-4">Төлөв</th>
          <th class="text-left p-4">Даалгавар</th>
          <th class="text-left p-4">Тайлбар</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $row): ?>
        <tr class="border-t align-top hover:bg-slate-50/50">
          <td class="p-4 font-medium text-slate-700"><?= e((string)$row['hicheel_kod']) ?></td>
          <td class="p-4">
            <div class="font-semibold text-slate-900"><a class="text-primary hover:underline" href="course_detail.php?id=<?= (int)$row['hicheel_id'] ?>"><?= e($row['hicheel_ner']) ?></a></div>
            <div class="text-xs text-slate-400 mt-0.5">ID: <?= (int)$row['hicheel_id'] ?></div>
          </td>
          <td class="p-4"><?= e((string)$row['bagsh_too']) ?></td>
          <td class="p-4"><span class="status-pill <?= ($row['tuluv'] ?? '') === 'Идэвхтэй' ? 'status-active' : 'status-muted' ?>"><?= e($row['tuluv']) ?></span></td>
          <td class="p-4"><?= e((string)$row['daalgavar_too']) ?></td>
          <td class="p-4 text-slate-500"><?= e((string)$row['tailbar']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?>
        <tr>
          <td colspan="6" class="p-10 text-center text-slate-400"><?= $hasFilter ? 'Шүүлтэд тохирох хичээл олдсонгүй.' : 'Хичээлийн мэдээлэл олдсонгүй.' ?></td>
        </tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
<?php include __DIR__ . '/footer.php'; ?>
