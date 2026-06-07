<?php
require_once __DIR__ . '/functions.php';
require_roles(['Админ','Сургалтын алба','Багш']);
$role = current_role();

$canCreate         = in_array($role, ['Админ','Сургалтын алба'], true);
$canManageStudents = $role === 'Сургалтын алба';
$editingId         = (int)($_GET['edit'] ?? 0);

/* ── POST: бүртгэх / засах / устгах ─────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canCreate) {
    $action = $_POST['action'] ?? 'create';

    if (in_array($action, ['update','delete'], true) && !$canManageStudents) {
        flash('Сурагчийн мэдээллийг засах, устгах эрх зөвхөн Сургалтын албанд байна.', 'error');
        header('Location: student_list.php'); exit;
    }

    if ($action === 'delete') {
        $deleteId = (int)($_POST['suragch_id'] ?? 0);
        if ($deleteId > 0) {
            try {
                db()->prepare('DELETE FROM suragch WHERE suragch_id = ?')->execute([$deleteId]);
                flash('Сурагчийн бүртгэл амжилттай устгагдлаа.');
            } catch (Throwable $e) {
                flash('Сурагчийг устгах боломжгүй байна. Холбоотой өгөгдөл байгаа эсэхийг шалгана уу.', 'error');
            }
        }
        header('Location: student_list.php'); exit;
    }

    $suragchId = (int)($_POST['suragch_id']     ?? 0);
    $ovog      = trim($_POST['ovog']            ?? '');
    $ner       = trim($_POST['ner']             ?? '');
    $email     = trim($_POST['email']           ?? '');
    $password  = trim($_POST['nuuts_ug']        ?? '');
    $register  = trim($_POST['register_dugaar'] ?? '');
    $angiId    = (int)($_POST['angi_id']        ?? 0);

    if (!$ovog || !$ner || !$email || !$angiId) {
        flash('Овог, нэр, имэйл, анги зэрэг шаардлагатай талбаруудыг бөглөнө үү.', 'error');
        $redirect = 'student_list.php'.($action==='update'&&$suragchId ? '?edit='.$suragchId : '');
        header('Location: '.$redirect); exit;
    }

    try {
        if ($action === 'update' && $suragchId > 0) {
            if ($password !== '') {
                db()->prepare('UPDATE suragch SET ovog=?,ner=?,email=?,nuuts_ug=?,register_dugaar=?,angi_id=? WHERE suragch_id=?')
                    ->execute([$ovog,$ner,$email,password_hash($password,PASSWORD_DEFAULT),$register?:null,$angiId,$suragchId]);
            } else {
                db()->prepare('UPDATE suragch SET ovog=?,ner=?,email=?,register_dugaar=?,angi_id=? WHERE suragch_id=?')
                    ->execute([$ovog,$ner,$email,$register?:null,$angiId,$suragchId]);
            }
            flash('Сурагчийн мэдээлэл амжилттай шинэчлэгдлээ.');
            header('Location: student_list.php'); exit;
        }
        if ($password === '') {
            flash('Шинэ сурагч бүртгэхдээ нууц үг оруулна уу.', 'error');
            header('Location: student_list.php'); exit;
        }
        db()->prepare('INSERT INTO suragch (ovog,ner,email,nuuts_ug,register_dugaar,angi_id,tuluv) VALUES (?,?,?,?,?,?,\'Идэвхтэй\')')
            ->execute([$ovog,$ner,$email,password_hash($password,PASSWORD_DEFAULT),$register?:null,$angiId]);
        flash('Сурагч амжилттай бүртгэгдлээ.');
        header('Location: student_list.php'); exit;
    } catch (Throwable $e) {
        flash('Мэдээлэл хадгалах үед алдаа гарлаа. Имэйл эсвэл регистр давхардсан эсэхийг шалгана уу.', 'error');
        $redirect = 'student_list.php'.($action==='update'&&$suragchId ? '?edit='.$suragchId : '');
        header('Location: '.$redirect); exit;
    }
}

/* ── GET filter параметрүүд ──────────────────────────────────── */
$q           = trim($_GET['q']           ?? '');
$filterAngi  = (int)($_GET['filter_angi']  ?? 0);
$filterTuluv = trim($_GET['filter_tuluv']  ?? '');

$pageTitle = 'Сурагчдын жагсаалт';

/* ── SQL ─────────────────────────────────────────────────────── */
$sql    = 'SELECT s.suragch_id, s.ovog, s.ner, s.email, s.register_dugaar, s.tuluv,
                  a.angi_ner, a.angi_id
           FROM suragch s
           LEFT JOIN angi a ON a.angi_id = s.angi_id';
