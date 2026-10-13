<?php
session_start();
$success = $_SESSION["success"] ?? "";
unset($_SESSION["success"]);
$name = "بلال";

$jobs = [
    [
        "company" => "شركة التدريب",
        "title" => "مطور ويب مبتدئ",
        "status" => "قيد التجهيز"
    ],
    [
        "company" => "شركة تجريبية ثانية",
        "title" => "مطور PHP مبتدئ",
        "status" => "لم أقدم بعد"
    ],
    [
    "company" => "شركة تجريبية ثالثة",
    "title" => "مطور Python مبتدئ",
    "status" => "مقابلة"
    ]
];
if (!isset($_SESSION["jobs"])) {
    $_SESSION["jobs"] = $jobs;
}

$jobs = $_SESSION["jobs"];
$error = "";

$search = trim($_GET["search"] ?? "");

$company = "";
$title = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "add_job") {
        $company = trim($_POST["company"] ?? "");
        $title = trim($_POST["title"] ?? "");

       if ($company === "" || $title === "") {
    $error = "يرجى إدخال اسم الشركة والمسمى الوظيفي.";
} elseif (
    mb_strlen($company, "UTF-8") > 100 ||
    mb_strlen($title, "UTF-8") > 150
) {
    $error = "اسم الشركة يجب ألا يتجاوز 100 حرف، والمسمى الوظيفي 150 حرفًا.";
} else {
            $jobs[] = [
                "company" => $company,
                "title" => $title,
                "status" => "لم أقدم بعد"
            ];

            $_SESSION["jobs"] = $jobs;
            $_SESSION["success"] = "تمت إضافة الوظيفة بنجاح.";

            header("Location: /job-tracker/");
            exit;
        }
    } elseif ($action === "update_status") {
        $index = filter_var(
            $_POST["job_index"] ?? "",
            FILTER_VALIDATE_INT
        );

        $status = $_POST["status"] ?? "";

        $allowedStatuses = [
            "لم أقدم بعد",
            "قيد التجهيز",
            "تم التقديم",
            "مقابلة"
        ];

        if (
            $index === false ||
            !isset($jobs[$index]) ||
            !in_array($status, $allowedStatuses, true)
        ) {
            $error = "الوظيفة أو الحالة غير صحيحة.";
        } else {
            $jobs[$index]["status"] = $status;
$_SESSION["jobs"] = $jobs;
$_SESSION["success"] = "تم تحديث حالة الوظيفة بنجاح.";

$returnSearch = trim($_POST["search"] ?? "");
$redirectUrl = "/job-tracker/";

if ($returnSearch !== "") {
    $redirectUrl .= "?search=" . rawurlencode($returnSearch);
}

header("Location: " . $redirectUrl);
exit;


        }
        } elseif ($action === "delete_job") {
        $index = filter_var(
            $_POST["job_index"] ?? "",
            FILTER_VALIDATE_INT
        );

        if ($index === false || !isset($jobs[$index])) {
            $error = "الوظيفة غير موجودة.";
        } else {
    unset($jobs[$index]);
    $jobs = array_values($jobs);

    $_SESSION["jobs"] = $jobs;
    $_SESSION["success"] = "تم حذف الوظيفة بنجاح.";

    $returnSearch = trim($_POST["search"] ?? "");
$redirectUrl = "/job-tracker/";

if ($returnSearch !== "") {
    $redirectUrl .= "?search=" . rawurlencode($returnSearch);
}

header("Location: " . $redirectUrl);
exit;
}
    } else {
        $error = "العملية غير معروفة.";
    }
}

$filteredJobs = $jobs;

if ($search !== "") {
    $filteredJobs = array_filter(
        $jobs,
        function ($job) use ($search) {
            return mb_stripos($job["company"], $search) !== false
                || mb_stripos($job["title"], $search) !== false;
        }
    );
}

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>متتبع طلبات الوظائف</title>
</head>
<body>
<h2>إضافة وظيفة</h2>

<form method="post" class="add-job-form">
<input type="hidden" name="action" value="add_job">
    <p>
        <label for="company">اسم الشركة:</label>
       <input
    type="text"
    id="company"
    name="company"
    value="<?php echo htmlspecialchars($company, ENT_QUOTES, "UTF-8"); ?>"
     maxlength="100"
    required
>
    </p>

    <p>
        <label for="title">المسمى الوظيفي:</label>
        <input
    type="text"
    id="title"
    name="title"
    value="<?php echo htmlspecialchars($title, ENT_QUOTES, "UTF-8"); ?>"
     maxlength="150"
    required
>
    </p>

    <button type="submit">إضافة الوظيفة</button>
</form>
<?php if ($error !== ""): ?>
    <p class="error-message">
        <?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?>
    </p>
<?php endif; ?>

    <h1>متتبع طلبات الوظائف</h1>
    <p>مرحبًا، <?php echo $name; ?>!</p>
<?php if ($success !== ""): ?>
    <p class="success-message">
        <?php echo htmlspecialchars($success, ENT_QUOTES, "UTF-8"); ?>
    </p>
