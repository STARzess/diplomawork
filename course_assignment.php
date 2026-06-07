<?php
require_once __DIR__ . '/functions.php';
require_roles(['Админ','Сургалтын алба']);

$editId = (int)($_GET['edit'] ?? 0);
$editClassId = (int)($_GET['edit_class'] ?? 0);
$editing = null;
$editingClassTeacher = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save_course_assignment';

    if ($action === 'delete_course_assignment') {
        $id = (int)($_POST['hicheel_bagsh_id'] ?? 0);
        if ($id) {
            try {
                db()->prepare('DELETE FROM hicheel_bagsh WHERE hicheel_bagsh_id=?')->execute([$id]);
                flash('Хичээл, багш, ангийн хуваарилалтыг устгалаа.');
            } catch (Throwable $e) {
                flash('Энэ хуваарилалттай холбоотой даалгавар эсвэл сурагчийн бүртгэл байгаа тул устгах боломжгүй.', 'error');
            }
        }
        header('Location: course_assignment.php'); exit;
    }

    if ($action === 'save_class_teacher') {
        $angiId = (int)($_POST['class_angi_id'] ?? 0);
        $teacherId = (int)($_POST['class_bagsh_id'] ?? 0);
        if ($angiId) {
            db()->prepare('UPDATE angi SET angi_udirdagch_id=? WHERE angi_id=?')->execute([$teacherId ?: null, $angiId]);
            flash('Анги удирдах багшийн хуваарилалтыг амжилттай хадгаллаа.');
        } else {
            flash('Анги сонгоно уу.', 'error');
        }
        header('Location: course_assignment.php'); exit;
    }

    if ($action === 'delete_class_teacher') {
        $angiId = (int)($_POST['class_angi_id'] ?? 0);
        if ($angiId) {
            db()->prepare('UPDATE angi SET angi_udirdagch_id=NULL WHERE angi_id=?')->execute([$angiId]);
            flash('Анги удирдах багшийн хуваарилалтыг устгалаа.');
        }
        header('Location: course_assignment.php'); exit;
    }

    $id = (int)($_POST['hicheel_bagsh_id'] ?? 0);
    $hicheelId = (int)($_POST['hicheel_id'] ?? 0);
    $bagshId = (int)($_POST['bagsh_id'] ?? 0);
    $angiId = (int)($_POST['angi_id'] ?? 0);
    $jil = trim($_POST['surgaltiin_jil'] ?? '');

    if ($hicheelId && $bagshId && $angiId && $jil !== '') {
        $exists = one_row('SELECT hicheel_bagsh_id FROM hicheel_bagsh WHERE hicheel_id=? AND bagsh_id=? AND angi_id=? AND surgaltiin_jil=? AND hicheel_bagsh_id <> ? LIMIT 1', [$hicheelId,$bagshId,$angiId,$jil,$id]);
        if ($exists) {
            flash('Ижил хичээл, багш, ангийн хуваарилалт аль хэдийн байна.', 'error');
        } else {
            if ($id) {
                db()->prepare('UPDATE hicheel_bagsh SET hicheel_id=?, bagsh_id=?, angi_id=?, surgaltiin_jil=? WHERE hicheel_bagsh_id=?')->execute([$hicheelId,$bagshId,$angiId,$jil,$id]);
                flash('Хуваарилалт амжилттай шинэчлэгдлээ.');
            } else {
                db()->prepare('INSERT INTO hicheel_bagsh (hicheel_id, bagsh_id, angi_id, surgaltiin_jil) VALUES (?, ?, ?, ?)')->execute([$hicheelId,$bagshId,$angiId,$jil]);
                flash('Хичээлийн хуваарилалт амжилттай нэмэгдлээ.');
            }
        }
    } else {
        flash('Бүх талбарыг бөглөнө үү.', 'error');
    }
    header('Location: course_assignment.php'); exit;
}

if ($editId) {
    $editing = one_row('SELECT * FROM hicheel_bagsh WHERE hicheel_bagsh_id=?', [$editId]);
}
if ($editClassId) {
    $editingClassTeacher = one_row('SELECT angi_id, angi_udirdagch_id FROM angi WHERE angi_id=?', [$editClassId]);
}

