<?php
require_once __DIR__ . '/functions.php';
require_roles(['Админ','Сургалтын алба']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $angiNer = trim($_POST['angi_ner'] ?? '');
    $tuvshin = trim($_POST['tuvshin'] ?? '');
    $jil = trim($_POST['surgaltiin_jil'] ?? '');
    $angiUdirdagchId = (int)($_POST['angi_udirdagch_id'] ?? 0);
    if ($angiNer !== '' && $tuvshin !== '' && $jil !== '') {
        db()->prepare('INSERT INTO angi (angi_ner, tuvshin, surgaltiin_jil, angi_udirdagch_id) VALUES (?, ?, ?, ?)')->execute([
            $angiNer,
            $tuvshin,
            $jil,
            $angiUdirdagchId ?: null,
        ]);
        flash('Анги амжилттай бүртгэгдлээ.');
        header('Location: class_list.php');
        exit;
    }
    flash('Анги, түвшин, сургалтын жилийг бөглөнө үү.', 'error');
}

$pageTitle = 'Ангиуд';
$rows = all_rows("SELECT a.angi_id, a.angi_ner, a.tuvshin, a.surgaltiin_jil,
                         CONCAT(COALESCE(j.ovog, ''), ' ', COALESCE(j.ner, '')) AS angi_udirdagch,
                         COUNT(s.suragch_id) AS suragch_too
                  FROM angi a
                  LEFT JOIN suragch s ON s.angi_id = a.angi_id
                  LEFT JOIN ajiltan j ON j.ajiltan_id = a.angi_udirdagch_id
                  GROUP BY a.angi_id
                  ORDER BY FIELD(a.tuvshin, 'бага', 'дунд', 'ахлах'), a.angi_ner");
$teachers = all_rows("SELECT a.ajiltan_id, a.ovog, a.ner
                      FROM ajiltan a
                      JOIN erkh e ON e.erkh_id = a.erkh_id
                      WHERE e.erkh_ner = 'Багш' AND a.tuluv = 'Идэвхтэй'
                      ORDER BY a.ovog, a.ner");
include __DIR__ . '/header.php';
?>
<div class="grid lg:grid-cols-3 gap-6">
  <section class="table-shell lg:col-span-2">
    <div class="p-5 border-b"><h3 class="font-semibold text-lg">Ангиудын жагсаалт</h3></div>
    <table class="w-full text-sm">
      <thead class="bg-slate-50"><tr><th class="text-left p-4">Анги</th><th class="text-left p-4">Түвшин</th><th class="text-left p-4">Сургалтын жил</th><th class="text-left p-4">Анги удирдах багш</th><th class="text-left p-4">Сурагч</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $row): ?>
        <tr class="border-t">
          <td class="p-4 font-medium"><?= e($row['angi_ner']) ?></td>
          <td class="p-4"><span class="status-pill status-info"><?= e((string)$row['tuvshin']) ?></span></td>
          <td class="p-4"><?= e($row['surgaltiin_jil']) ?></td>
          <td class="p-4"><?= e(trim((string)$row['angi_udirdagch']) ?: 'Одоогоор сонгоогүй') ?></td>
          <td class="p-4"><?= e((string)$row['suragch_too']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="5" class="empty-state">Анги бүртгэгдээгүй байна.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </section>
  <section class="form-card p-6">
    <h3 class="font-semibold text-lg mb-4">Анги бүртгэх</h3>
    <form method="post" class="space-y-4">
      <input name="angi_ner" class="w-full rounded-xl border-slate-300 px-4 py-3" placeholder="10А" required>
      <select name="tuvshin" class="w-full rounded-xl border-slate-300 px-4 py-3" required>
        <option value="">Түвшин сонгох</option>
        <option value="бага">Бага</option>
        <option value="дунд">Дунд</option>
        <option value="ахлах">Ахлах</option>
      </select>
      <input name="surgaltiin_jil" class="w-full rounded-xl border-slate-300 px-4 py-3" placeholder="2025-2026" required>
      <select name="angi_udirdagch_id" class="w-full rounded-xl border-slate-300 px-4 py-3">
        <option value="">Анги удирдах багш сонгох</option>
        <?php foreach ($teachers as $teacher): ?>
          <option value="<?= e((string)$teacher['ajiltan_id']) ?>"><?= e(trim($teacher['ovog'].' '.$teacher['ner'])) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="w-full bg-primary text-white rounded-xl py-3 font-medium">Анги бүртгэх</button>
    </form>
  </section>
</div>
<?php include __DIR__ . '/footer.php'; ?>
