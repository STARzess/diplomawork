<?php
require_once __DIR__ . '/functions.php';
ensure_gamified_homework_schema();
require_roles(['Сурагч']);
close_expired_homeworks();
$pageTitle = 'Гэрийн даалгаврын жагсаалт';
$studentId = current_user()['id'];
$rows = all_rows('SELECT gd.daalgavar_id, gd.garchig, gd.tailbar, \'Энгийн\' AS daalgavar_turul, gd.togloomchilson, gd.togloom_turul, gd.togloom_zorilgo, gd.od_shagnal, gd.turshlaga_onoo, gd.duusah_ognoo, gd.hamgiin_ih_onoo, gd.tuluv AS daalgavar_tuluv, h.hicheel_ner, a.angi_ner, dh.hariu_id, dh.tuluv AS hariu_tuluv, dh.ilgeesen_ognoo, u.avsan_onoo FROM geriin_daalgavar gd JOIN hicheel_bagsh hb ON hb.hicheel_bagsh_id=gd.hicheel_bagsh_id JOIN suragch s ON s.angi_id=hb.angi_id JOIN hicheel h ON h.hicheel_id=hb.hicheel_id JOIN angi a ON a.angi_id=hb.angi_id LEFT JOIN daalgavriin_hariu dh ON dh.daalgavar_id=gd.daalgavar_id AND dh.suragch_id=? LEFT JOIN unelgee u ON u.hariu_id=dh.hariu_id WHERE s.suragch_id=? ORDER BY gd.duusah_ognoo ASC', [$studentId, $studentId]);
include __DIR__ . '/header.php';
?>
<style>
  .game-badge { display:inline-flex; align-items:center; gap:6px; border-radius:999px; padding:5px 10px; background:#fff7ed; color:#c2410c; font-size:12px; font-weight:800; margin-bottom:8px; }
  .game-mini { margin-top:10px; display:grid; gap:8px; max-width:360px; }
  .game-mini-row { display:flex; flex-wrap:wrap; gap:8px; align-items:center; color:#475569; font-size:12px; }
  .game-chip { display:inline-flex; align-items:center; gap:4px; border-radius:999px; padding:4px 9px; background:#eef2ff; color:#1a237e; font-weight:800; }
  .game-progress { height:7px; border-radius:999px; background:#e2e8f0; overflow:hidden; }
  .game-progress span { display:block; height:100%; border-radius:999px; background:#22c55e; }
</style>
<div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
  <div class="p-5 border-b"><h3 class="font-semibold text-lg">Миний даалгаврууд</h3></div>
  <table class="w-full text-sm">
    <thead class="bg-slate-50"><tr><th class="text-left p-4">Хичээл</th><th class="text-left p-4">Гарчиг</th><th class="text-left p-4">Дуусах огноо</th><th class="text-left p-4">Оноо</th><th class="text-left p-4">Төлөв</th><th class="text-left p-4">Үйлдэл</th></tr></thead>
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
        <td class="p-4">
          <?php $isGame = !empty($row['togloomchilson']) && is_primary_grade($row['angi_ner'] ?? ''); ?>
          <?php if ($isGame): ?>
            <?php $progress = gamified_progress_percent($row['duusah_ognoo'], !empty($row['hariu_id'])); ?>
            <div class="game-badge"><span class="material-symbols-outlined text-sm">extension</span><?= e(gamified_level_label($row['togloom_turul'] ?? null)) ?></div>
          <?php endif; ?>
          <div class="font-medium"><?= e($row['garchig']) ?></div>
          <div class="text-slate-500 text-xs mt-1 line-clamp-2"><?= e((string)($isGame && !empty($row['togloom_zorilgo']) ? $row['togloom_zorilgo'] : $row['tailbar'])) ?></div>
          <?php if ($isGame): ?>
            <div class="game-mini">
              <div class="game-mini-row">
                <span class="game-chip"><span class="material-symbols-outlined text-sm">star</span><?= (int)($row['od_shagnal'] ?? 3) ?> од</span>
                <span class="game-chip"><span class="material-symbols-outlined text-sm">bolt</span><?= (int)($row['turshlaga_onoo'] ?? 10) ?> XP</span>
                <span><?= $row['hariu_id'] ? 'Дууссан' : 'Аялал үргэлжилж байна' ?></span>
              </div>
              <div class="game-progress"><span style="width:<?= (int)$progress ?>%"></span></div>
            </div>
          <?php endif; ?>
        </td>
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
              <a class="inline-flex px-4 py-2 rounded-xl bg-indigo-700 text-white" href="submission_create.php?daalgavar_id=<?= e((string)$row['daalgavar_id']) ?>">Хариу илгээх</a>
            <?php endif; ?>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="6" class="empty-state">Даалгавар олдсонгүй.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/footer.php'; ?>
