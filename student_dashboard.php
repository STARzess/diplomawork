<?php
require_once __DIR__ . '/functions.php';
require_roles(['Сурагч']);
$pageTitle = 'Сурагчийн хяналтын самбар';
$studentId = (int)current_user()['id'];

function sd_percent(float $value): string {
    return rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.') . '%';
}
function sd_grade_letter(float $score): string {
    if ($score >= 90) return 'A';
    if ($score >= 80) return 'B';
    if ($score >= 70) return 'C';
    if ($score >= 60) return 'D';
    return 'F';
}
function sd_due_badge(?string $dt): array {
    if (!$dt) return ['-', 'status-muted'];
    try {
        $due = new DateTime($dt);
        $now = new DateTime();
        if ($due < $now) {
            $diff = $now->diff($due);
            $days = (int)$diff->format('%a');
            if ($days === 0) return ['Хоцорсон', 'status-danger'];
            return [$days . ' өдөр хоцорсон', 'status-danger'];
        }
        $diff = $now->diff($due);
        $days = (int)$diff->format('%a');
        if ($days === 0) return ['Өнөөдөр', 'status-danger'];
        if ($days === 1) return ['Маргааш', 'status-warn'];
        return [$days . ' хоног', 'status-info'];
    } catch (Throwable $e) {
        return ['-', 'status-muted'];
    }
}

$myCourses = count_value('SELECT COUNT(DISTINCT hb.hicheel_bagsh_id) FROM suragch s JOIN hicheel_bagsh hb ON hb.angi_id=s.angi_id WHERE s.suragch_id = ?', [$studentId]);
$activeHomework = count_value("SELECT COUNT(*) FROM geriin_daalgavar gd JOIN hicheel_bagsh hb ON hb.hicheel_bagsh_id=gd.hicheel_bagsh_id JOIN suragch s ON s.angi_id=hb.angi_id LEFT JOIN daalgavriin_hariu dh ON dh.daalgavar_id=gd.daalgavar_id AND dh.suragch_id=s.suragch_id WHERE s.suragch_id=? AND dh.hariu_id IS NULL AND gd.tuluv='Идэвхтэй' AND gd.duusah_ognoo >= NOW()", [$studentId]);
$submitted = count_value('SELECT COUNT(*) FROM daalgavriin_hariu WHERE suragch_id=?', [$studentId]);
$avgScoreRow = one_row('SELECT ROUND(AVG(u.avsan_onoo),2) AS avg_score FROM unelgee u JOIN daalgavriin_hariu dh ON dh.hariu_id=u.hariu_id WHERE dh.suragch_id=?', [$studentId]);
$avgScore = (float)($avgScoreRow['avg_score'] ?? 0);
$totalHomeworks = count_value('SELECT COUNT(*) FROM geriin_daalgavar gd JOIN hicheel_bagsh hb ON hb.hicheel_bagsh_id=gd.hicheel_bagsh_id JOIN suragch s ON s.angi_id=hb.angi_id WHERE s.suragch_id=?', [$studentId]);
$progressPercent = $totalHomeworks > 0 ? min(100, round(($submitted / $totalHomeworks) * 100)) : 0;

$upcomingHomeworks = all_rows(
    'SELECT gd.daalgavar_id, gd.garchig, gd.tailbar, gd.duusah_ognoo, h.hicheel_ner 
     FROM geriin_daalgavar gd
     JOIN hicheel_bagsh hb ON hb.hicheel_bagsh_id=gd.hicheel_bagsh_id
     JOIN suragch s ON s.angi_id=hb.angi_id
     JOIN hicheel h ON h.hicheel_id=hb.hicheel_id
     LEFT JOIN daalgavriin_hariu dh ON dh.daalgavar_id=gd.daalgavar_id AND dh.suragch_id=s.suragch_id
     WHERE s.suragch_id=? AND dh.hariu_id IS NULL AND gd.tuluv="Идэвхтэй" AND gd.duusah_ognoo >= NOW()
     ORDER BY gd.duusah_ognoo ASC LIMIT 3',
    [$studentId]
);