$pageTitle = 'Багш, ангид хуваарилах';
$courses = all_rows('SELECT hicheel_id, hicheel_ner, hicheel_kod FROM hicheel ORDER BY hicheel_ner');
$teachers = all_rows("SELECT a.ajiltan_id, a.ovog, a.ner FROM ajiltan a JOIN erkh e ON e.erkh_id=a.erkh_id WHERE e.erkh_ner='Багш' AND a.tuluv='Идэвхтэй' ORDER BY a.ovog, a.ner");
$classes = all_rows('SELECT angi_id, angi_ner, surgaltiin_jil, angi_udirdagch_id FROM angi ORDER BY FIELD(tuvshin, "бага", "дунд", "ахлах"), angi_ner');
$rows = all_rows('SELECT hb.hicheel_bagsh_id, h.hicheel_ner, h.hicheel_kod, a.angi_ner, hb.surgaltiin_jil, aj.ovog, aj.ner FROM hicheel_bagsh hb JOIN hicheel h ON h.hicheel_id=hb.hicheel_id JOIN angi a ON a.angi_id=hb.angi_id JOIN ajiltan aj ON aj.ajiltan_id=hb.bagsh_id ORDER BY hb.hicheel_bagsh_id DESC');
$classTeacherRows = all_rows("SELECT a.angi_id, a.angi_ner, a.surgaltiin_jil, COALESCE(CONCAT(j.ovog, ' ', j.ner), 'Одоогоор сонгоогүй') AS bagsh_ner FROM angi a LEFT JOIN ajiltan j ON j.ajiltan_id=a.angi_udirdagch_id ORDER BY FIELD(a.tuvshin, 'бага', 'дунд', 'ахлах'), a.angi_ner");
include __DIR__ . '/header.php';
?>
<div class="grid xl:grid-cols-2 gap-6 mb-6">
  <section class="form-card p-6">
    <div class="flex items-center justify-between gap-3 mb-4">
      <h3 class="font-semibold text-lg"><?= $editing ? 'Хуваарилалт засах' : 'Хичээлд багш, анги хуваарилах' ?></h3>
      <?php if ($editing): ?><a href="course_assignment.php" class="text-sm text-primary font-medium">Цэвэрлэх</a><?php endif; ?>
    </div>
    <form method="post" class="space-y-4">
      <input type="hidden" name="action" value="save_course_assignment">
      <input type="hidden" name="hicheel_bagsh_id" value="<?= e((string)($editing['hicheel_bagsh_id'] ?? 0)) ?>">
      <select name="hicheel_id" class="w-full rounded-xl border-slate-300 px-4 py-3" required>
        <option value="">Хичээл сонгох</option>
        <?php foreach ($courses as $course): ?><option value="<?= e((string)$course['hicheel_id']) ?>" <?= (int)($editing['hicheel_id'] ?? 0) === (int)$course['hicheel_id'] ? 'selected' : '' ?>><?= e(($course['hicheel_kod'] ? $course['hicheel_kod'].' - ' : '').$course['hicheel_ner']) ?></option><?php endforeach; ?>
      </select>
      <select name="bagsh_id" class="w-full rounded-xl border-slate-300 px-4 py-3" required>
        <option value="">Багш сонгох</option>
        <?php foreach ($teachers as $teacher): ?><option value="<?= e((string)$teacher['ajiltan_id']) ?>" <?= (int)($editing['bagsh_id'] ?? 0) === (int)$teacher['ajiltan_id'] ? 'selected' : '' ?>><?= e($teacher['ovog'].' '.$teacher['ner']) ?></option><?php endforeach; ?>
      </select>
      <select name="angi_id" class="w-full rounded-xl border-slate-300 px-4 py-3" required>
        <option value="">Анги сонгох</option>
        <?php foreach ($classes as $class): ?><option value="<?= e((string)$class['angi_id']) ?>" <?= (int)($editing['angi_id'] ?? 0) === (int)$class['angi_id'] ? 'selected' : '' ?>><?= e($class['angi_ner'].' / '.$class['surgaltiin_jil']) ?></option><?php endforeach; ?>
      </select>
      <input name="surgaltiin_jil" value="<?= e((string)($editing['surgaltiin_jil'] ?? '')) ?>" class="w-full rounded-xl border-slate-300 px-4 py-3" placeholder="2025-2026" required>
      <button class="w-full bg-primary text-white rounded-xl py-3 font-medium"><?= $editing ? 'Шинэчлэх' : 'Хуваарилалт хадгалах' ?></button>
    </form>
  </section>
  <section class="form-card p-6">
    <div class="flex items-center justify-between gap-3 mb-4">
      <h3 class="font-semibold text-lg">Анги удирдах багш хуваарилах</h3>
      <?php if ($editingClassTeacher): ?><a href="course_assignment.php" class="text-sm text-primary font-medium">Цэвэрлэх</a><?php endif; ?>
    </div>
    <form method="post" class="space-y-4">
      <input type="hidden" name="action" value="save_class_teacher">
      <select name="class_angi_id" class="w-full rounded-xl border-slate-300 px-4 py-3" required>
        <option value="">Анги сонгох</option>
        <?php foreach ($classes as $class): ?><option value="<?= e((string)$class['angi_id']) ?>" <?= (int)($editingClassTeacher['angi_id'] ?? 0) === (int)$class['angi_id'] ? 'selected' : '' ?>><?= e($class['angi_ner'].' / '.$class['surgaltiin_jil']) ?></option><?php endforeach; ?>
      </select>
      <select name="class_bagsh_id" class="w-full rounded-xl border-slate-300 px-4 py-3">
        <option value="">Багш сонгох</option>
        <?php foreach ($teachers as $teacher): ?><option value="<?= e((string)$teacher['ajiltan_id']) ?>" <?= (int)($editingClassTeacher['angi_udirdagch_id'] ?? 0) === (int)$teacher['ajiltan_id'] ? 'selected' : '' ?>><?= e($teacher['ovog'].' '.$teacher['ner']) ?></option><?php endforeach; ?>
      </select>
      <button class="w-full bg-slate-900 text-white rounded-xl py-3 font-medium"><?= $editingClassTeacher ? 'Шинэчлэх' : 'Хадгалах' ?></button>
    </form>
  </section>
