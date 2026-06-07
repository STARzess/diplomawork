<?php
require_once __DIR__ . '/functions.php';
require_roles(['Админ','Сургалтын алба','Багш']);
$pageTitle = 'Сурагч хичээлд бүртгэх';
include __DIR__ . '/header.php';
?>
<div class="form-card p-8 max-w-3xl">
  <h3 class="font-semibold text-xl mb-4">Ангиар автоматаар хамаарах болсон</h3>
  <p class="text-slate-600 leading-7 mb-4">Системийн шинэчлэлтээр хичээл нь ангитай шууд холбоотой болсон. Тиймээс тухайн ангид бүртгэлтэй бүх сурагч хичээлд автоматаар хамаарна.</p>
  <p class="text-slate-600 leading-7 mb-6">Сурагчийг нэг нэгээр нь хичээлд бүртгэх шаардлагагүй. Хичээлийн хуваарилалтыг <strong>Багш, ангид хуваарилах</strong> хэсгээс, харин сурагчийн ангийг <strong>Сурагч</strong> хэсгээс удирдана.</p>
  <div class="flex flex-wrap gap-3">
    <a href="course_assignment.php" class="rounded-xl bg-primary text-white px-5 py-3 font-medium">Багш, ангид хуваарилах</a>
    <a href="student_list.php" class="rounded-xl bg-white border border-slate-300 px-5 py-3 font-medium">Сурагчийн жагсаалт</a>
  </div>
</div>
<?php include __DIR__ . '/footer.php'; ?>