$params = [];

if ($role === 'Багш') {
    $sql .= ' INNER JOIN hicheel_bagsh hb
                ON hb.angi_id = s.angi_id
               AND hb.bagsh_id = '.(int)current_user()['id'];
}

$wheres = [];
if ($q !== '') {
    $wheres[] = '(s.ovog LIKE ? OR s.ner LIKE ? OR s.email LIKE ? OR s.register_dugaar LIKE ?)';
    $like = "%{$q}%";
    array_push($params, $like, $like, $like, $like);
}
if ($filterAngi > 0) {
    $wheres[] = 's.angi_id = ?';
    $params[] = $filterAngi;
}
if ($filterTuluv !== '') {
    $wheres[] = 's.tuluv = ?';
    $params[] = $filterTuluv;
}
if ($wheres) {
    $sql .= ' WHERE '.implode(' AND ', $wheres);
}
$sql .= ' GROUP BY s.suragch_id ORDER BY a.angi_ner, s.ovog, s.ner';

$rows    = all_rows($sql, $params);
$classes = all_rows('SELECT angi_id, angi_ner FROM angi ORDER BY tuvshin, angi_ner');

$editStudent = null;
if ($editingId > 0 && $canManageStudents) {
    $editStudent = one_row('SELECT suragch_id,ovog,ner,email,register_dugaar,angi_id FROM suragch WHERE suragch_id=?', [$editingId]);
}