$latestResults = all_rows(
    'SELECT gd.garchig, h.hicheel_ner, u.avsan_onoo, u.bagshiin_tailbar, u.unelsen_ognoo
     FROM unelgee u
     JOIN daalgavriin_hariu dh ON dh.hariu_id=u.hariu_id
     JOIN geriin_daalgavar gd ON gd.daalgavar_id=dh.daalgavar_id
     JOIN hicheel_bagsh hb ON hb.hicheel_bagsh_id=gd.hicheel_bagsh_id
     JOIN hicheel h ON h.hicheel_id=hb.hicheel_id
     WHERE dh.suragch_id=?
     ORDER BY u.unelsen_ognoo DESC LIMIT 2',
    [$studentId]
);

$courseCards = all_rows(
    'SELECT hb.hicheel_bagsh_id, h.hicheel_ner, a.angi_ner,
            COUNT(DISTINCT gd.daalgavar_id) AS niit_daalgavar,
            COUNT(DISTINCT dh.hariu_id) AS ilgeesen_daalgavar
     FROM suragch s
     JOIN hicheel_bagsh hb ON hb.angi_id=s.angi_id
     JOIN hicheel h ON h.hicheel_id=hb.hicheel_id
     JOIN angi a ON a.angi_id=hb.angi_id
     LEFT JOIN geriin_daalgavar gd ON gd.hicheel_bagsh_id=hb.hicheel_bagsh_id
     LEFT JOIN daalgavriin_hariu dh ON dh.daalgavar_id=gd.daalgavar_id AND dh.suragch_id=s.suragch_id
     WHERE s.suragch_id=?
     GROUP BY hb.hicheel_bagsh_id
     ORDER BY h.hicheel_ner ASC LIMIT 4',
    [$studentId]
);

$courseIcons = ['science','history_edu','language','computer'];
$cardAccents = ['#eef2ff','#fff7ed','#ecfdf5','#f5f3ff'];

