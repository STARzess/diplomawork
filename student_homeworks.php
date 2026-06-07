<?php
require_once __DIR__ . '/functions.php';
require_roles(['Сурагч']);
close_expired_homeworks();
$pageTitle = 'Гэрийн даалгаврын жагсаалт';
$studentId = current_user()['id'];
$rows = all_rows('SELECT gd.daalgavar_id, gd.garchig, gd.tailbar, gd.daalgavar_turul, gd.duusah_ognoo, gd.hamgiin_ih_onoo, gd.tuluv AS daalgavar_tuluv, h.hicheel_ner, dh.hariu_id, dh.tuluv AS hariu_tuluv, dh.ilgeesen_ognoo, u.avsan_onoo FROM geriin_daalgavar gd JOIN hicheel_bagsh hb ON hb.hicheel_bagsh_id=gd.hicheel_bagsh_id JOIN suragch s ON s.angi_id=hb.angi_id JOIN hicheel h ON h.hicheel_id=hb.hicheel_id LEFT JOIN daalgavriin_hariu dh ON dh.daalgavar_id=gd.daalgavar_id AND dh.suragch_id=? LEFT JOIN unelgee u ON u.hariu_id=dh.hariu_id WHERE s.suragch_id=? ORDER BY gd.duusah_ognoo ASC', [$studentId, $studentId]);
include __DIR__ . '/header.php';
?>
<div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
  <div class="p-5 border-b"><h3 class="font-semibold text-lg">Миний даалгаврууд</h3></div>
  <table class="w-full text-sm">
    <thead class="bg-slate-50"><tr><th class="text-left p-4">Хичээл</th><th class="text-left p-4">Гарчиг</th><th class="text-left p-4">Төрөл</th><th class="text-left p-4">Дуусах огноо</th><th class="text-left p-4">Оноо</th><th class="text-left p-4">Төлөв</th><th class="text-left p-4">Үйлдэл</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
      <?php
        $overdue = is_homework_overdue($row['duusah_ognoo']);
        if ($row['hariu_id']) {
            $statusText = $row['hariu_tuluv'] ?: 'Илгээгдсэн';
            $statusType = $row['avsan_onoo'] !== null ? 'success' : 'warning';
        } elseif ($overdue) {
            $statusText = 'Хоцорсон';
            $statusType = 'danger';
        } else {
            $statusText = 'Илгээгээгүй';
            $statusType = 'default';
        }
      ?>
      <tr class="border-t align-top">
        <td class="p-4"><?= e($row['hicheel_ner']) ?></td>
        <td class="p-4"><div class="font-medium"><?= e($row['garchig']) ?></div><div class="text-slate-500 text-xs mt-1 line-clamp-2"><?= e((string)$row['tailbar']) ?></div></td>
        <td class="p-4"><?= e($row['daalgavar_turul'] ?: 'Энгийн') ?></td>
        <td class="p-4"><div><?= e(format_dt($row['duusah_ognoo'])) ?></div><div class="text-xs <?= $overdue ? 'text-red-600' : 'text-slate-500' ?> mt-1"><?= e(homework_time_text($row['duusah_ognoo'])) ?></div></td>
        <td class="p-4"><?= e(format_score_with_letter($row['avsan_onoo'] ?? null)) ?></td>
        <td class="p-4"><span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold <?= e(status_badge_class($statusType)) ?>"><?= e($statusText) ?></span></td>
        <td class="p-4">
          <div class="flex flex-wrap gap-2">
            <a class="inline-flex px-4 py-2 rounded-xl border border-slate-300 bg-white text-slate-700 hover:bg-slate-50" href="homework_view.php?id=<?= e((string)$row['daalgavar_id']) ?>">Харах</a>
            <?php if ($row['hariu_id']): ?>
              <span class="inline-flex px-3 py-2 rounded-xl bg-green-100 text-green-700">Илгээсэн</span>
            <?php elseif ($overdue): ?>
              <span class="inline-flex px-3 py-2 rounded-xl bg-red-100 text-red-700">Хугацаа дууссан</span>
            <?php else: ?>
              <a class="inline-flex px-4 py-2 rounded-xl bg-indigo-700 text-white" href="submission_create.php?daalgavar_id=<?= e((string)$row['daalgavar_id']) ?>"><?= ($row['daalgavar_turul'] ?: 'Энгийн') === 'Тест' ? 'Тест бөглөх' : 'Хариу илгээх' ?></a>
            <?php endif; ?>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="7" class="empty-state">Даалгавар олдсонгүй.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/footer.php'; ?>
