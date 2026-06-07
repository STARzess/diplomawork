<?php
require_once __DIR__ . '/functions.php';
require_roles(['Сурагч']);
close_expired_homeworks();

$pageTitle = 'Хичээлүүд';
$studentId = (int)current_user()['id'];
$q = trim($_GET['q'] ?? '');

$sql = 'SELECT hb.hicheel_bagsh_id, h.hicheel_id, h.hicheel_kod, h.hicheel_ner, h.tailbar, h.tuluv,
               a.angi_ner, hb.surgaltiin_jil,
               CONCAT(COALESCE(aj.ovog,""), CASE WHEN aj.ovog IS NOT NULL AND aj.ner IS NOT NULL THEN " " ELSE "" END, COALESCE(aj.ner,"")) AS bagsh_ner,
               COUNT(DISTINCT gd.daalgavar_id) AS daalgavar_too,
               COUNT(DISTINCT dh.hariu_id) AS ilgeesen_too
        FROM suragch st
        JOIN hicheel_bagsh hb ON hb.angi_id = st.angi_id
        JOIN hicheel h ON h.hicheel_id = hb.hicheel_id
        JOIN angi a ON a.angi_id = hb.angi_id
        LEFT JOIN ajiltan aj ON aj.ajiltan_id = hb.bagsh_id
        LEFT JOIN geriin_daalgavar gd ON gd.hicheel_bagsh_id = hb.hicheel_bagsh_id
        LEFT JOIN daalgavriin_hariu dh ON dh.daalgavar_id = gd.daalgavar_id AND dh.suragch_id = st.suragch_id
        WHERE st.suragch_id = ? AND h.tuluv = "Идэвхтэй"';
$params = [$studentId];
if ($q !== '') {
    $sql .= ' AND (h.hicheel_ner LIKE ? OR h.hicheel_kod LIKE ? OR a.angi_ner LIKE ?)';
    $like = "%{$q}%";
    array_push($params, $like, $like, $like);
}
$sql .= ' GROUP BY hb.hicheel_bagsh_id ORDER BY h.hicheel_ner ASC';
$rows = all_rows($sql, $params);

$thumbStyles = [
    // aqua bubbles
    'background-color:#9fe7e2;background-image:radial-gradient(circle at 18% 24%, rgba(24,128,128,.16) 0 18px, transparent 19px),radial-gradient(circle at 40% 18%, rgba(26,188,156,.16) 0 16px, transparent 17px),radial-gradient(circle at 68% 26%, rgba(45,212,191,.18) 0 17px, transparent 18px),radial-gradient(circle at 82% 48%, rgba(20,184,166,.16) 0 18px, transparent 19px),radial-gradient(circle at 30% 70%, rgba(14,116,144,.15) 0 17px, transparent 18px),radial-gradient(circle at 58% 72%, rgba(8,145,178,.14) 0 16px, transparent 17px),linear-gradient(135deg,#9fe7e2,#6fd3d4);',
    // honeycomb-ish gold
    'background-color:#efc86b;background-image:linear-gradient(30deg, rgba(180,120,24,.16) 12%, transparent 12.5%, transparent 87%, rgba(180,120,24,.16) 87.5%),linear-gradient(150deg, rgba(180,120,24,.16) 12%, transparent 12.5%, transparent 87%, rgba(180,120,24,.16) 87.5%),linear-gradient(90deg, rgba(180,120,24,.10) 2%, transparent 2.5%, transparent 97%, rgba(180,120,24,.10) 97.5%),linear-gradient(135deg,#f1ce79,#e6b958);background-size:28px 48px,28px 48px,28px 48px,cover;background-position:0 0,0 0,0 0,center;',
    // gray geometric
    'background-color:#cdd7dd;background-image:linear-gradient(135deg, rgba(100,116,139,.12) 25%, transparent 25%),linear-gradient(225deg, rgba(100,116,139,.12) 25%, transparent 25%),linear-gradient(315deg, rgba(100,116,139,.12) 25%, transparent 25%),linear-gradient(45deg, rgba(100,116,139,.12) 25%, transparent 25%),linear-gradient(135deg,#d7e0e5,#b9c6cd);background-size:32px 32px,32px 32px,32px 32px,32px 32px,cover;background-position:16px 0,16px 0,0 0,0 0,center;',
    // purple facets
    'background-color:#7f6cf1;background-image:linear-gradient(60deg, rgba(255,255,255,.10) 25%, transparent 25.5%, transparent 75%, rgba(255,255,255,.10) 75%),linear-gradient(120deg, rgba(67,56,202,.16) 25%, transparent 25.5%, transparent 75%, rgba(67,56,202,.16) 75%),linear-gradient(135deg,#8978ff,#6754e2);background-size:36px 62px,36px 62px,cover;background-position:0 0,0 0,center;',
    // sky circles
    'background-color:#a7d8ff;background-image:radial-gradient(circle at 22% 28%, rgba(37,99,235,.14) 0 16px, transparent 17px),radial-gradient(circle at 54% 40%, rgba(14,165,233,.13) 0 18px, transparent 19px),radial-gradient(circle at 76% 68%, rgba(3,105,161,.12) 0 17px, transparent 18px),linear-gradient(135deg,#b7e0ff,#8bc3f8);',
    // pink circles
    'background-color:#f4b9c8;background-image:radial-gradient(circle at 26% 25%, rgba(219,39,119,.14) 0 17px, transparent 18px),radial-gradient(circle at 58% 38%, rgba(244,63,94,.12) 0 19px, transparent 20px),radial-gradient(circle at 76% 74%, rgba(190,24,93,.12) 0 16px, transparent 17px),linear-gradient(135deg,#ffd0db,#ee9fb7);',
];