<?php endif; ?>
<form method="get">
    <label for="search">ابحث باسم الشركة أو المسمى الوظيفي:</label>
    <input
    type="text"
    id="search"
    name="search"
    value="<?php echo htmlspecialchars($search, ENT_QUOTES, "UTF-8"); ?>"
>
    <button type="submit">بحث</button>
<a href="/job-tracker/">عرض جميع الوظائف</a>
</form>

<h2>قائمة الوظائف</h2>
<p>
    عدد النتائج: <?php echo count($filteredJobs); ?>
    من أصل <?php echo count($jobs); ?> وظيفة
</p>

<?php if (count($jobs) === 0): ?>
    <p>لم تضف أي وظائف بعد. استخدم نموذج الإضافة للبدء.</p>
<?php elseif (count($filteredJobs) === 0): ?>
    <p>لا توجد وظائف مطابقة للبحث.</p>
<?php endif; ?>

<?php foreach ($filteredJobs as $index => $job): ?>
<div class="job-card">
<p>رقم الوظيفة: <?php echo $index + 1; ?></p>
   <h3><?php echo htmlspecialchars($job["company"], ENT_QUOTES, "UTF-8"); ?></h3>
<p>المسمى الوظيفي: <?php echo htmlspecialchars($job["title"], ENT_QUOTES, "UTF-8"); ?></p>
<p>حالة الطلب: <?php echo htmlspecialchars($job["status"], ENT_QUOTES, "UTF-8"); ?></p>
    <?php if ($job["status"] === "لم أقدم بعد"): ?>
    <p>الخطوة التالية: تجهيز الطلب.</p>
<?php elseif ($job["status"] === "قيد التجهيز"): ?>
    <p>الخطوة التالية: مراجعة السيرة الذاتية.</p>
<?php elseif ($job["status"] === "تم التقديم"): ?>
    <p>الخطوة التالية: متابعة رد الشركة.</p>
<?php elseif ($job["status"] === "مقابلة"): ?>
    <p>الخطوة التالية: التحضير للمقابلة.</p>
<?php else: ?>
    <p>حالة غير معروفة؛ راجع البيانات.</p>
<?php endif; ?>
<form method="post">
    <input type="hidden" name="action" value="update_status">
    <input type="hidden" name="job_index" value="<?php echo $index; ?>">
    <input
    type="hidden"
    name="search"
    value="<?php echo htmlspecialchars($search, ENT_QUOTES, "UTF-8"); ?>"
>

    <label for="status-<?php echo $index; ?>">تغيير الحالة:</label>

    <select name="status" id="status-<?php echo $index; ?>">
        <?php foreach (["لم أقدم بعد", "قيد التجهيز", "تم التقديم", "مقابلة"] as $option): ?>
            <option
                value="<?php echo htmlspecialchars($option, ENT_QUOTES, "UTF-8"); ?>"
                <?php if ($job["status"] === $option): ?>selected<?php endif; ?>
            >
                <?php echo htmlspecialchars($option, ENT_QUOTES, "UTF-8"); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit">حفظ الحالة</button>
</form>
<form method="post"
      onsubmit="return confirm('هل تريد حذف هذه الوظيفة؟');">
    <input type="hidden" name="action" value="delete_job">
    <input type="hidden" name="job_index" value="<?php echo $index; ?>">
    
    <input
    type="hidden"
    name="search"
    value="<?php echo htmlspecialchars($search, ENT_QUOTES, "UTF-8"); ?>"
>

    <button type="submit" class="delete-button">حذف الوظيفة</button>
</form>
    </div>
<?php endforeach; ?>

<style>
    body {
.job-card {
    background-color: white;
    padding: 20px;
    margin-bottom: 16px;
    border: 1px solid #ddd;
    border-radius: 8px;
}
input[type="text"],
select {
    padding: 8px;
    border: 1px solid #bbb;
    border-radius: 4px;
    font: inherit;
}

button {
    padding: 8px 14px;
    border: none;
    border-radius: 4px;
    background-color: #2563eb;
    color: white;
    font: inherit;
    cursor: pointer;
}

button:hover {
    background-color: #1d4ed8;
}

        font-family: Arial, sans-serif;
        max-width: 800px;
        margin: 40px auto;
        padding: 0 20px;
        background-color: #f5f5f5;
        color: #222;
    }
.delete-button {
    background-color: #dc2626;
}

.delete-button:hover {
    background-color: #b91c1c;
}

.success-message {
    padding: 12px;
    background-color: #dcfce7;
    color: #166534;
    border: 1px solid #86efac;
    border-radius: 4px;
}

.error-message {
    padding: 12px;
    background-color: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
    border-radius: 4px;
}

.add-job-form {
    background-color: white;
    padding: 20px;
    margin-bottom: 24px;
    border: 1px solid #ddd;
    border-radius: 8px;
}

.add-job-form label {
    display: block;
    margin-bottom: 6px;
}

.add-job-form input[type="text"] {
    width: 100%;
    box-sizing: border-box;
}
</style>
</body>
</html>