<?php
require_once __DIR__ . '/functions.php';
require_roles(['Сурагч']);
close_expired_homeworks();

$pageTitle = 'Хичээлийн дэлгэрэнгүй';
$studentId = (int)current_user()['id'];
$hbId = (int)($_GET['id'] ?? 0);

$course = one_row('SELECT hb.hicheel_bagsh_id, hb.surgaltiin_jil, h.hicheel_id, h.hicheel_kod, h.hicheel_ner, h.tailbar, a.angi_ner,
                          CONCAT(COALESCE(aj.ovog,""), CASE WHEN aj.ovog IS NOT NULL AND aj.ner IS NOT NULL THEN " " ELSE "" END, COALESCE(aj.ner,"")) AS bagsh_ner
                   FROM hicheel_bagsh hb
                   JOIN hicheel h ON h.hicheel_id = hb.hicheel_id
                   JOIN angi a ON a.angi_id = hb.angi_id
                   JOIN suragch st ON st.angi_id = hb.angi_id
                   LEFT JOIN ajiltan aj ON aj.ajiltan_id = hb.bagsh_id
                   WHERE hb.hicheel_bagsh_id = ? AND st.suragch_id = ? AND h.tuluv = "Идэвхтэй"
                   LIMIT 1', [$hbId, $studentId]);
if (!$course) {
    flash('Хичээл олдсонгүй эсвэл харах эрхгүй байна.', 'error');
    header('Location: student_courses.php');
    exit;
}

$homeworks = all_rows('SELECT gd.daalgavar_id, gd.garchig, gd.tailbar, gd.daalgavar_turul, gd.ogson_ognoo, gd.duusah_ognoo, gd.hamgiin_ih_onoo, gd.tuluv AS daalgavar_tuluv,
                             dh.hariu_id, dh.tuluv AS hariu_tuluv, dh.ilgeesen_ognoo, u.avsan_onoo
                      FROM geriin_daalgavar gd
                      LEFT JOIN daalgavriin_hariu dh ON dh.daalgavar_id = gd.daalgavar_id AND dh.suragch_id = ?
                      LEFT JOIN unelgee u ON u.hariu_id = dh.hariu_id
                      WHERE gd.hicheel_bagsh_id = ?
                      ORDER BY gd.duusah_ognoo ASC, gd.daalgavar_id DESC', [$studentId, $hbId]);

include __DIR__ . '/header.php';
?>
<div class="space-y-6">
  <section class="bg-white rounded-2xl shadow-sm border p-6">
    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
      <div>
        <div class="text-sm text-indigo-700 font-semibold mb-2"><?= e(($course['hicheel_kod'] ? $course['hicheel_kod'].' · ' : '').$course['angi_ner']) ?></div>
        <h3 class="font-headline text-2xl font-extrabold text-primary"><?= e($course['hicheel_ner']) ?></h3>
        <p class="text-slate-600 mt-3 whitespace-pre-line"><?= nl2br(e((string)($course['tailbar'] ?: 'Тайлбар байхгүй.'))) ?></p>
      </div>
      <div class="rounded-2xl bg-slate-50 border border-slate-200 p-4 text-sm min-w-[220px]">
        <div><span class="font-semibold">Багш:</span> <?= e($course['bagsh_ner'] ?: '-') ?></div>
        <div class="mt-2"><span class="font-semibold">Анги:</span> <?= e($course['angi_ner']) ?></div>
        <div class="mt-2"><span class="font-semibold">Жил:</span> <?= e($course['surgaltiin_jil']) ?></div>
      </div>
    </div>
  </section>

  <section class="table-shell">
    <div class="p-5 border-b"><h3 class="font-semibold text-lg">Энэ хичээлийн гэрийн даалгаврууд</h3></div>
    <div class="overflow-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50"><tr><th class="text-left p-4">Гарчиг</th><th class="text-left p-4">Төрөл</th><th class="text-left p-4">Дуусах огноо</th><th class="text-left p-4">Оноо</th><th class="text-left p-4">Төлөв</th><th class="text-left p-4">Үйлдэл</th></tr></thead>
        <tbody>
        <?php foreach ($homeworks as $row): ?>
          <?php
            $overdue = is_homework_overdue($row['duusah_ognoo']);
            if ($row['hariu_id']) { $statusText = $row['avsan_onoo'] !== null ? 'Үнэлгээ тавигдсан' : 'Илгээсэн'; $statusType = $row['avsan_onoo'] !== null ? 'success' : 'warning'; }
            elseif ($overdue) { $statusText = 'Хоцорсон'; $statusType = 'danger'; }
            else { $statusText = 'Илгээгээгүй'; $statusType = 'default'; }
          ?>
          <tr class="border-t align-top hover:bg-slate-50/60">
            <td class="p-4"><div class="font-semibold text-slate-900"><?= e($row['garchig']) ?></div><div class="text-xs text-slate-500 mt-1 line-clamp-2"><?= e((string)$row['tailbar']) ?></div></td>
            <td class="p-4"><?= e($row['daalgavar_turul'] ?: 'Энгийн') ?></td>
            <td class="p-4"><div><?= e(format_dt($row['duusah_ognoo'])) ?></div><div class="text-xs <?= $overdue ? 'text-red-600' : 'text-slate-500' ?> mt-1"><?= e(homework_time_text($row['duusah_ognoo'])) ?></div></td>
            <td class="p-4"><?= e(format_score_with_letter($row['avsan_onoo'] ?? null)) ?></td>
            <td class="p-4"><span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold <?= e(status_badge_class($statusType)) ?>"><?= e($statusText) ?></span></td>
            <td class="p-4"><div class="flex flex-wrap gap-2">
              <a class="inline-flex px-4 py-2 rounded-xl border border-slate-300 bg-white text-slate-700 hover:bg-slate-50" href="homework_view.php?id=<?= (int)$row['daalgavar_id'] ?>">Харах</a>
              <?php if (!$row['hariu_id'] && !$overdue): ?><a class="inline-flex px-4 py-2 rounded-xl bg-indigo-700 text-white" href="submission_create.php?daalgavar_id=<?= (int)$row['daalgavar_id'] ?>"><?= ($row['daalgavar_turul'] ?: 'Энгийн') === 'Тест' ? 'Тест бөглөх' : 'Хариу илгээх' ?></a><?php endif; ?>
            </div></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$homeworks): ?><tr><td colspan="6" class="empty-state">Энэ хичээлд гэрийн даалгавар бүртгэгдээгүй байна.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>
<?php include __DIR__ . '/footer.php'; ?>