$hasFilter = $q !== '' || $filterAngi > 0 || $filterTuluv !== '';
include __DIR__ . '/header.php';
?>
<div class="grid lg:grid-cols-3 gap-6">

  <!-- ── Жагсаалт ──────────────────────────────────────────────── -->
  <section class="table-shell lg:col-span-2">

    <div class="p-5 border-b space-y-3">
      <div class="flex items-center justify-between">
        <h3 class="font-semibold text-lg">
          Сурагчид
          <span class="ml-2 text-sm font-normal text-slate-500">нийт <?= count($rows) ?> сурагч </span>
        </h3>
        <?php if ($hasFilter): ?>
          <a href="student_list.php" class="text-sm text-red-500 hover:underline flex items-center gap-1">
            ✕ Шүүлт цэвэрлэх
          </a>
        <?php endif; ?>
      </div>

      <!-- Filter форм -->
      <form method="get" class="grid grid-cols-2 md:grid-cols-4 gap-2">

        <!-- Нэр / имэйл / регистр хайх -->
        <div class="col-span-2 md:col-span-4">
          <input name="q" value="<?= e($q) ?>"
                 class="w-full rounded-xl border-slate-300 px-3 py-2 text-sm"
                 placeholder="Нэр, имэйл эсвэл регистрийн дугаараар хайх…">
        </div>

        <!-- Анги -->
        <select name="filter_angi" class="rounded-xl border-slate-300 px-3 py-2 text-sm">
          <option value="">Бүх анги</option>
          <?php foreach ($classes as $c): ?>
            <option value="<?= (int)$c['angi_id'] ?>" <?= $filterAngi===(int)$c['angi_id'] ? 'selected' : '' ?>>
              <?= e($c['angi_ner']) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <!-- Төлөв -->
        <select name="filter_tuluv" class="rounded-xl border-slate-300 px-3 py-2 text-sm">
          <option value="">Бүх төлөв</option>
          <?php foreach (['Идэвхтэй','Идэвхгүй','Төгссөн','Шилжсэн'] as $t): ?>
            <option value="<?= $t ?>" <?= $filterTuluv===$t ? 'selected' : '' ?>><?= $t ?></option>
          <?php endforeach; ?>
        </select>

        <button class="col-span-2 bg-slate-900 text-white rounded-xl px-4 py-2 text-sm font-medium">
          Шүүх
        </button>
      </form>
    </div>

    <!-- Хүснэгт -->
    <div class="overflow-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="text-left p-4">Нэр</th>
            <th class="text-left p-4">Анги</th>
            <th class="text-left p-4">Имэйл</th>
            <th class="text-left p-4">Регистр</th>
            <th class="text-left p-4">Төлөв</th>
            <?php if ($canManageStudents): ?>
              <th class="text-left p-4">Үйлдэл</th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
          <tr class="border-t hover:bg-slate-50/50">
            <td class="p-4 font-medium"><a class="text-primary hover:underline" href="student_detail.php?id=<?= (int)$row['suragch_id'] ?>"><?= e($row['ovog'].' '.$row['ner']) ?></a></td>
            <td class="p-4">
              <span class="inline-block bg-indigo-50 text-indigo-700 text-xs font-semibold px-2 py-0.5 rounded-lg">
                <?= e((string)$row['angi_ner']) ?>
              </span>
            </td>
            <td class="p-4 text-slate-600"><?= e((string)$row['email']) ?></td>
            <td class="p-4 text-slate-600 font-mono text-xs"><?= e((string)$row['register_dugaar']) ?></td>
            <td class="p-4">
              <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium
                <?= $row['tuluv']==='Идэвхтэй' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500' ?>">
                <?= e((string)$row['tuluv']) ?>
              </span>
            </td>
            <?php if ($canManageStudents): ?>
            <td class="p-4">
              <div class="flex items-center gap-2">
                <a href="student_list.php?edit=<?= (int)$row['suragch_id'] ?><?= $q!=='' ? '&q='.urlencode($q) : '' ?>"
                   class="inline-flex items-center justify-center px-3 py-2 rounded-xl border border-slate-300 bg-white text-slate-800 hover:bg-slate-50 font-medium min-w-[80px]">
                  Засах
                </a>
                <form method="post" onsubmit="return confirm('Энэ сурагчийн бүртгэлийг устгах уу?');" class="inline">
                  <input type="hidden" name="action"     value="delete">
                  <input type="hidden" name="suragch_id" value="<?= (int)$row['suragch_id'] ?>">
                  <button class="inline-flex items-center justify-center px-3 py-2 rounded-xl border border-red-200 bg-red-600 text-white hover:bg-red-700 font-medium min-w-[80px]">
                    Устгах
                  </button>
                </form>
              </div>
            </td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
          <tr>
            <td colspan="<?= $canManageStudents ? 6 : 5 ?>" class="p-10 text-center text-slate-400">
              <?= $hasFilter ? 'Шүүлтэд тохирох сурагч олдсонгүй.' : 'Сурагчийн мэдээлэл олдсонгүй.' ?>
            </td>
          </tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>

  <!-- ── Бүртгэх / Засах форм ──────────────────────────────────── -->
  <?php if ($canCreate): ?>
  <section class="bg-white rounded-2xl shadow-sm border p-6">
    <div class="flex items-center justify-between mb-4 gap-3">
      <h3 class="font-semibold text-lg"><?= $editStudent ? 'Сурагч засах' : 'Сурагч бүртгэх' ?></h3>
      <?php if ($editStudent): ?>
        <a href="student_list.php" class="text-sm text-slate-500 hover:text-slate-800">Цэвэрлэх</a>
      <?php endif; ?>
    </div>
    <form method="post" class="space-y-4">
      <input type="hidden" name="action" value="<?= $editStudent ? 'update' : 'create' ?>">
      <?php if ($editStudent): ?>
        <input type="hidden" name="suragch_id" value="<?= (int)$editStudent['suragch_id'] ?>">
      <?php endif; ?>
      <input name="ovog"  class="w-full rounded-xl border-slate-300 px-4 py-3" placeholder="Овог"  required value="<?= e($editStudent['ovog'] ?? '') ?>">
      <input name="ner"   class="w-full rounded-xl border-slate-300 px-4 py-3" placeholder="Нэр"   required value="<?= e($editStudent['ner']  ?? '') ?>">
      <input name="email" type="email" class="w-full rounded-xl border-slate-300 px-4 py-3" placeholder="Имэйл" required value="<?= e($editStudent['email'] ?? '') ?>">
      <input name="nuuts_ug" type="password" class="w-full rounded-xl border-slate-300 px-4 py-3"
             placeholder="<?= $editStudent ? 'Нууц үг (солих бол бөглөнө)' : 'Нууц үг' ?>"
             <?= $editStudent ? '' : 'required' ?>>
      <input name="register_dugaar" class="w-full rounded-xl border-slate-300 px-4 py-3"
             placeholder="Регистрийн дугаар" value="<?= e($editStudent['register_dugaar'] ?? '') ?>">
      <select name="angi_id" class="w-full rounded-xl border-slate-300 px-4 py-3" required>
        <option value="">Анги сонгох</option>
        <?php foreach ($classes as $class): ?>
          <option value="<?= e((string)$class['angi_id']) ?>"
            <?= ((int)($editStudent['angi_id'] ?? 0)===(int)$class['angi_id']) ? 'selected' : '' ?>>
            <?= e($class['angi_ner']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button class="w-full bg-indigo-700 text-white rounded-xl py-3 font-medium">
        <?= $editStudent ? 'Шинэчлэх' : 'Бүртгэх' ?>
      </button>
    </form>
  </section>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/footer.php'; ?>
