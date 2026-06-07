<?php
require_once __DIR__ . '/functions.php';
require_roles(['Админ', 'Сургалтын алба']);

$pdo       = db();
$canManage = in_array(current_role(), ['Админ','Сургалтын алба'], true);
$editingId = (int)($_GET['edit'] ?? 0);

/* ── POST: бүртгэх / засах / устгах ─────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canManage) {
    $action = $_POST['action'] ?? 'create';

    if ($action === 'delete') {
        $deleteId = (int)($_POST['ajiltan_id'] ?? 0);
        if ($deleteId > 0) {
            if ($deleteId === (int)(current_user()['id'] ?? 0)) {
                flash('Өөрийн бүртгэлийг устгах боломжгүй.', 'error');
            } else {
                try {
                    $pdo->prepare('DELETE FROM ajiltan WHERE ajiltan_id = ?')->execute([$deleteId]);
                    flash('Ажилтны бүртгэл амжилттай устгагдлаа.');
                } catch (Throwable $e) {
                    flash('Устгах боломжгүй байна. Холбоотой өгөгдөл байгаа эсэхийг шалгана уу.', 'error');
                }
            }
        }
        header('Location: employee_list.php'); exit;
    }

    $ajiltanId = (int)($_POST['ajiltan_id'] ?? 0);
    $ovog      = trim($_POST['ovog']        ?? '');
    $ner       = trim($_POST['ner']         ?? '');
    $email     = trim($_POST['email']       ?? '');
    $password  = trim($_POST['nuuts_ug']    ?? '');
    $erkhId    = (int)($_POST['erkh_id']    ?? 0);
    $tuluv     = trim($_POST['tuluv']       ?? 'Идэвхтэй');

    if (!$ovog || !$ner || !$email || !$erkhId) {
        flash('Овог, нэр, имэйл, эрхийг заавал бөглөнө үү.', 'error');
        $redirect = 'employee_list.php'.($action==='update'&&$ajiltanId ? '?edit='.$ajiltanId : '');
        header('Location: '.$redirect); exit;
    }

    try {
        if ($action === 'update' && $ajiltanId > 0) {
            $current = one_row('SELECT ajiltan_id,nuuts_ug FROM ajiltan WHERE ajiltan_id=?', [$ajiltanId]);
            if (!$current) {
                flash('Засах ажилтан олдсонгүй.', 'error');
            } else {
                if ($password !== '') {
                    $pdo->prepare('UPDATE ajiltan SET ovog=?,ner=?,email=?,nuuts_ug=?,erkh_id=?,tuluv=? WHERE ajiltan_id=?')
                        ->execute([$ovog,$ner,$email,password_hash($password,PASSWORD_DEFAULT),$erkhId,$tuluv,$ajiltanId]);
                } else {
                    $pdo->prepare('UPDATE ajiltan SET ovog=?,ner=?,email=?,erkh_id=?,tuluv=? WHERE ajiltan_id=?')
                        ->execute([$ovog,$ner,$email,$erkhId,$tuluv,$ajiltanId]);
                }
                flash('Ажилтны мэдээлэл амжилттай шинэчлэгдлээ.');
            }
            header('Location: employee_list.php'); exit;
        }
        if ($password === '') {
            flash('Шинэ ажилтан бүртгэхдээ нууц үг оруулна уу.', 'error');
            header('Location: employee_list.php'); exit;
        }
        $pdo->prepare('INSERT INTO ajiltan (ovog,ner,email,nuuts_ug,erkh_id,tuluv) VALUES (?,?,?,?,?,?)')
            ->execute([$ovog,$ner,$email,password_hash($password,PASSWORD_DEFAULT),$erkhId,$tuluv]);
        flash('Ажилтан амжилттай бүртгэгдлээ.');
        header('Location: employee_list.php'); exit;
    } catch (Throwable $e) {
        flash('Мэдээлэл хадгалах үед алдаа гарлаа. Имэйл давхардсан эсэхийг шалгана уу.', 'error');
        $redirect = 'employee_list.php'.($action==='update'&&$ajiltanId ? '?edit='.$ajiltanId : '');
        header('Location: '.$redirect); exit;
    }
}

/* ── GET filter параметрүүд ──────────────────────────────────── */
$q           = trim($_GET['q']           ?? '');
$filterErkh  = (int)($_GET['filter_erkh']  ?? 0);
$filterTuluv = trim($_GET['filter_tuluv']  ?? '');

$pageTitle = 'Ажилтны жагсаалт';