</div>

<div class="grid xl:grid-cols-2 gap-6">
  <section class="table-shell">
    <div class="p-5 border-b"><h3 class="font-semibold text-lg">Хичээлд багш, ангийн хуваарилалт</h3></div>
    <table class="w-full text-sm">
      <thead class="bg-slate-50"><tr><th class="text-left p-4">Хичээл</th><th class="text-left p-4">Багш</th><th class="text-left p-4">Анги</th><th class="text-left p-4">Жил</th><th class="text-left p-4">Үйлдэл</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $row): ?>
      <tr class="border-t align-top">
        <td class="p-4"><a class="text-primary font-medium" href="course_detail.php?id=<?= e((string)$row['hicheel_bagsh_id']) ?>&mode=assignment"><?= e(($row['hicheel_kod'] ? $row['hicheel_kod'].' - ' : '').$row['hicheel_ner']) ?></a></td>
        <td class="p-4"><?= e($row['ovog'].' '.$row['ner']) ?></td>
        <td class="p-4"><?= e($row['angi_ner']) ?></td>
        <td class="p-4"><?= e($row['surgaltiin_jil']) ?></td>
        <td class="p-4">
          <div class="flex flex-wrap gap-2">
            <a href="course_assignment.php?edit=<?= e((string)$row['hicheel_bagsh_id']) ?>" class="rounded-xl bg-white border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">Засах</a>
            <form method="post" onsubmit="return confirm('Энэ хуваарилалтыг устгах уу?');">
              <input type="hidden" name="action" value="delete_course_assignment">
              <input type="hidden" name="hicheel_bagsh_id" value="<?= e((string)$row['hicheel_bagsh_id']) ?>">
              <button class="rounded-xl bg-red-600 text-white px-4 py-2 text-sm font-medium">Устгах</button>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="5" class="empty-state">Хуваарилалт бүртгэгдээгүй байна.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </section>

  <section class="table-shell">
    <div class="p-5 border-b"><h3 class="font-semibold text-lg">Анги удирдах багшийн хуваарилалт</h3></div>
    <table class="w-full text-sm">
      <thead class="bg-slate-50"><tr><th class="text-left p-4">Анги</th><th class="text-left p-4">Сургалтын жил</th><th class="text-left p-4">Багш</th><th class="text-left p-4">Үйлдэл</th></tr></thead>
      <tbody>
      <?php foreach ($classTeacherRows as $row): ?>
      <tr class="border-t align-top">
        <td class="p-4 font-medium"><?= e($row['angi_ner']) ?></td>
        <td class="p-4"><?= e($row['surgaltiin_jil']) ?></td>
        <td class="p-4"><?= e($row['bagsh_ner']) ?></td>
        <td class="p-4">
          <div class="flex flex-wrap gap-2">
            <a href="course_assignment.php?edit_class=<?= e((string)$row['angi_id']) ?>" class="rounded-xl bg-white border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">Засах</a>
            <form method="post" onsubmit="return confirm('Анги удирдах багшийн хуваарилалтыг устгах уу?');">
              <input type="hidden" name="action" value="delete_class_teacher">
              <input type="hidden" name="class_angi_id" value="<?= e((string)$row['angi_id']) ?>">
              <button class="rounded-xl bg-red-600 text-white px-4 py-2 text-sm font-medium">Устгах</button>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$classTeacherRows): ?><tr><td colspan="4" class="empty-state">Анги бүртгэгдээгүй байна.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </section>
</div>
<?php include __DIR__ . '/footer.php'; ?>
