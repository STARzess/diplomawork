<?php
require_once __DIR__ . '/functions.php';
ensure_gamified_homework_schema();
require_roles(['Багш']);
close_expired_homeworks();

$teacherId = (int)current_user()['id'];
$pageTitle = 'Гэрийн даалгавар';

$q = trim($_GET['q'] ?? '');
$filterAngi = (int)($_GET['filter_angi'] ?? 0);
$filterTuluv = trim($_GET['filter_tuluv'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_homework') {
    $deleteId = (int)($_POST['daalgavar_id'] ?? 0);
    if ($deleteId > 0) {
        $owner = one_row('SELECT gd.daalgavar_id
                          FROM geriin_daalgavar gd
                          JOIN hicheel_bagsh hb ON hb.hicheel_bagsh_id = gd.hicheel_bagsh_id
                          WHERE gd.daalgavar_id = ? AND hb.bagsh_id = ?
                          LIMIT 1', [$deleteId, $teacherId]);
        if ($owner) {
            db()->prepare('DELETE FROM geriin_daalgavar WHERE daalgavar_id = ?')->execute([$deleteId]);
            flash('Гэрийн даалгавар амжилттай устлаа.');
        } else {
            flash('Энэ даалгаврыг устгах эрхгүй байна.', 'error');
        }
    } else {
        flash('Устгах даалгавар олдсонгүй.', 'error');
    }
    $redirect = 'teacher_homeworks.php';
    $query = $_GET ? ('?' . http_build_query($_GET)) : '';
    header('Location: ' . $redirect . $query);
    exit;
}

$sql = 'SELECT gd.daalgavar_id, gd.garchig, gd.tailbar, \'Энгийн\' AS daalgavar_turul, gd.togloomchilson, gd.togloom_turul, gd.od_shagnal, gd.turshlaga_onoo, gd.ogson_ognoo, gd.duusah_ognoo, gd.hamgiin_ih_onoo, gd.tuluv,
               h.hicheel_ner, h.hicheel_kod, a.angi_id, a.angi_ner,
               COUNT(DISTINCT dh.hariu_id) AS hariu_too,
               COUNT(DISTINCT u.unelgee_id) AS unelgee_too
        FROM geriin_daalgavar gd
        JOIN hicheel_bagsh hb ON hb.hicheel_bagsh_id = gd.hicheel_bagsh_id
        JOIN hicheel h ON h.hicheel_id = hb.hicheel_id
        JOIN angi a ON a.angi_id = hb.angi_id
        LEFT JOIN daalgavriin_hariu dh ON dh.daalgavar_id = gd.daalgavar_id
        LEFT JOIN unelgee u ON u.hariu_id = dh.hariu_id
        WHERE hb.bagsh_id = ?';
$params = [$teacherId];

if ($q !== '') {
    $sql .= ' AND (gd.garchig LIKE ? OR h.hicheel_ner LIKE ? OR h.hicheel_kod LIKE ? OR a.angi_ner LIKE ?)';
    $like = "%{$q}%";
    array_push($params, $like, $like, $like, $like);
}
if ($filterAngi > 0) { $sql .= ' AND a.angi_id = ?'; $params[] = $filterAngi; }

$sql .= ' GROUP BY gd.daalgavar_id ORDER BY gd.duusah_ognoo DESC, gd.daalgavar_id DESC';

$rows = all_rows($sql, $params);
$myClasses = all_rows('SELECT DISTINCT a.angi_id, a.angi_ner FROM hicheel_bagsh hb JOIN angi a ON a.angi_id=hb.angi_id WHERE hb.bagsh_id=? ORDER BY a.angi_ner', [$teacherId]);
$hasFilter = $q !== '' || $filterAngi > 0 || $filterTuluv !== '';

include __DIR__ . '/header.php';
?>
<div class="table-shell">
  <div class="p-5 border-b space-y-4">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
      <div>
        <h3 class="font-headline text-xl font-extrabold text-primary">Гэрийн даалгаврын жагсаалт</h3>
      </div>
      <div class="flex items-center gap-3">
        <?php if ($hasFilter): ?><a href="teacher_homeworks.php" class="text-sm text-red-500 hover:underline">✕ Цэвэрлэх</a><?php endif; ?>
        <a class="inline-flex items-center justify-center rounded-2xl bg-primary text-white px-4 py-2.5 font-medium" href="homework_create.php">Даалгавар нэмэх</a>
      </div>
    </div>
    <form method="get" class="grid grid-cols-2 md:grid-cols-4 gap-2">
      <div class="col-span-2">
        <input name="q" value="<?= e($q) ?>" class="w-full rounded-2xl border-slate-300 px-4 py-2.5 text-sm bg-white" placeholder="Даалгавар, хичээл эсвэл ангиар хайх…">
      </div>
      <select name="filter_angi" class="rounded-2xl border-slate-300 px-3 py-2.5 text-sm bg-white">
        <option value="">Бүх анги</option>
        <?php foreach ($myClasses as $c): ?><option value="<?= (int)$c['angi_id'] ?>" <?= $filterAngi===(int)$c['angi_id'] ? 'selected' : '' ?>><?= e($c['angi_ner']) ?></option><?php endforeach; ?>
      </select>
      <button class="bg-slate-900 text-white rounded-2xl px-4 py-2.5 font-medium text-sm">Шүүх</button>
    </form>
  </div>
  <div class="overflow-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50">
        <tr>
          <th class="text-left p-4">Гарчиг</th>
          <th class="text-left p-4">Хичээл</th>
          <th class="text-left p-4">Анги</th>
          <th class="text-left p-4">Дуусах огноо</th>
          <th class="text-left p-4">Төлөв</th>
          <th class="text-left p-4">Хариу</th>
          <th class="text-left p-4">Оноо</th>
          <th class="text-left p-4">Үйлдэл</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $row): ?>
        <?php
          $overdue = is_homework_overdue($row['duusah_ognoo']);
          $statusLabel = $overdue ? 'Хугацаа дууссан' : ($row['tuluv'] ?: 'Идэвхтэй');
          $statusClass = $overdue ? 'status-pill status-danger' : 'status-pill status-active';
        ?>
        <tr class="border-t align-top hover:bg-slate-50/60">
          <td class="p-4">
            <div class="font-semibold text-slate-900"><?= e($row['garchig']) ?></div>
            <?php if (!empty($row['togloomchilson']) && is_primary_grade($row['angi_ner'] ?? '')): ?>
              <div class="mt-2 inline-flex items-center gap-1 rounded-full bg-orange-50 text-orange-700 border border-orange-200 px-2.5 py-1 text-xs font-extrabold">
                <span class="material-symbols-outlined text-sm">extension</span>
                <?= e(gamified_level_label($row['togloom_turul'] ?? null)) ?> · <?= (int)($row['od_shagnal'] ?? 3) ?> од · <?= (int)($row['turshlaga_onoo'] ?? 10) ?> XP
              </div>
            <?php endif; ?>
            <div class="text-xs text-slate-500 mt-1 line-clamp-2"><?= e((string)$row['tailbar']) ?></div>
          </td>
          <td class="p-4"><?= e($row['hicheel_ner']) ?></td>
          <td class="p-4"><span class="inline-flex bg-indigo-50 text-indigo-700 text-xs font-semibold px-2 py-1 rounded-lg"><?= e($row['angi_ner']) ?></span></td>
          <td class="p-4 min-w-[190px]"><div><?= e(format_dt($row['duusah_ognoo'])) ?></div><div class="text-xs <?= $overdue ? 'text-red-600' : 'text-slate-500' ?> mt-1 leading-5"><?= e(homework_time_text($row['duusah_ognoo'])) ?></div></td>
          <td class="p-4 whitespace-nowrap">
            <?php if ($overdue): ?>
              <span style="display:inline-flex;align-items:center;white-space:nowrap;padding:4px 12px;border-radius:999px;background:#fee2e2;color:#dc2626;font-size:12px;font-weight:700;line-height:1.4;">
                <?= e($statusLabel) ?>
              </span>
            <?php else: ?>
              <span style="display:inline-flex;align-items:center;white-space:nowrap;padding:4px 12px;border-radius:999px;background:#dcfce7;color:#16a34a;font-size:12px;font-weight:700;line-height:1.4;">
                <?= e($statusLabel) ?>
              </span>
            <?php endif; ?>
          </td>
          <td class="p-4 whitespace-nowrap"><span class="font-semibold"><?= (int)$row['hariu_too'] ?></span><span class="text-xs text-slate-500"> илгээсэн</span></td>
          <td class="p-4 whitespace-nowrap"><?= e((string)$row['hamgiin_ih_onoo']) ?></td>
          <td class="p-4">
            <div class="flex flex-wrap gap-2">
              <a class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium" href="homework_edit.php?id=<?= (int)$row['daalgavar_id'] ?>">Засах</a>

              <a class="rounded-lg bg-indigo-700 text-white px-3 py-1.5 text-xs font-medium" href="submission_review.php?daalgavar_id=<?= (int)$row['daalgavar_id'] ?>">Хариу шалгах</a>
              <form method="post" onsubmit="return confirm('Энэ гэрийн даалгаврыг устгах уу? Устгавал холбогдох хариу, үнэлгээ мөн устах боломжтой.');" class="inline">
                <input type="hidden" name="action" value="delete_homework">
                <input type="hidden" name="daalgavar_id" value="<?= (int)$row['daalgavar_id'] ?>">
                <button type="submit" class="rounded-lg border border-red-200 bg-red-50 text-red-700 px-3 py-1.5 text-xs font-medium">Устгах</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="9" class="empty-state"><?= $hasFilter ? 'Шүүлтэд тохирох даалгавар олдсонгүй.' : 'Та одоогоор даалгавар өгөөгүй байна.' ?></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/footer.php'; ?>