/* ── SQL ─────────────────────────────────────────────────────── */
$sql    = 'SELECT a.ajiltan_id, a.ovog, a.ner, a.email, a.tuluv, a.erkh_id, e.erkh_ner
           FROM ajiltan a
           JOIN erkh e ON e.erkh_id = a.erkh_id';
$params = [];
$wheres = [];

if ($q !== '') {
    $wheres[] = '(a.ovog LIKE ? OR a.ner LIKE ? OR a.email LIKE ?)';
    $like = "%{$q}%";
    array_push($params, $like, $like, $like);
}
if ($filterErkh > 0) {
    $wheres[] = 'a.erkh_id = ?';
    $params[] = $filterErkh;
}
if ($filterTuluv !== '') {
    $wheres[] = 'a.tuluv = ?';
    $params[] = $filterTuluv;
}
if ($wheres) {
    $sql .= ' WHERE '.implode(' AND ', $wheres);
}
$sql .= ' ORDER BY a.erkh_id, a.ovog, a.ner';

$rows         = all_rows($sql, $params);
$roles        = all_rows('SELECT erkh_id, erkh_ner FROM erkh ORDER BY erkh_id');
$editEmployee = null;
if ($editingId > 0) {
    $editEmployee = one_row('SELECT ajiltan_id,ovog,ner,email,erkh_id,tuluv FROM ajiltan WHERE ajiltan_id=?', [$editingId]);
}