include __DIR__ . '/header.php';
?>
<style>
  .course-overview-wrap{background:#fff;border:1px solid #e2e8f0;border-radius:16px;box-shadow:0 10px 30px rgba(15,23,42,.06);overflow:hidden}
  .course-overview-header{padding:24px 28px 18px;border-bottom:1px solid #e5e7eb;background:#fafbfc}
  .course-overview-title{font-size:17px;font-weight:800;color:#334155}
  .course-search-wrap{padding:22px 28px 14px;border-bottom:1px solid #edf2f7}
  .course-list-wrap{padding:0 16px 6px}
  .course-list-item{display:flex;align-items:center;gap:16px;position:relative;padding:12px 6px 12px 6px;min-height:112px;border-bottom:1px solid #d9dee7;text-decoration:none}
  .course-list-item:hover{background:#fbfcfd}
  .course-thumb-box{width:152px;height:100px;border-radius:4px;flex-shrink:0;overflow:hidden;background-size:cover;background-position:center}
  .course-text-meta{font-size:13px;color:#7c8798;line-height:1.4}
  .course-title-text{margin-top:2px;font-size:17px;line-height:1.25;font-weight:700;color:#253041}
  .course-subtext{margin-top:6px;font-size:13px;color:#7c8798}
  .course-desc{margin-top:6px;font-size:13px;color:#94a3b8}
  @media (max-width:768px){
    .course-overview-header,.course-search-wrap{padding-left:18px;padding-right:18px}
    .course-list-item{gap:12px;min-height:98px}
    .course-thumb-box{width:110px;height:74px}
    .course-title-text{font-size:15px}
    .course-text-meta,.course-subtext,.course-desc{font-size:12px}
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
          <a href="student_courses.php" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm text-slate-700">Цэвэрлэх</a>
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
          $progress = $daalgavarToo > 0 ? round(($ilgeesenToo / $daalgavarToo) * 100) : 0;
        ?>
        <a href="student_course_detail.php?id=<?= (int)$row['hicheel_bagsh_id'] ?>" class="course-list-item">
          <div class="course-thumb-box" style="<?= e($thumbStyle) ?>"></div>
          <div class="min-w-0 flex-1">
            <div class="course-text-meta">
              <?= e((string)$row['hicheel_kod']) ?> - <?= e($row['angi_ner']) ?>
              <?php if (!empty($row['bagsh_ner'])): ?>
                (<?= e($row['bagsh_ner']) ?>)
              <?php endif; ?>
            </div>
            <div class="course-title-text"><?= e($row['hicheel_ner']) ?></div>
            <div class="course-subtext"><?= e($row['surgaltiin_jil']) ?> оны хичээл</div>
            <div class="course-subtext"><?= $progress ?>% дууссан</div>
            <?php if (!empty($row['tailbar'])): ?>
              <div class="course-desc"><?= e((string)$row['tailbar']) ?></div>
            <?php endif; ?>
          </div>
        </a>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="empty-state text-base py-12">
        <?= $q !== '' ? 'Хайлтад тохирох хичээл олдсонгүй.' : 'Танд харагдах хичээл олдсонгүй.' ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/footer.php'; ?>
