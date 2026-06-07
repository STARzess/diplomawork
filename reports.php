<?php
require_once __DIR__ . '/functions.php';
require_login();
$role = current_role();
$pageTitle = $role === 'Сурагч' ? 'Миний үнэлгээ' : 'Тайлан';

if ($role === 'Сурагч') {
    $studentId = current_user()['id'];

    $rows = all_rows('SELECT h.hicheel_ner, gd.garchig, u.avsan_onoo, u.bagshiin_tailbar, u.unelsen_ognoo
                      FROM unelgee u
                      JOIN daalgavriin_hariu dh ON dh.hariu_id=u.hariu_id
                      JOIN geriin_daalgavar gd ON gd.daalgavar_id=dh.daalgavar_id
                      JOIN hicheel_bagsh hb ON hb.hicheel_bagsh_id=gd.hicheel_bagsh_id
                      JOIN hicheel h ON h.hicheel_id=hb.hicheel_id
                      WHERE dh.suragch_id=?
                      ORDER BY u.unelsen_ognoo DESC', [$studentId]);

    $performanceRows = all_rows('SELECT h.hicheel_ner, gd.garchig, gd.duusah_ognoo,
                                        dh.hariu_id, dh.ilgeesen_ognoo, u.avsan_onoo,
                                        CASE
                                            WHEN dh.hariu_id IS NOT NULL AND dh.ilgeesen_ognoo > gd.duusah_ognoo THEN "Хоцорсон"
                                            WHEN dh.hariu_id IS NOT NULL THEN "Илгээсэн"
                                            WHEN gd.duusah_ognoo < NOW() THEN "Илгээгээгүй"
                                            ELSE "Хүлээгдэж буй"
                                        END AS guitsetgeliin_tuluv
                                 FROM suragch s
                                 JOIN hicheel_bagsh hb ON hb.angi_id=s.angi_id
                                 JOIN hicheel h ON h.hicheel_id=hb.hicheel_id
                                 JOIN geriin_daalgavar gd ON gd.hicheel_bagsh_id=hb.hicheel_bagsh_id
                                 LEFT JOIN daalgavriin_hariu dh ON dh.daalgavar_id=gd.daalgavar_id AND dh.suragch_id=s.suragch_id
                                 LEFT JOIN unelgee u ON u.hariu_id=dh.hariu_id
                                 WHERE s.suragch_id=?
                                 ORDER BY gd.duusah_ognoo DESC, h.hicheel_ner, gd.garchig', [$studentId]);

    $coursePerformanceRows = all_rows('SELECT h.hicheel_ner,
                                              COUNT(DISTINCT gd.daalgavar_id) AS niit,
                                              COUNT(DISTINCT dh.hariu_id) AS ilgeesen,
                                              COUNT(DISTINCT CASE WHEN dh.hariu_id IS NOT NULL AND dh.ilgeesen_ognoo > gd.duusah_ognoo THEN dh.hariu_id END) AS hotsorson,
                                              COUNT(DISTINCT CASE WHEN dh.hariu_id IS NULL AND gd.duusah_ognoo < NOW() THEN gd.daalgavar_id END) AS ilgeegeegui,
                                              ROUND(AVG(u.avsan_onoo),2) AS dundaj_onoo
                                       FROM suragch s
                                       JOIN hicheel_bagsh hb ON hb.angi_id=s.angi_id
                                       JOIN hicheel h ON h.hicheel_id=hb.hicheel_id
                                       JOIN geriin_daalgavar gd ON gd.hicheel_bagsh_id=hb.hicheel_bagsh_id
                                       LEFT JOIN daalgavriin_hariu dh ON dh.daalgavar_id=gd.daalgavar_id AND dh.suragch_id=s.suragch_id
                                       LEFT JOIN unelgee u ON u.hariu_id=dh.hariu_id
                                       WHERE s.suragch_id=?
                                       GROUP BY h.hicheel_id, h.hicheel_ner
                                       ORDER BY h.hicheel_ner', [$studentId]);

    $chartLabels = [];
    $chartScores = [];
    foreach (array_reverse($rows) as $row) {
        $chartLabels[] = $row['garchig'];
        $chartScores[] = (float)$row['avsan_onoo'];
    }

    $totalHomework = count($performanceRows);
    $submittedHomework = 0;
    $lateHomework = 0;
    $notSubmittedHomework = 0;
    $pendingHomework = 0;
    $gradedHomework = 0;

    foreach ($performanceRows as $pRow) {
        if (!empty($pRow['hariu_id'])) $submittedHomework++;
        if ($pRow['guitsetgeliin_tuluv'] === 'Хоцорсон') $lateHomework++;
        if ($pRow['guitsetgeliin_tuluv'] === 'Илгээгээгүй') $notSubmittedHomework++;
        if ($pRow['guitsetgeliin_tuluv'] === 'Хүлээгдэж буй') $pendingHomework++;
        if ($pRow['avsan_onoo'] !== null && $pRow['avsan_onoo'] !== '') $gradedHomework++;
    }

    $completionPercent = $totalHomework > 0 ? min(100, round(($submittedHomework / $totalHomework) * 100)) : 0;
    $gradedPercent = $submittedHomework > 0 ? min(100, round(($gradedHomework / $submittedHomework) * 100)) : 0;
    $avgScore = count($rows) ? round(array_sum(array_map(fn($r) => (float)$r['avsan_onoo'], $rows)) / count($rows), 2) : 0;

    function student_perf_badge(string $status): string {
        if ($status === 'Илгээсэн') return 'bg-blue-100 text-blue-700';
        if ($status === 'Хоцорсон') return 'bg-red-100 text-red-700';
        if ($status === 'Илгээгээгүй') return 'bg-slate-100 text-slate-600';
        return 'bg-amber-100 text-amber-700';
    }

    include __DIR__ . '/header.php';
    ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
      .performance-card{background:#fff;border:1px solid #e2e8f0;border-radius:1rem;box-shadow:0 1px 2px rgba(15,23,42,.04);overflow:hidden}
      .performance-progress{height:12px;border-radius:999px;background:#e5e7eb;overflow:hidden}
      .performance-progress span{display:block;height:100%;border-radius:inherit;background:#1a237e}
      .perf-stat{border:1px solid #e2e8f0;background:#f8fafc;border-radius:14px;padding:14px 16px}
      .perf-stat .label{font-size:13px;color:#64748b;margin-bottom:6px}.perf-stat .value{font-weight:800;font-size:22px;color:#1a237e}
      .perf-row{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:14px 0;border-top:1px solid #edf2f7}
      .perf-row:first-child{border-top:0}.perf-pill{display:inline-flex;border-radius:999px;padding:6px 11px;font-size:12px;font-weight:800;white-space:nowrap}
    </style>
    <div class="grid xl:grid-cols-3 gap-6 mb-6">
      <div class="card p-5 xl:col-span-2">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 class="font-semibold text-lg">Миний үнэлгээ</h3>
            <p class="page-subtitle">Даалгавар бүрийн үнэлгээний өөрчлөлтийг графикаар харуулна.</p>
          </div>
        </div>
        <div class="chart-wrap"><canvas id="gradeChart"></canvas></div>
      </div>
      <div class="form-card p-6">
        <h3 class="font-semibold text-lg mb-4">Хураангуй</h3>
        <div class="space-y-4">
          <div class="summary-item"><span>Нийт үнэлгээ</span><strong><?= e((string)count($rows)) ?></strong></div>
          <div class="summary-item"><span>Дундаж оноо</span><strong><?= e((string)$avgScore) ?></strong></div>
        </div>
      </div>
    </div>

    <div class="grid xl:grid-cols-3 gap-6 mb-6">
      <div class="performance-card xl:col-span-2 p-6">
        <div class="flex items-start justify-between gap-4 mb-5">
          <div>
            <h3 class="font-semibold text-lg">Миний даалгаврын гүйцэтгэл</h3>
            <p class="page-subtitle">Өөрийн хичээлүүдийн даалгавар илгээсэн, хоцорсон, хүлээгдэж буй төлөвийг нэгтгэн харуулна.</p>
          </div>
          <div class="text-right">
            <div class="text-3xl font-extrabold text-primary"><?= e((string)$completionPercent) ?>%</div>
            <div class="text-sm text-slate-500">Нийт гүйцэтгэл</div>
          </div>
        </div>
        <div class="performance-progress mb-5"><span style="width:<?= e((string)$completionPercent) ?>%"></span></div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
          <div class="perf-stat"><div class="label">Нийт даалгавар</div><div class="value"><?= e((string)$totalHomework) ?></div></div>
          <div class="perf-stat"><div class="label">Илгээсэн</div><div class="value text-green-700"><?= e((string)$submittedHomework) ?></div></div>
          <div class="perf-stat"><div class="label">Хоцорсон</div><div class="value text-red-600"><?= e((string)$lateHomework) ?></div></div>
          <div class="perf-stat"><div class="label">Илгээгээгүй</div><div class="value text-slate-700"><?= e((string)$notSubmittedHomework) ?></div></div>
        </div>
        <div class="text-sm text-slate-600">Илгээсэн хариунаас <?= e((string)$gradedPercent) ?>% нь үнэлэгдсэн байна.</div>
      </div>

      <div class="performance-card p-6">
        <h3 class="font-semibold text-lg mb-4">Хичээлээр</h3>
        <div class="space-y-4">
          <?php foreach ($coursePerformanceRows as $course):
            $courseTotal = (int)$course['niit'];
            $courseSubmitted = (int)$course['ilgeesen'];
            $coursePercent = $courseTotal > 0 ? min(100, round(($courseSubmitted / $courseTotal) * 100)) : 0;
          ?>
            <div>
              <div class="flex justify-between gap-3 mb-2">
                <strong class="text-sm text-slate-900"><?= e($course['hicheel_ner']) ?></strong>
                <span class="text-sm font-bold text-primary"><?= e((string)$coursePercent) ?>%</span>
              </div>
              <div class="performance-progress" style="height:8px"><span style="width:<?= e((string)$coursePercent) ?>%"></span></div>
              <div class="text-xs text-slate-500 mt-2"><?= e((string)$courseSubmitted) ?>/<?= e((string)$courseTotal) ?> даалгавар • Дундаж: <?= e((string)($course['dundaj_onoo'] ?? 0)) ?></div>
            </div>
          <?php endforeach; ?>
          <?php if (!$coursePerformanceRows): ?><div class="text-sm text-slate-500">Гүйцэтгэлийн мэдээлэл алга.</div><?php endif; ?>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border overflow-hidden mb-6">
      <div class="p-5 border-b"><h3 class="font-semibold text-lg">Даалгаврын гүйцэтгэлийн жагсаалт</h3></div>
      <div class="p-5">
        <?php foreach (array_slice($performanceRows, 0, 8) as $pRow): ?>
          <div class="perf-row">
            <div>
              <div class="font-semibold text-slate-900"><?= e($pRow['garchig']) ?></div>
              <div class="text-sm text-slate-500"><?= e($pRow['hicheel_ner']) ?> • Дуусах: <?= e($pRow['duusah_ognoo']) ?></div>
            </div>
            <div class="text-right">
              <span class="perf-pill <?= e(student_perf_badge($pRow['guitsetgeliin_tuluv'])) ?>"><?= e($pRow['guitsetgeliin_tuluv']) ?></span>
              <div class="text-sm text-slate-500 mt-2"><?= $pRow['avsan_onoo'] !== null ? 'Оноо: ' . e((string)$pRow['avsan_onoo']) : 'Оноо: -' ?></div>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (!$performanceRows): ?><div class="empty-state">Одоогоор даалгаврын гүйцэтгэлийн мэдээлэл алга.</div><?php endif; ?>
      </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
      <div class="p-5 border-b"><h3 class="font-semibold text-lg">Үнэлгээний дэлгэрэнгүй жагсаалт</h3></div>
      <table class="w-full text-sm">
        <thead class="bg-slate-50"><tr><th class="text-left p-4">Хичээл</th><th class="text-left p-4">Даалгавар</th><th class="text-left p-4">Оноо</th><th class="text-left p-4">Тайлбар</th><th class="text-left p-4">Огноо</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
          <tr class="border-t"><td class="p-4"><?= e($row['hicheel_ner']) ?></td><td class="p-4"><?= e($row['garchig']) ?></td><td class="p-4 font-semibold text-primary"><?= e((string)$row['avsan_onoo']) ?></td><td class="p-4"><?= e((string)$row['bagshiin_tailbar']) ?></td><td class="p-4"><?= e($row['unelsen_ognoo']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="6" class="empty-state">Одоогоор үнэлгээ бүртгэгдээгүй байна.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
    <script>
      const gradeCtx = document.getElementById('gradeChart');
      if (gradeCtx) {
        new Chart(gradeCtx, {
          type: 'bar',
          data: {
            labels: <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>,
            datasets: [{ label: 'Оноо', data: <?= json_encode($chartScores, JSON_UNESCAPED_UNICODE) ?>, backgroundColor: 'rgba(79, 70, 229, 0.75)', borderRadius: 10, maxBarThickness: 48 }]
          },
          options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, suggestedMax: 100, ticks: { stepSize: 10 } } } }
        });
      }
    </script>
    <?php include __DIR__ . '/footer.php'; exit; }

require_roles(['Админ','Сургалтын алба','Багш']);

$teacherId = (int)current_user()['id'];
$isTeacher = $role === 'Багш';
$report = $_GET['report'] ?? 'grade-report';
if (!in_array($report, ['grade-report', 'performance-report'], true)) {
    $report = 'grade-report';
}

$pageTitle = $report === 'grade-report' ? 'Сурагчдын үнэлгээний тайлан' : 'Сурагчдын гүйцэтгэлийн тайлан';

$startDate = trim($_GET['start_date'] ?? '');
$endDate = trim($_GET['end_date'] ?? '');
$angiId = (int)($_GET['angi_id'] ?? 0);
$hicheelId = (int)($_GET['hicheel_id'] ?? 0);
$suragchId = (int)($_GET['suragch_id'] ?? 0);
$tuluv = trim($_GET['tuluv'] ?? '');

$classes = $isTeacher
    ? all_rows('SELECT DISTINCT a.angi_id, a.angi_ner FROM angi a JOIN hicheel_bagsh hb ON hb.angi_id=a.angi_id WHERE hb.bagsh_id=? ORDER BY a.angi_ner', [$teacherId])
    : all_rows('SELECT angi_id, angi_ner FROM angi ORDER BY angi_ner');

$courses = $isTeacher
    ? all_rows('SELECT DISTINCT h.hicheel_id, h.hicheel_ner, h.hicheel_kod FROM hicheel h JOIN hicheel_bagsh hb ON hb.hicheel_id=h.hicheel_id WHERE hb.bagsh_id=? ORDER BY h.hicheel_ner', [$teacherId])
    : all_rows('SELECT hicheel_id, hicheel_ner, hicheel_kod FROM hicheel ORDER BY hicheel_ner');

$studentSql = $isTeacher
    ? 'SELECT DISTINCT s.suragch_id, s.ovog, s.ner, a.angi_ner FROM suragch s JOIN angi a ON a.angi_id=s.angi_id JOIN hicheel_bagsh hb ON hb.angi_id=s.angi_id WHERE hb.bagsh_id=? AND s.tuluv="Идэвхтэй"'
    : 'SELECT s.suragch_id, s.ovog, s.ner, a.angi_ner FROM suragch s JOIN angi a ON a.angi_id=s.angi_id WHERE s.tuluv="Идэвхтэй"';
$studentParams = $isTeacher ? [$teacherId] : [];
if ($angiId > 0) {
    $studentSql .= ' AND s.angi_id=?';
    $studentParams[] = $angiId;
}
$studentSql .= ' ORDER BY a.angi_ner, s.ovog, s.ner';
$students = all_rows($studentSql, $studentParams);

function report_query_string(array $override = []): string {
    $data = array_merge($_GET, $override);
    foreach ($data as $k => $v) {
        if ($v === '' || $v === null || $v === 0 || $v === '0') unset($data[$k]);
    }
    return http_build_query($data);
}

$gradeRows = [];
$performanceRows = [];
$summary = [
    'total' => 0,
    'avg' => 0,
    'submitted' => 0,
    'graded' => 0,
    'late' => 0,
    'not_submitted' => 0,
];

if ($report === 'grade-report') {
    $where = [];
    $params = [];
    $sql = 'SELECT s.suragch_id, CONCAT(s.ovog," ",s.ner) AS suragch_ner, a.angi_ner,
                   h.hicheel_ner, h.hicheel_kod, gd.garchig,
                   dh.ilgeesen_ognoo, u.unelsen_ognoo, u.avsan_onoo, u.bagshiin_tailbar,
                   gd.hamgiin_ih_onoo
            FROM unelgee u
            JOIN daalgavriin_hariu dh ON dh.hariu_id=u.hariu_id
            JOIN suragch s ON s.suragch_id=dh.suragch_id
            JOIN angi a ON a.angi_id=s.angi_id
            JOIN geriin_daalgavar gd ON gd.daalgavar_id=dh.daalgavar_id
            JOIN hicheel_bagsh hb ON hb.hicheel_bagsh_id=gd.hicheel_bagsh_id
            JOIN hicheel h ON h.hicheel_id=hb.hicheel_id';
    if ($isTeacher) { $where[] = 'hb.bagsh_id=?'; $params[] = $teacherId; }
    if ($startDate !== '') { $where[] = 'DATE(u.unelsen_ognoo) >= ?'; $params[] = $startDate; }
    if ($endDate !== '') { $where[] = 'DATE(u.unelsen_ognoo) <= ?'; $params[] = $endDate; }
    if ($angiId > 0) { $where[] = 'a.angi_id=?'; $params[] = $angiId; }
    if ($hicheelId > 0) { $where[] = 'h.hicheel_id=?'; $params[] = $hicheelId; }
    if ($suragchId > 0) { $where[] = 's.suragch_id=?'; $params[] = $suragchId; }
    if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
    $sql .= ' ORDER BY u.unelsen_ognoo DESC, a.angi_ner, s.ovog, s.ner';
    $gradeRows = all_rows($sql, $params);

    $summary['total'] = count($gradeRows);
    $summary['avg'] = $gradeRows ? round(array_sum(array_map(fn($r) => (float)$r['avsan_onoo'], $gradeRows)) / count($gradeRows), 2) : 0;
} else {
    $where = [];
    $params = [];
    $sql = 'SELECT s.suragch_id, CONCAT(s.ovog," ",s.ner) AS suragch_ner, a.angi_ner,
                   h.hicheel_ner, h.hicheel_kod, gd.garchig,
                   gd.duusah_ognoo, gd.tuluv AS daalgavar_tuluv,
                   dh.hariu_id, dh.ilgeesen_ognoo, dh.tuluv AS hariu_tuluv,
                   u.avsan_onoo,
                   CASE
                       WHEN dh.hariu_id IS NOT NULL AND dh.ilgeesen_ognoo > gd.duusah_ognoo THEN "Хоцорсон"
                       WHEN dh.hariu_id IS NOT NULL THEN "Илгээсэн"
                       WHEN gd.duusah_ognoo < NOW() THEN "Илгээгээгүй"
                       ELSE "Хүлээгдэж буй"
                   END AS guitsetgeliin_tuluv
            FROM suragch s
            JOIN angi a ON a.angi_id=s.angi_id
            JOIN hicheel_bagsh hb ON hb.angi_id=s.angi_id
            JOIN hicheel h ON h.hicheel_id=hb.hicheel_id
            JOIN geriin_daalgavar gd ON gd.hicheel_bagsh_id=hb.hicheel_bagsh_id
            LEFT JOIN daalgavriin_hariu dh ON dh.daalgavar_id=gd.daalgavar_id AND dh.suragch_id=s.suragch_id
            LEFT JOIN unelgee u ON u.hariu_id=dh.hariu_id
            WHERE s.tuluv="Идэвхтэй"';
    if ($isTeacher) { $where[] = 'hb.bagsh_id=?'; $params[] = $teacherId; }

    if ($startDate !== '' && $endDate !== '') {
        $where[] = '(
            DATE(gd.ogson_ognoo) BETWEEN ? AND ?
            OR DATE(gd.duusah_ognoo) BETWEEN ? AND ?
            OR (dh.ilgeesen_ognoo IS NOT NULL AND DATE(dh.ilgeesen_ognoo) BETWEEN ? AND ?)
            OR (u.unelsen_ognoo IS NOT NULL AND DATE(u.unelsen_ognoo) BETWEEN ? AND ?)
        )';
        array_push($params, $startDate, $endDate, $startDate, $endDate, $startDate, $endDate, $startDate, $endDate);
    } elseif ($startDate !== '') {
        $where[] = '(
            DATE(gd.ogson_ognoo) >= ?
            OR DATE(gd.duusah_ognoo) >= ?
            OR (dh.ilgeesen_ognoo IS NOT NULL AND DATE(dh.ilgeesen_ognoo) >= ?)
            OR (u.unelsen_ognoo IS NOT NULL AND DATE(u.unelsen_ognoo) >= ?)
        )';
        array_push($params, $startDate, $startDate, $startDate, $startDate);
    } elseif ($endDate !== '') {
        $where[] = '(
            DATE(gd.ogson_ognoo) <= ?
            OR DATE(gd.duusah_ognoo) <= ?
            OR (dh.ilgeesen_ognoo IS NOT NULL AND DATE(dh.ilgeesen_ognoo) <= ?)
            OR (u.unelsen_ognoo IS NOT NULL AND DATE(u.unelsen_ognoo) <= ?)
        )';
        array_push($params, $endDate, $endDate, $endDate, $endDate);
    }

    if ($angiId > 0) { $where[] = 'a.angi_id=?'; $params[] = $angiId; }
    if ($hicheelId > 0) { $where[] = 'h.hicheel_id=?'; $params[] = $hicheelId; }
    if ($tuluv !== '') {
        if ($tuluv === 'Илгээсэн') $where[] = 'dh.hariu_id IS NOT NULL AND dh.ilgeesen_ognoo <= gd.duusah_ognoo';
        elseif ($tuluv === 'Хоцорсон') $where[] = 'dh.hariu_id IS NOT NULL AND dh.ilgeesen_ognoo > gd.duusah_ognoo';
        elseif ($tuluv === 'Илгээгээгүй') $where[] = 'dh.hariu_id IS NULL AND gd.duusah_ognoo < NOW()';
        elseif ($tuluv === 'Хүлээгдэж буй') $where[] = 'dh.hariu_id IS NULL AND gd.duusah_ognoo >= NOW()';
    }
    if ($where) $sql .= ' AND ' . implode(' AND ', $where);
    $sql .= ' ORDER BY gd.duusah_ognoo DESC, a.angi_ner, s.ovog, s.ner';
    $performanceRows = all_rows($sql, $params);

    $summary['total'] = count($performanceRows);
    foreach ($performanceRows as $r) {
        if (!empty($r['hariu_id'])) $summary['submitted']++;
        if ($r['avsan_onoo'] !== null && $r['avsan_onoo'] !== '') $summary['graded']++;
        if ($r['guitsetgeliin_tuluv'] === 'Хоцорсон') $summary['late']++;
        if ($r['guitsetgeliin_tuluv'] === 'Илгээгээгүй') $summary['not_submitted']++;
    }
}


$printStartDate = $startDate !== '' ? $startDate : '-';
$printEndDate = $endDate !== '' ? $endDate : '-';
$printDateRange = ($startDate !== '' || $endDate !== '') ? ($printStartDate . ' - ' . $printEndDate) : 'Бүх хугацаа';
$printedBy = trim((current_user()['name'] ?? '') . ' / ' . $role);
$printedAt = date('Y-m-d H:i');

include __DIR__ . '/header.php';
?>
<style>
  @media screen { .print-only { display: none !important; } }
  @media print {
    @page { size: A4 landscape; margin: 6mm; }
    html, body { background: #fff !important; font-family: Arial, Tahoma, "DejaVu Sans", sans-serif !important; color: #111827 !important; }
    body { zoom: 0.72; }
    aside, body > div > aside, main > header, .no-print, .report-tabs, .report-filter { display: none !important; }
    main { margin-left: 0 !important; width: 100% !important; max-width: none !important; }
    main .p-8 { padding: 0 !important; }
    .print-only { display: block !important; }
    .print-report-brand { display: flex !important; align-items: center !important; gap: 8px !important; margin-bottom: 6px !important; }
    .print-report-logo { width: 30px !important; height: 30px !important; border-radius: 8px !important; background: #1A237E !important; color: #fff !important; display: flex !important; align-items: center !important; justify-content: center !important; flex: 0 0 30px !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .print-report-logo svg { width: 20px !important; height: 20px !important; }
    .print-report-title-main { font-size: 18px !important; line-height: 1.05 !important; font-weight: 800 !important; color: #111827 !important; letter-spacing: 0 !important; }
    .print-report-title-sub { font-size: 10px !important; color: #475569 !important; margin-top: 2px !important; letter-spacing: 0 !important; }
    .print-report-heading { text-align: center !important; font-size: 20px !important; font-weight: 800 !important; margin: 6px 0 10px !important; letter-spacing: 0 !important; }
    .print-meta-grid { display: grid !important; grid-template-columns: repeat(3, 1fr) !important; gap: 6px !important; margin-bottom: 8px !important; }
    .print-meta-box { border: 1px solid #cbd5e1 !important; padding: 6px 8px !important; min-height: 34px !important; }
    .print-meta-label { display: block !important; font-size: 10px !important; color: #475569 !important; margin-bottom: 2px !important; }
    .print-meta-value { font-size: 12px !important; font-weight: 800 !important; color: #111827 !important; }
    .print-separator { border: 0 !important; border-top: 2px solid #111827 !important; margin: 6px 0 8px !important; }
    .rounded-2xl, .rounded-xl, .rounded-full { border-radius: 0 !important; }
    .shadow-sm, .shadow, .card { box-shadow: none !important; }
    .bg-white { background: #fff !important; }
    .overflow-auto { overflow: visible !important; }
    .grid.md\:grid-cols-3 { display: grid !important; grid-template-columns: repeat(3, 1fr) !important; gap: 8px !important; margin-bottom: 8px !important; }
    .grid.md\:grid-cols-4 { display: grid !important; grid-template-columns: repeat(4, 1fr) !important; gap: 8px !important; margin-bottom: 8px !important; }
    .grid.md\:grid-cols-3 > div, .grid.md\:grid-cols-4 > div { padding: 10px !important; }
    .text-3xl { font-size: 22px !important; line-height: 1.1 !important; }
    .p-5 { padding: 10px !important; }
    .mb-6 { margin-bottom: 8px !important; }
    table { width: 100% !important; min-width: 0 !important; border-collapse: collapse !important; page-break-inside: auto !important; table-layout: fixed !important; }
    thead { display: table-header-group !important; }
    tr { page-break-inside: avoid !important; page-break-after: auto !important; }
    th, td { border: 1px solid #cbd5e1 !important; padding: 5px 6px !important; font-size: 9px !important; line-height: 1.25 !important; vertical-align: middle !important; word-break: break-word !important; overflow-wrap: anywhere !important; }
    th { background: #f1f5f9 !important; font-weight: 800 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .min-w-\[980px\], .min-w-\[1100px\] { min-width: 0 !important; }
    .print-signature { display: block !important; width: 280px !important; margin: 12px 0 0 auto !important; page-break-inside: avoid !important; text-align: center !important; color: #475569 !important; }
    .print-signature-title { font-size: 10px !important; margin-bottom: 6px !important; }
    .print-signature-line { border-bottom: 1px solid #111827 !important; height: 18px !important; margin-bottom: 4px !important; }
    .print-signature-name { font-size: 10px !important; font-weight: 800 !important; color: #111827 !important; }
  }
</style>

<div class="print-only">
  <div class="print-report-brand">
    <div class="print-report-logo" aria-hidden="true">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M12 3 2.5 8 12 13 21.5 8 12 3Z" stroke="white" stroke-width="1.8" stroke-linejoin="round"/>
        <path d="M5.5 10.2V15.4C5.5 16.1 6.05 16.75 7.15 17.35 9.75 18.8 14.25 18.8 16.85 17.35 17.95 16.75 18.5 16.1 18.5 15.4V10.2" stroke="white" stroke-width="1.8" stroke-linecap="round"/>
        <path d="M21.5 8V14" stroke="white" stroke-width="1.8" stroke-linecap="round"/>
      </svg>
    </div>
    <div>
      <div class="print-report-title-main">Гэрийн даалгаврын систем</div>
      <div class="print-report-title-sub">Сурагчдын гэрийн даалгаврын систем</div>
    </div>
  </div>
  <div class="print-report-heading"><?= e($pageTitle) ?></div>
  <div class="print-meta-grid">
    <div class="print-meta-box"><span class="print-meta-label">Тайлан хэвлэсэн огноо:</span><span class="print-meta-value"><?= e($printedAt) ?></span></div>
    <div class="print-meta-box"><span class="print-meta-label">Тайлангийн огноо:</span><span class="print-meta-value"><?= e($printDateRange) ?></span></div>
    <div class="print-meta-box"><span class="print-meta-label">Хэвлэсэн хүн:</span><span class="print-meta-value"><?= e($printedBy) ?></span></div>
  </div>
  <hr class="print-separator">
</div>

<div class="grid md:grid-cols-2 gap-4 mb-6 report-tabs no-print">
  <a href="reports.php?<?= e(report_query_string(['report'=>'grade-report'])) ?>" class="rounded-2xl border p-5 transition shadow-sm <?= $report === 'grade-report' ? 'bg-primary text-white border-primary' : 'bg-white hover:border-indigo-300 hover:shadow' ?>">
    <div class="text-base font-bold leading-6">Сурагчдын үнэлгээний тайлан</div>
  </a>
  <a href="reports.php?<?= e(report_query_string(['report'=>'performance-report'])) ?>" class="rounded-2xl border p-5 transition shadow-sm <?= $report === 'performance-report' ? 'bg-primary text-white border-primary' : 'bg-white hover:border-indigo-300 hover:shadow' ?>">
    <div class="text-base font-bold leading-6">Сурагчдын гүйцэтгэлийн тайлан</div>
  </a>
</div>

<div class="bg-white rounded-2xl shadow-sm border overflow-hidden mb-6 report-filter no-print">
  <div class="p-5 border-b">
    <h3 class="font-semibold text-lg">Тайлангийн шүүлтүүр</h3>
    <p class="text-sm text-slate-500 mt-1"><?= $report === 'grade-report' ? 'Сурагчдын үнэлгээний тайлан гаргах шүүлтүүрүүд' : 'Сурагчдын гүйцэтгэлийн тайлан гаргах шүүлтүүрүүд' ?></p>
  </div>
  <form method="get" class="p-5 grid md:grid-cols-2 xl:grid-cols-4 gap-4 items-end">
    <input type="hidden" name="report" value="<?= e($report) ?>">
    <div>
      <label class="block text-sm font-medium mb-2">Эхлэх огноо</label>
      <input type="date" name="start_date" value="<?= e($startDate) ?>" class="w-full rounded-xl border-slate-300 px-4 py-3">
    </div>
    <div>
      <label class="block text-sm font-medium mb-2">Дуусах огноо</label>
      <input type="date" name="end_date" value="<?= e($endDate) ?>" class="w-full rounded-xl border-slate-300 px-4 py-3">
    </div>
    <div>
      <label class="block text-sm font-medium mb-2">Анги</label>
      <select name="angi_id" class="w-full rounded-xl border-slate-300 px-4 py-3">
        <option value="0">Бүх анги</option>
        <?php foreach ($classes as $class): ?>
          <option value="<?= (int)$class['angi_id'] ?>" <?= $angiId === (int)$class['angi_id'] ? 'selected' : '' ?>><?= e($class['angi_ner']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium mb-2">Хичээл</label>
      <select name="hicheel_id" class="w-full rounded-xl border-slate-300 px-4 py-3">
        <option value="0">Бүх хичээл</option>
        <?php foreach ($courses as $course): ?>
          <option value="<?= (int)$course['hicheel_id'] ?>" <?= $hicheelId === (int)$course['hicheel_id'] ? 'selected' : '' ?>><?= e(($course['hicheel_kod'] ? $course['hicheel_kod'].' - ' : '').$course['hicheel_ner']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php if ($report === 'grade-report'): ?>
    <div>
      <label class="block text-sm font-medium mb-2">Сурагч</label>
      <select name="suragch_id" class="w-full rounded-xl border-slate-300 px-4 py-3">
        <option value="0">Бүх сурагч</option>
        <?php foreach ($students as $student): ?>
          <option value="<?= (int)$student['suragch_id'] ?>" <?= $suragchId === (int)$student['suragch_id'] ? 'selected' : '' ?>><?= e($student['ovog'].' '.$student['ner'].' / '.($student['angi_ner'] ?? '-')) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php else: ?>
    <div>
      <label class="block text-sm font-medium mb-2">Төлөв</label>
      <select name="tuluv" class="w-full rounded-xl border-slate-300 px-4 py-3">
        <option value="">Бүх төлөв</option>
        <?php foreach (['Илгээсэн','Хоцорсон','Илгээгээгүй','Хүлээгдэж буй'] as $st): ?>
          <option value="<?= e($st) ?>" <?= $tuluv === $st ? 'selected' : '' ?>><?= e($st) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <div class="flex gap-2 xl:col-span-2">
      <button class="rounded-xl bg-primary text-white px-5 py-3 font-medium">Тайлан гаргах</button>
      <button type="button" onclick="window.print()" class="inline-flex items-center justify-center rounded-xl border border-primary bg-white px-5 py-3 text-sm font-semibold text-primary">Хэвлэх</button>
      <a href="reports.php?report=<?= e($report) ?>" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm text-slate-700">Цэвэрлэх</a>
    </div>
  </form>
</div>

<?php if ($report === 'grade-report'): ?>
<div class="grid md:grid-cols-3 gap-4 mb-6">
  <div class="bg-white rounded-2xl border p-5"><div class="text-sm text-slate-500">Нийт үнэлгээ</div><div class="text-3xl font-extrabold text-primary mt-2"><?= e((string)$summary['total']) ?></div></div>
  <div class="bg-white rounded-2xl border p-5"><div class="text-sm text-slate-500">Дундаж оноо</div><div class="text-3xl font-extrabold text-primary mt-2"><?= e((string)$summary['avg']) ?></div></div>
  <div class="bg-white rounded-2xl border p-5"><div class="text-sm text-slate-500">Үсгэн үнэлгээ</div><div class="text-3xl font-extrabold text-primary mt-2"><?= e(score_letter((float)$summary['avg'])) ?></div></div>
</div>
<div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
  <div class="p-5 border-b">
    <h3 class="font-semibold text-lg">Сурагчдын үнэлгээний тайлан</h3>

  </div>
  <div class="overflow-auto">
    <table class="w-full text-sm min-w-[980px]">
      <thead class="bg-slate-50"><tr><th class="text-left p-4">Сурагч</th><th class="text-left p-4">Анги</th><th class="text-left p-4">Хичээл</th><th class="text-left p-4">Даалгавар</th><th class="text-left p-4">Илгээсэн</th><th class="text-left p-4">Үнэлсэн</th><th class="text-left p-4">Оноо</th><th class="text-left p-4">Үнэлгээ</th><th class="text-left p-4">Тайлбар</th></tr></thead>
      <tbody>
      <?php foreach ($gradeRows as $row): ?>
        <tr class="border-t hover:bg-slate-50/60">
          <td class="p-4 font-medium"><?= e($row['suragch_ner']) ?></td>
          <td class="p-4"><?= e($row['angi_ner']) ?></td>
          <td class="p-4"><?= e(($row['hicheel_kod'] ? $row['hicheel_kod'].' - ' : '').$row['hicheel_ner']) ?></td>
          <td class="p-4 font-medium"><?= e($row['garchig']) ?></td>
          <td class="p-4 whitespace-nowrap"><?= e(format_dt($row['ilgeesen_ognoo'])) ?></td>
          <td class="p-4 whitespace-nowrap"><?= e(format_dt($row['unelsen_ognoo'])) ?></td>
          <td class="p-4 font-semibold text-primary"><?= e(number_format((float)$row['avsan_onoo'], 2)) ?> / <?= e((string)$row['hamgiin_ih_onoo']) ?></td>
          <td class="p-4 font-bold"><?= e(score_letter((float)$row['avsan_onoo'])) ?></td>
          <td class="p-4 text-slate-600"><?= e((string)$row['bagshiin_tailbar']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$gradeRows): ?><tr><td colspan="9" class="empty-state">Шүүлтэд тохирох үнэлгээний мэдээлэл олдсонгүй.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="print-signature">
    <div class="print-signature-title">Хянасан</div>
    <div class="print-signature-line"></div>

  </div>
</div>
<?php else: ?>
<div class="grid md:grid-cols-4 gap-4 mb-6">
  <div class="bg-white rounded-2xl border p-5"><div class="text-sm text-slate-500">Нийт мөр</div><div class="text-3xl font-extrabold text-primary mt-2"><?= e((string)$summary['total']) ?></div></div>
  <div class="bg-white rounded-2xl border p-5"><div class="text-sm text-slate-500">Илгээсэн</div><div class="text-3xl font-extrabold text-green-700 mt-2"><?= e((string)$summary['submitted']) ?></div></div>
  <div class="bg-white rounded-2xl border p-5"><div class="text-sm text-slate-500">Хоцорсон</div><div class="text-3xl font-extrabold text-red-700 mt-2"><?= e((string)$summary['late']) ?></div></div>
  <div class="bg-white rounded-2xl border p-5"><div class="text-sm text-slate-500">Илгээгээгүй</div><div class="text-3xl font-extrabold text-slate-700 mt-2"><?= e((string)$summary['not_submitted']) ?></div></div>
</div>
<div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
  <div class="p-5 border-b">
    <h3 class="font-semibold text-lg">Сурагчдын гүйцэтгэлийн тайлан</h3>
  </div>
  <div class="overflow-auto">
    <table class="w-full text-sm min-w-[980px]">
      <thead class="bg-slate-50"><tr><th class="text-left p-4">Сурагч</th><th class="text-left p-4">Анги</th><th class="text-left p-4">Хичээл</th><th class="text-left p-4">Даалгавар</th><th class="text-left p-4">Дуусах огноо</th><th class="text-left p-4">Илгээсэн огноо</th><th class="text-left p-4">Төлөв</th><th class="text-left p-4">Оноо</th></tr></thead>
      <tbody>
      <?php foreach ($performanceRows as $row): ?>
        <?php
          $status = $row['guitsetgeliin_tuluv'];
          $badge = 'bg-slate-100 text-slate-700';
          if ($status === 'Илгээсэн') $badge = 'bg-green-100 text-green-700';
          if ($status === 'Хоцорсон') $badge = 'bg-red-100 text-red-700';
          if ($status === 'Хүлээгдэж буй') $badge = 'bg-blue-100 text-blue-700';
        ?>
        <tr class="border-t hover:bg-slate-50/60">
          <td class="p-4 font-medium"><?= e($row['suragch_ner']) ?></td>
          <td class="p-4"><?= e($row['angi_ner']) ?></td>
          <td class="p-4"><?= e(($row['hicheel_kod'] ? $row['hicheel_kod'].' - ' : '').$row['hicheel_ner']) ?></td>
          <td class="p-4 font-medium"><?= e($row['garchig']) ?></td>
          <td class="p-4 whitespace-nowrap"><?= e(format_dt($row['duusah_ognoo'])) ?></td>
          <td class="p-4 whitespace-nowrap"><?= e(format_dt($row['ilgeesen_ognoo'])) ?></td>
          <td class="p-4"><span class="px-3 py-1 rounded-full text-xs font-semibold whitespace-nowrap <?= $badge ?>"><?= e($status) ?></span></td>
          <td class="p-4 font-semibold text-primary"><?= e($row['avsan_onoo'] !== null ? number_format((float)$row['avsan_onoo'], 2) : '-') ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$performanceRows): ?><tr><td colspan="8" class="empty-state">Шүүлтэд тохирох гүйцэтгэлийн мэдээлэл олдсонгүй.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="print-signature">
    <div class="print-signature-title">Хянасан</div>
    <div class="print-signature-line"></div>
  </div>
</div>
<?php endif; ?>
<?php include __DIR__ . '/footer.php'; ?>
