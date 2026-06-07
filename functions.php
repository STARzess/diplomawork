<?php
require_once __DIR__ . '/config.php';

function e(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function flash(?string $message = null, string $type = 'success'): ?array {
    if ($message !== null) {
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
        return null;
    }
    if (!isset($_SESSION['flash'])) return null;
    $data = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $data;
}

function is_logged_in(): bool {
    return !empty($_SESSION['user']);
}

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function current_role(): string {
    return $_SESSION['user']['role'] ?? '';
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function require_roles(array $roles): void {
    require_login();
    if (!in_array(current_role(), $roles, true)) {
        flash('Энэ хуудсанд хандах эрхгүй байна.', 'error');
        header('Location: index.php');
        exit;
    }
}

function dashboard_url_by_role(string $role): string {
    return match($role) {
        'Админ' => 'admin_dashboard.php',
        'Багш' => 'teacher_dashboard.php',
        'Сургалтын алба' => 'training_dashboard.php',
        'Сурагч' => 'student_dashboard.php',
        default => 'index.php',
    };
}

function redirect_dashboard(): void {
    header('Location: ' . dashboard_url_by_role(current_role()));
    exit;
}

function login_user(array $user): void {
    $_SESSION['user'] = $user;
}

function logout_user(): void {
    unset($_SESSION['user']);
    session_regenerate_id(true);
}

function verify_password(string $input, string $stored): bool {
    if ($input === $stored) return true;
    if (password_get_info($stored)['algo'] !== null) {
        return password_verify($input, $stored);
    }
    return false;
}

function find_employee_by_email(string $email): ?array {
    $sql = "SELECT a.ajiltan_id AS id, a.ovog, a.ner, a.email, a.nuuts_ug, e.erkh_ner AS role
            FROM ajiltan a
            JOIN erkh e ON e.erkh_id = a.erkh_id
            WHERE a.email = ? AND a.tuluv = 'Идэвхтэй'
            LIMIT 1";
    $stmt = db()->prepare($sql);
    $stmt->execute([$email]);
    return $stmt->fetch() ?: null;
}

function find_student_by_email(string $email): ?array {
    $sql = "SELECT s.suragch_id AS id, s.ovog, s.ner, s.email, s.nuuts_ug, 'Сурагч' AS role, a.angi_ner
            FROM suragch s
            LEFT JOIN angi a ON a.angi_id = s.angi_id
            WHERE s.email = ? AND s.tuluv = 'Идэвхтэй'
            LIMIT 1";
    $stmt = db()->prepare($sql);
    $stmt->execute([$email]);
    return $stmt->fetch() ?: null;
}

function login_attempt(string $email, string $password): bool {
    $user = find_employee_by_email($email);
    if ($user && verify_password($password, $user['nuuts_ug'])) {
        login_user([
            'id' => (int)$user['id'],
            'name' => trim(($user['ovog'] ?? '') . ' ' . ($user['ner'] ?? '')),
            'email' => $user['email'],
            'role' => $user['role'],
        ]);
        return true;
    }

    $student = find_student_by_email($email);
    if ($student && verify_password($password, $student['nuuts_ug'])) {
        login_user([
            'id' => (int)$student['id'],
            'name' => trim(($student['ovog'] ?? '') . ' ' . ($student['ner'] ?? '')),
            'email' => $student['email'],
            'role' => 'Сурагч',
            'angi_ner' => $student['angi_ner'] ?? '',
        ]);
        return true;
    }
    return false;
}

function count_value(string $sql, array $params = []): int {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

function all_rows(string $sql, array $params = []): array {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function one_row(string $sql, array $params = []): ?array {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row ?: null;
}

function role_id_by_name(string $roleName): ?int {
    $stmt = db()->prepare('SELECT erkh_id FROM erkh WHERE erkh_ner = ? LIMIT 1');
    $stmt->execute([$roleName]);
    $id = $stmt->fetchColumn();
    return $id !== false ? (int)$id : null;
}

function format_dt(?string $value): string {
    if (!$value) return '-';
    try {
        return (new DateTime($value))->format('Y-m-d H:i');
    } catch (Throwable $e) {
        return (string)$value;
    }
}

function ensure_upload_dir(): string {
    $dir = __DIR__ . '/uploads';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    return $dir;
}

function upload_file_if_present(string $field): string {
    if (empty($_FILES[$field]['name']) || !is_uploaded_file($_FILES[$field]['tmp_name'])) {
        return '';
    }
    $dir = ensure_upload_dir();

    $original = basename((string)$_FILES[$field]['name']);
    $original = preg_replace('/[\\\/\:\*\?"\<\>\|]/u', '_', $original);
    $original = trim($original);
    if ($original === '') {
        $original = 'havsralt';
    }

    $info = pathinfo($original);
    $base = $info['filename'] ?? 'havsralt';
    $ext = isset($info['extension']) && $info['extension'] !== '' ? '.' . $info['extension'] : '';
    $name = $base . $ext;
    $target = $dir . '/' . $name;
    $i = 2;
    while (file_exists($target)) {
        $name = $base . '_' . $i . $ext;
        $target = $dir . '/' . $name;
        $i++;
    }

    move_uploaded_file($_FILES[$field]['tmp_name'], $target);
    return 'uploads/' . $name;
}

function display_file_name(?string $pathOrName): string {
    $name = basename((string)$pathOrName);
    if ($name === '' || $name === '.') return '-';
    // Хуучин оруулсан 1777..._filename хэлбэрийн тоон prefix-ийг дэлгэц дээр нууж харуулна.
    $name = preg_replace('/^\d{8,}_/u', '', $name);
    $name = preg_replace('/_{4,}/u', '_', $name);
    return $name ?: basename((string)$pathOrName);
}

function is_admin_or_training(): bool {
    return in_array(current_role(), ['Админ', 'Сургалтын алба'], true);
}

function score_letter(?float $score): string {
    if ($score === null) return '-';
    if ($score < 60) return 'F';
    if ($score < 70) return 'D';
    if ($score < 80) return 'C';
    if ($score < 90) return 'B';
    return 'A';
}

function format_score_with_letter($score): string {
    if ($score === null || $score === '') return '-';
    $value = (float)$score;
    return number_format($value, 2) . ' / ' . score_letter($value);
}

function is_homework_overdue(?string $due): bool {
    if (!$due) return false;
    try { return (new DateTime($due)) < new DateTime(); }
    catch (Throwable $e) { return false; }
}

function format_duration_mn(DateTime $from, DateTime $to): string {
    $seconds = abs($to->getTimestamp() - $from->getTimestamp());
    $days = intdiv($seconds, 86400);
    $hours = intdiv($seconds % 86400, 3600);
    $minutes = intdiv($seconds % 3600, 60);
    if ($days > 0) return $days . ' өдөр ' . $hours . ' цаг';
    if ($hours > 0) return $hours . ' цаг ' . $minutes . ' минут';
    return max(1, $minutes) . ' минут';
}

function homework_time_text(?string $due): string {
    if (!$due) return '-';
    try {
        $now = new DateTime();
        $dueDt = new DateTime($due);
        if ($dueDt < $now) return 'Даалгаврын хугацаа хэтэрсэн: ' . format_duration_mn($dueDt, $now);
        return 'Үлдсэн хугацаа: ' . format_duration_mn($now, $dueDt);
    } catch (Throwable $e) { return '-'; }
}

function close_expired_homeworks(): void {
    try { db()->exec("UPDATE geriin_daalgavar SET tuluv='Хугацаа дууссан' WHERE duusah_ognoo < NOW() AND tuluv='Идэвхтэй'"); }
    catch (Throwable $e) { try { db()->exec("UPDATE geriin_daalgavar SET tuluv='Хаагдсан' WHERE duusah_ognoo < NOW() AND tuluv='Идэвхтэй'"); } catch (Throwable $ignore) {} }
}

function ensure_gamified_homework_schema(): void {
    static $done = false;
    if ($done) return;
    $done = true;

    try {
        $columns = all_rows('SHOW COLUMNS FROM geriin_daalgavar');
        $existing = array_column($columns, 'Field');
        $adds = [
            'togloomchilson' => "ADD COLUMN togloomchilson TINYINT(1) NOT NULL DEFAULT 0 AFTER daalgavar_turul",
            'togloom_turul' => "ADD COLUMN togloom_turul VARCHAR(60) DEFAULT NULL AFTER togloomchilson",
            'togloom_zorilgo' => "ADD COLUMN togloom_zorilgo VARCHAR(255) DEFAULT NULL AFTER togloom_turul",
            'togloom_alham' => "ADD COLUMN togloom_alham TEXT DEFAULT NULL AFTER togloom_zorilgo",
            'od_shagnal' => "ADD COLUMN od_shagnal INT NOT NULL DEFAULT 3 AFTER togloom_alham",
            'turshlaga_onoo' => "ADD COLUMN turshlaga_onoo INT NOT NULL DEFAULT 10 AFTER od_shagnal",
            'tav_duudlaga' => "ADD COLUMN tav_duudlaga VARCHAR(120) DEFAULT NULL AFTER turshlaga_onoo",
        ];

        foreach ($adds as $field => $sql) {
            if (!in_array($field, $existing, true)) {
                db()->exec("ALTER TABLE geriin_daalgavar {$sql}");
            }
        }
    } catch (Throwable $e) {
        // Normal homework pages remain usable if the database user cannot migrate.
    }
}

function grade_number_from_class(?string $className): ?int {
    if (!$className) return null;
    if (preg_match('/^\s*(\d{1,2})/u', $className, $m)) {
        return (int)$m[1];
    }
    return null;
}

function is_primary_grade(?string $className): bool {
    $grade = grade_number_from_class($className);
    return $grade !== null && $grade >= 1 && $grade <= 5;
}

function gamified_steps(?string $steps): array {
    $lines = preg_split('/\R/u', (string)$steps) ?: [];
    $lines = array_values(array_filter(array_map('trim', $lines), fn($line) => $line !== ''));
    return $lines ?: ['Даалгавраа анхааралтай уншина', 'Алхам бүрээр бодож гүйцэтгэнэ', 'Хариугаа илгээнэ'];
}

function gamified_level_label(?string $type): string {
    return match($type) {
        'quiz' => 'Асуултын аялал',
        'mission' => 'Даалгаврын аялал',
        'puzzle' => 'Бодлогын тоглоом',
        'story' => 'Өгүүллэгт аялал',
        default => 'Суралцах аялал',
    };
}

function gamified_progress_percent(?string $due, bool $submitted): int {
    if ($submitted) return 100;
    if (!$due) return 35;
    try {
        $now = new DateTime();
        $dueDt = new DateTime($due);
        if ($dueDt < $now) return 15;
        $hours = max(1, ($dueDt->getTimestamp() - $now->getTimestamp()) / 3600);
        if ($hours <= 24) return 70;
        if ($hours <= 72) return 50;
        return 35;
    } catch (Throwable $e) {
        return 35;
    }
}

function status_badge_class(string $type): string {
    return match($type) {
        'success' => 'bg-green-100 text-green-700',
        'danger' => 'bg-red-100 text-red-700',
        'warning' => 'bg-amber-100 text-amber-700',
        default => 'bg-slate-100 text-slate-700',
    };
}