include __DIR__ . '/header.php';
?>
<style>
.student-dash-grid{display:grid;grid-template-columns:minmax(0,1fr) 330px;gap:28px;align-items:start}
.summary-cards{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}
.summary-card{background:#fff;border:1px solid #e2e8f0;border-radius:18px;padding:18px 18px 16px;box-shadow:0 10px 30px rgba(15,23,42,.05)}
.summary-icon{width:44px;height:44px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:22px;margin-bottom:14px}
.summary-label{font-size:14px;color:#526069}
.summary-value{font-size:34px;font-weight:800;color:#1a237e;line-height:1.1;margin-top:8px}
.summary-value.small{font-size:31px}
.section-title-row{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px}
.section-title{font-size:18px;font-weight:800;color:#1a237e}
.text-link{font-size:14px;color:#1a237e;font-weight:700}
.upcoming-list,.course-card{background:#fff;border:1px solid #e2e8f0;border-radius:18px;box-shadow:0 10px 30px rgba(15,23,42,.05)}
.upcoming-item{display:flex;justify-content:space-between;gap:14px;padding:16px 18px;border-bottom:1px solid #edf2f7}
.upcoming-item:last-child{border-bottom:none}
.upcoming-icon{width:46px;height:46px;border-radius:14px;background:#f1f5f9;color:#1a237e;display:flex;align-items:center;justify-content:center;font-size:21px;flex-shrink:0}
.upcoming-left{display:flex;gap:14px;align-items:flex-start;min-width:0}
.upcoming-title{font-weight:800;color:#111827;font-size:16px}
.upcoming-meta{font-size:13px;color:#64748b;margin-top:4px}
.right-col{display:flex;flex-direction:column;gap:16px}
.result-card{background:#fff;border:1px solid #e2e8f0;border-left-width:3px;border-radius:16px;padding:16px 16px 14px;box-shadow:0 10px 30px rgba(15,23,42,.05)}
.result-top{display:flex;align-items:flex-start;justify-content:space-between;gap:8px}
.result-score{display:inline-flex;align-items:center;border-radius:8px;padding:4px 8px;font-size:12px;font-weight:800;color:#14532d;background:#dcfce7}
.result-score.low{background:#dbeafe;color:#1e3a8a}
.result-title{font-weight:800;color:#0f172a;margin-top:8px}
.result-comment{font-size:13px;color:#475569;margin-top:6px;line-height:1.45}
.result-meta{display:flex;align-items:center;gap:8px;margin-top:14px;font-size:12px;color:#64748b}
.result-dot{width:26px;height:26px;border-radius:999px;background:#e5e7eb}
.progress-card{background:#1a237e;color:#fff;border-radius:18px;padding:18px 18px 16px;box-shadow:0 12px 30px rgba(26,35,126,.22)}
.progress-title{font-size:28px;font-weight:800}
.progress-sub{font-size:13px;font-weight:700;text-transform:uppercase;margin-top:12px;opacity:.9}
.progress-track{margin-top:10px;height:6px;border-radius:999px;background:rgba(255,255,255,.2);overflow:hidden}
.progress-bar{height:100%;background:#7cff75;border-radius:999px}
.progress-meta{display:flex;justify-content:space-between;align-items:center;font-size:13px;margin-top:8px;font-weight:700}
.course-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:18px}
.course-card{padding:18px 18px 54px;position:relative;overflow:hidden;min-height:150px;text-decoration:none;display:block;transition:transform .18s ease, box-shadow .18s ease}
.course-card:hover{transform:translateY(-2px);box-shadow:0 16px 34px rgba(15,23,42,.08)}
.course-icon{width:38px;height:38px;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#1a237e;font-size:19px;margin-bottom:18px}
.course-name{font-weight:800;color:#1a237e;line-height:1.35;margin-bottom:8px}
.course-class{font-size:13px;color:#64748b;line-height:1.35}
.course-footer{position:absolute;left:18px;right:18px;bottom:14px;display:flex;align-items:center;justify-content:flex-end;font-size:12px;color:#64748b;line-height:1.2}
.empty-note{padding:24px;text-align:center;color:#64748b;background:#fff;border:1px solid #e2e8f0;border-radius:18px}
.header-action-btn{display:inline-flex;align-items:center;justify-content:center;background:#1a237e;color:#fff;border-radius:10px;padding:13px 22px;font-weight:700;box-shadow:0 10px 20px rgba(26,35,126,.2)}
.header-row-top{display:flex;justify-content:space-between;gap:18px;align-items:flex-start;margin-bottom:24px}
.status-danger{background:#fee2e2;color:#b91c1c}
@media (max-width: 1200px){.summary-cards,.course-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.student-dash-grid{grid-template-columns:1fr}}
@media (max-width: 700px){.summary-cards,.course-grid{grid-template-columns:1fr}.header-row-top{flex-direction:column}.upcoming-item{flex-direction:column}.page-title{font-size:2rem}}
</style>

<div class="header-row-top">
  <div>
    <h1 class="page-title"></h1>
  </div>
  <a href="submission_create.php" class="header-action-btn">Даалгавар илгээх</a>
</div>

<div class="summary-cards mb-8">
  <?php
  $summary = [
    ['📚', '#eef2ff', 'Миний хичээлүүд', (string)$myCourses],
    ['📋', '#fff7ed', 'Идэвхтэй даалгавар', str_pad((string)$activeHomework, 2, '0', STR_PAD_LEFT)],
    ['📄', '#ecfdf5', 'Илгээсэн даалгавар', (string)$submitted],
    ['⭐', '#f5f3ff', 'Дундаж оноо', $avgScore > 0 ? sd_percent($avgScore) : '0%'],
  ];
  foreach ($summary as [$icon, $bg, $label, $value]): ?>
    <div class="summary-card">
      <div class="summary-icon" style="background: <?= e($bg) ?>"><?= e($icon) ?></div>
      <div class="summary-label"><?= e($label) ?></div>
      <div class="summary-value <?= mb_strlen($value) > 5 ? 'small' : '' ?>"><?= e($value) ?></div>
    </div>
  <?php endforeach; ?>
</div>

<div class="student-dash-grid mb-8">
  <div>
    <div class="section-title-row">
      <div class="section-title">Удахгүй дуусах даалгаврууд</div>
      <a class="text-link" href="student_homeworks.php">Бүгдийг харах</a>
    </div>

    <?php if ($upcomingHomeworks): ?>
      <div class="upcoming-list">
        <?php foreach ($upcomingHomeworks as $i => $row): ?>
          <?php [$badgeText, $badgeClass] = sd_due_badge($row['duusah_ognoo']); ?>
          <div class="upcoming-item">
            <div class="upcoming-left">
              <div class="upcoming-icon"><?= e(['∑','文','⚗️'][$i % 3]) ?></div>
              <div class="min-w-0">
                <div class="upcoming-title"><?= e($row['hicheel_ner']) ?>: <?= e($row['garchig']) ?></div>
                <div class="upcoming-meta">Сэдэв: <?= e(mb_strimwidth((string)($row['tailbar'] ?: 'Даалгаврын тайлбар байхгүй.'), 0, 60, '...')) ?></div>
              </div>
            </div>
            <div class="text-right shrink-0">
              <span class="status-pill <?= e($badgeClass) ?>"><?= e($badgeText) ?></span>
              <div class="upcoming-meta mt-2"><?= e(substr((string)$row['duusah_ognoo'], 0, 10)) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty-note">Одоогоор дуусах хугацаатай идэвхтэй даалгавар алга байна.</div>
    <?php endif; ?>
  </div>

  <div class="right-col">
    <div>
      <div class="section-title-row">
        <div class="section-title">Сүүлийн дүнгүүд</div>
      </div>
      <?php if ($latestResults): ?>
        <div class="space-y-4">
          <?php foreach ($latestResults as $index => $row): ?>
            <?php $low = ((float)$row['avsan_onoo']) < 60; ?>
            <div class="result-card" style="border-left-color: <?= $index === 0 ? '#14532d' : '#1a237e' ?>;">
              <div class="result-top">
                <span class="result-score <?= $low ? 'low' : '' ?>"><?= e(rtrim(rtrim(number_format((float)$row['avsan_onoo'], 2, '.', ''), '0'), '.')) ?> / 100</span>
                <span class="text-xs text-slate-400"><?= e(substr((string)$row['unelsen_ognoo'], 0, 10) === date('Y-m-d') ? 'өнөөдөр' : format_dt($row['unelsen_ognoo'])) ?></span>
              </div>
              <div class="result-title"><?= e($row['garchig']) ?></div>
              <div class="text-sm text-slate-500 mt-1"><?= e($row['hicheel_ner']) ?></div>
              <div class="result-comment">“<?= e($row['bagshiin_tailbar'] ?: 'Тайлбар оруулаагүй.') ?>”</div>
              <div class="result-meta"><span class="result-dot"></span><span>Үсгэн үнэлгээ: <?= e(sd_grade_letter((float)$row['avsan_onoo'])) ?></span></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="empty-note">Үнэлгээтэй хариу одоогоор алга байна.</div>
      <?php endif; ?>
    </div>

    <div class="progress-card">
      <div class="section-title !text-white !mb-0">Сургалтын явц</div>
      <div class="progress-sub">Улирлын бэлтгэл</div>
      <div class="progress-meta"><span></span><span><?= e($progressPercent . '%') ?></span></div>
      <div class="progress-track"><div class="progress-bar" style="width: <?= e((string)$progressPercent) ?>%"></div></div>
      <p class="text-sm mt-4 text-indigo-100">Та өнгөрсөн нийт <?= e((string)$totalHomeworks) ?> даалгавраас <?= e((string)$submitted) ?>-ыг илгээсэн байна.</p>
    </div>
  </div>
</div>

<div>
  <div class="section-title-row">
    <div class="section-title">Миний хичээлүүд</div>
  </div>
  <?php if ($courseCards): ?>
    <div class="course-grid">
      <?php foreach ($courseCards as $idx => $course): ?>
        <?php
          $accent = $cardAccents[$idx % count($cardAccents)];
        ?>
        <a href="student_course_detail.php?id=<?= (int)$course['hicheel_bagsh_id'] ?>" class="course-card block">
          <div class="course-icon" style="background: <?= e($accent) ?>"><span class="material-symbols-outlined"><?= e($courseIcons[$idx % count($courseIcons)]) ?></span></div>
          <div class="course-name"><?= e($course['hicheel_ner']) ?></div>
          <div class="course-class"><?= e($course['angi_ner']) ?> анги</div>
          <div class="course-footer">
            <div><?= e((string)$course['niit_daalgavar']) ?> даалгавар</div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="empty-note">Танд хуваарилагдсан хичээл олдсонгүй.</div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/footer.php'; ?>