$hasFilter = $q !== '' || $filterErkh > 0 || $filterTuluv !== '';
include __DIR__ . '/header.php';
?>
<div class="grid lg:grid-cols-3 gap-6">

  <!-- ── Жагсаалт ──────────────────────────────────────────────── -->
  <section class="lg:col-span-2 bg-white rounded-2xl shadow-sm border overflow-hidden">

    <div class="p-5 border-b space-y-3">
      <div class="flex items-center justify-between">
        <h3 class="font-semibold text-lg">
          Ажилтнууд
          <span class="ml-2 text-sm font-normal text-slate-500"><?= count($rows) ?> үр дүн</span>
        </h3>
        <?php if ($hasFilter): ?>
          <a href="employee_list.php" class="text-sm text-red-500 hover:underline flex items-center gap-1">
            ✕ Шүүлт цэвэрлэх
          </a>
        <?php endif; ?>
      </div>

      <!-- Filter форм -->
      <form method="get" class="grid grid-cols-2 md:grid-cols-4 gap-2">

        <!-- Нэр / имэйл хайх -->
        <div class="col-span-2 md:col-span-4">
          <input name="q" value="<?= e($q) ?>"
                 class="w-full rounded-xl border-slate-300 px-3 py-2 text-sm"
                 placeholder="Нэр эсвэл имэйлээр хайх…">
        </div>

        <!-- Эрх -->
        <select name="filter_erkh" class="rounded-xl border-slate-300 px-3 py-2 text-sm">
          <option value="">Бүх эрх</option>
          <?php foreach ($roles as $r): ?>
            <option value="<?= (int)$r['erkh_id'] ?>" <?= $filterErkh===(int)$r['erkh_id'] ? 'selected' : '' ?>>
              <?= e($r['erkh_ner']) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <!-- Төлөв -->
        <select name="filter_tuluv" class="rounded-xl border-slate-300 px-3 py-2 text-sm">
          <option value="">Бүх төлөв</option>
          <option value="Идэвхтэй" <?= $filterTuluv==='Идэвхтэй' ? 'selected' : '' ?>>Идэвхтэй</option>
          <option value="Идэвхгүй"  <?= $filterTuluv==='Идэвхгүй'  ? 'selected' : '' ?>>Идэвхгүй</option>
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
            <th class="text-left p-4">Эрх</th>
            <th class="text-left p-4">Имэйл</th>
            <th class="text-left p-4">Төлөв</th>
            <th class="text-left p-4">Үйлдэл</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
          <tr class="border-t hover:bg-slate-50/50">
            <td class="p-4 font-medium"><a class="text-primary hover:underline" href="employee_detail.php?id=<?= (int)$row['ajiltan_id'] ?>"><?= e($row['ovog'].' '.$row['ner']) ?></a></td>
            <td class="p-4">
              <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium
                <?= $row['erkh_ner']==='Багш' ? 'bg-blue-100 text-blue-700' :
                   ($row['erkh_ner']==='Админ' ? 'bg-purple-100 text-purple-700' : 'bg-amber-100 text-amber-700') ?>">
                <?= e($row['erkh_ner']) ?>
              </span>
            </td>
            <td class="p-4 text-slate-600"><?= e($row['email']) ?></td>
            <td class="p-4">
              <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium
                <?= $row['tuluv']==='Идэвхтэй' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500' ?>">
                <?= e($row['tuluv']) ?>
              </span>
            </td>
            <td class="p-4">
              <div class="flex items-center gap-2">
                <a href="employee_list.php?edit=<?= (int)$row['ajiltan_id'] ?><?= $q!=='' ? '&q='.urlencode($q) : '' ?>"
                   class="inline-flex items-center justify-center px-3 py-2 rounded-xl border border-slate-300 bg-white text-slate-800 hover:bg-slate-50 font-medium min-w-[80px]">
                  Засах
                </a>
                <form method="post" onsubmit="return confirm('Энэ ажилтны бүртгэлийг устгах уу?');" class="inline">
                  <input type="hidden" name="action"     value="delete">
                  <input type="hidden" name="ajiltan_id" value="<?= (int)$row['ajiltan_id'] ?>">
                  <button class="inline-flex items-center justify-center px-3 py-2 rounded-xl border border-red-200 bg-red-600 text-white hover:bg-red-700 font-medium min-w-[80px]">
                    Хасах
                  </button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
          <tr>
            <td colspan="5" class="p-10 text-center text-slate-400">
              <?= $hasFilter ? 'Шүүлтэд тохирох ажилтан олдсонгүй.' : 'Ажилтан олдсонгүй.' ?>
            </td>
          </tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>

  <!-- ── Бүртгэх / Засах форм ──────────────────────────────────── -->
  <?php if ($canManage): ?>
  <section class="bg-white rounded-2xl shadow-sm border p-6">
    <div class="flex items-center justify-between mb-4 gap-3">
      <h3 class="font-semibold text-lg"><?= $editEmployee ? 'Ажилтан засах' : 'Ажилтан бүртгэх' ?></h3>
      <?php if ($editEmployee): ?>
        <a href="employee_list.php" class="text-sm text-slate-500 hover:text-slate-800">Цэвэрлэх</a>
      <?php endif; ?>
    </div>
    <form method="post" class="space-y-4">
      <input type="hidden" name="action" value="<?= $editEmployee ? 'update' : 'create' ?>">
      <?php if ($editEmployee): ?>
        <input type="hidden" name="ajiltan_id" value="<?= (int)$editEmployee['ajiltan_id'] ?>">
      <?php endif; ?>
      <input name="ovog"  class="w-full rounded-xl border-slate-300 px-4 py-3" placeholder="Овог"  required value="<?= e($editEmployee['ovog'] ?? '') ?>">
      <input name="ner"   class="w-full rounded-xl border-slate-300 px-4 py-3" placeholder="Нэр"   required value="<?= e($editEmployee['ner']  ?? '') ?>">
      <input name="email" type="email" class="w-full rounded-xl border-slate-300 px-4 py-3" placeholder="Имэйл" required value="<?= e($editEmployee['email'] ?? '') ?>">
      <input name="nuuts_ug" type="password" class="w-full rounded-xl border-slate-300 px-4 py-3"
             placeholder="<?= $editEmployee ? 'Нууц үг (солих бол бөглөнө)' : 'Нууц үг' ?>"
             <?= $editEmployee ? '' : 'required' ?>>
      <select name="erkh_id" class="w-full rounded-xl border-slate-300 px-4 py-3" required>
        <option value="">Эрх сонгох</option>
        <?php foreach ($roles as $r): ?>
          <option value="<?= (int)$r['erkh_id'] ?>"
            <?= ((int)($editEmployee['erkh_id'] ?? 0)===(int)$r['erkh_id']) ? 'selected' : '' ?>>
            <?= e($r['erkh_ner']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <select name="tuluv" class="w-full rounded-xl border-slate-300 px-4 py-3" required>
        <?php $ct = $editEmployee['tuluv'] ?? 'Идэвхтэй'; ?>
        <option value="Идэвхтэй" <?= $ct==='Идэвхтэй' ? 'selected' : '' ?>>Идэвхтэй</option>
        <option value="Идэвхгүй"  <?= $ct==='Идэвхгүй'  ? 'selected' : '' ?>>Идэвхгүй</option>
      </select>
      <button class="w-full bg-indigo-700 text-white rounded-xl py-3 font-medium">
        <?= $editEmployee ? 'Шинэчлэх' : 'Бүртгэх' ?>
      </button>
    </form>
  </section>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/footer.php'; ?>
