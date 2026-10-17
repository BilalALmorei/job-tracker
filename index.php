<?php
// بدء الجلسة وقراءة رسالة النجاح مرة واحدة.
session_start();
// تحميل الاتصال بقاعدة البيانات.
require __DIR__ . "/db.php";
// قراءة حقل نصي وإزالة المسافات من طرفيه.
function readText(array $input, string $key): string
{
    $value = $input[$key] ?? "";

    return is_string($value) ? trim($value) : "";
}
// إنشاء رمز لحماية النماذج إذا لم يكن موجودًا في الجلسة.
if (!isset($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["csrf_token"];
$success = $_SESSION["success"] ?? "";
unset($_SESSION["success"]);
$name = "بلال";

// قراءة الوظائف من قاعدة البيانات قبل معالجة الطلبات.
$statement = $pdo->query(
    "SELECT id, company, title, status, url, created_at
     FROM jobs
     ORDER BY id DESC"
);

$jobs = $statement->fetchAll();
// استخدام معرّف قاعدة البيانات كمفتاح لكل وظيفة.
$jobs = array_column($jobs, null, "id");

// إعداد رسالة الخطأ وحقول الإضافة وقراءة نص البحث من العنوان.
$error = "";
$company = "";
$title = "";
$url = "";
$search = trim($_GET["search"] ?? "");

// قائمة الحالات المسموحة للتحقق منها وعرضها في نموذج التعديل.
$allowedStatuses = [
    "لم أقدم بعد",
    "قيد التجهيز",
    "تم التقديم",
    "مقابلة"
];

// قراءة فلتر الحالة وقبول الحالات المعروفة فقط.
$statusFilter = readText($_GET, "status_filter");

if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = "";
}

// ربط كل حالة باسم فئة CSS ثابت.
$statusClasses = [
    "لم أقدم بعد" => "status-pending",
    "قيد التجهيز" => "status-preparing",
    "تم التقديم" => "status-applied",
    "مقابلة" => "status-interview"
];

// استقبال النماذج التي تضيف البيانات أو تعدلها أو تحذفها.
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  // رفض الطلب إذا كان رمز الحماية مفقودًا أو غير مطابق للجلسة.
    $submittedToken = $_POST["csrf_token"] ?? "";

    if (
        !is_string($submittedToken) ||
        !hash_equals($csrfToken, $submittedToken)
    ) {
        http_response_code(403);
        exit("تعذر التحقق من الطلب. ارجع إلى الصفحة وحدّثها ثم حاول مجددًا.");
    }
    $action = $_POST["action"] ?? "";

    // إضافة وظيفة بعد التحقق من الحقول المطلوبة وطول النصوص.
    if ($action === "add_job") {
        $company = trim($_POST["company"] ?? "");
        $title = trim($_POST["title"] ?? "");
        $url = readText($_POST, "url");

        if ($company === "" || $title === "") {
            $error = "يرجى إدخال اسم الشركة والمسمى الوظيفي.";
        } elseif (
            mb_strlen($company, "UTF-8") > 100 ||
            mb_strlen($title, "UTF-8") > 150
        ) {
            $error = "اسم الشركة يجب ألا يتجاوز 100 حرف، والمسمى الوظيفي 150 حرفًا.";
        } elseif (mb_strlen($company, "UTF-8") > 100 || mb_strlen($title, "UTF-8") > 150) {
    $error = "اسم الشركة يجب ألا يتجاوز 100 حرف، والمسمى الوظيفي 150 حرفًا.";
} elseif (strlen($url) > 2048) {
    $error = "رابط الإعلان طويل جدًا.";
} elseif (
    $url !== "" &&
    (
        filter_var($url, FILTER_VALIDATE_URL) === false ||
        !in_array(
            strtolower((string) parse_url($url, PHP_URL_SCHEME)),
            ["http", "https"],
            true
        )
    )
) {
    $error = "أدخل رابطًا صحيحًا يبدأ بـ http:// أو https://.";
        } else {
          
    // حفظ الوظيفة مع رابط اختياري باستخدام استعلام مجهز.
$statement = $pdo->prepare(
    "INSERT INTO jobs (company, title, status, url)
     VALUES (:company, :title, :status, :url)"
);

$statement->execute([
    "company" => $company,
    "title" => $title,
    "status" => "لم أقدم بعد",
    "url" => $url === "" ? null : $url
]);
    // إبقاء الإضافة للجلسة مؤقتًا أثناء نقل التطبيق.
            $jobs[] = [
                "id" => (int) $pdo->lastInsertId(),
                "company" => $company,
                "title" => $title,
                "status" => "لم أقدم بعد"
            ];

            
            $_SESSION["success"] = "تمت إضافة الوظيفة بنجاح.";

            // العودة بطلب جديد لمنع تكرار الإضافة عند تحديث الصفحة.
            header("Location: /job-tracker/");
            exit;
        }

    // تعديل حالة وظيفة موجودة باستخدام حالة مسموحة فقط.
    } elseif ($action === "update_status") {
        $index = filter_var(
            $_POST["job_index"] ?? "",
            FILTER_VALIDATE_INT
        );
        $status = $_POST["status"] ?? "";

        if (
            $index === false ||
            !isset($jobs[$index]) ||
            !in_array($status, $allowedStatuses, true)
        ) {
            $error = "الوظيفة أو الحالة غير صحيحة.";
        } else {
            // تعديل حالة الصف المرتبط بالبطاقة في قاعدة البيانات.
if (isset($jobs[$index]["id"])) {
    $statement = $pdo->prepare(
        "UPDATE jobs
         SET status = :status
         WHERE id = :id"
    );

    $statement->execute([
        "status" => $status,
        "id" => $jobs[$index]["id"]
    ]);
}
            $jobs[$index]["status"] = $status;
            
            $_SESSION["success"] = "تم تحديث حالة الوظيفة بنجاح.";

            // قراءة البحث وفلتر الحالة للعودة إلى النتائج نفسها.
$returnSearch = readText($_POST, "search");
$returnStatusFilter = readText($_POST, "status_filter");

// قبول الحالات المعروفة فقط.
if (!in_array($returnStatusFilter, $allowedStatuses, true)) {
    $returnStatusFilter = "";
}

// تجهيز قيم الرابط وترميز العربية والمسافات تلقائيًا.
$query = http_build_query([
    "search" => $returnSearch,
    "status_filter" => $returnStatusFilter
]);

header("Location: /job-tracker/?" . $query);
exit;
        }

    // حذف وظيفة موجودة وإعادة ترتيب مفاتيح القائمة.
    } elseif ($action === "delete_job") {
        $index = filter_var(
            $_POST["job_index"] ?? "",
            FILTER_VALIDATE_INT
        );

        if ($index === false || !isset($jobs[$index])) {
            $error = "الوظيفة غير موجودة.";
        } else {
            // حذف الصف المرتبط بالبطاقة من قاعدة البيانات.
if (isset($jobs[$index]["id"])) {
    $statement = $pdo->prepare(
        "DELETE FROM jobs WHERE id = :id"
    );

    $statement->execute([
        "id" => $jobs[$index]["id"]
    ]);
}
            unset($jobs[$index]);
           
            
            $_SESSION["success"] = "تم حذف الوظيفة بنجاح.";

            // الاحتفاظ بالبحث وفلتر الحالة بعد الحذف.
$returnSearch = readText($_POST, "search");
$returnStatusFilter = readText($_POST, "status_filter");

// قبول الحالات المعروفة فقط.
if (!in_array($returnStatusFilter, $allowedStatuses, true)) {
    $returnStatusFilter = "";
}

// بناء رابط العودة إلى النتائج نفسها.
$query = http_build_query([
    "search" => $returnSearch,
    "status_filter" => $returnStatusFilter
]);

header("Location: /job-tracker/?" . $query);
exit;
        }
    } else {
        $error = "العملية غير معروفة.";
    }
}

// قراءة الوظائف المحفوظة في قاعدة البيانات.
$statement = $pdo->query(
    "SELECT id, company, title, status, created_at
     FROM jobs
     ORDER BY id DESC"
);

$databaseJobs = $statement->fetchAll();

// تصفية الوظائف بالبحث مع إبقاء مفاتيحها الأصلية للتعديل والحذف.
// عرض الوظائف التي تطابق نص البحث والحالة المختارة معًا.
$filteredJobs = array_filter(
    $jobs,
    function ($job) use ($search, $statusFilter) {
        // البحث الفارغ يقبل جميع الوظائف.
        $matchesSearch = $search === ""
            || mb_stripos($job["company"], $search, 0, "UTF-8") !== false
            || mb_stripos($job["title"], $search, 0, "UTF-8") !== false;

        // الحالة الفارغة تعني اختيار جميع الحالات.
        $matchesStatus = $statusFilter === ""
            || $job["status"] === $statusFilter;

        // يجب أن يتحقق الشرطان لعرض الوظيفة.
        return $matchesSearch && $matchesStatus;
    }
);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <!-- إعداد ترميز الصفحة وعنوانها وعرضها على الهاتف. -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>متتبع طلبات الوظائف</title>



    <!-- تنسيق الصفحة والبطاقات والنماذج والرسائل. -->
    <style>
        /* تنسيق المحتوى العام وتوسيطه. */
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
            background-color: #f5f5f5;
            color: #222;
        }

        /* بطاقة مستقلة لكل وظيفة. */
        .job-card {
            background-color: white;
            padding: 20px;
            margin-bottom: 16px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        /* التنسيق المشترك لحقول النص وقوائم الاختيار. */
        input[type="text"],
        select {
            padding: 8px;
            border: 1px solid #bbb;
            border-radius: 4px;
            font: inherit;
        }

        /* الأزرار العامة وحالتها عند مرور المؤشر. */
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

        /* تمييز زر الحذف بالأحمر. */
        .delete-button {
            background-color: #dc2626;
        }

        .delete-button:hover {
            background-color: #b91c1c;
        }

        /* عرض رسائل النجاح بالأخضر. */
        .success-message {
            padding: 12px;
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
            border-radius: 4px;
        }

        /* عرض رسائل الخطأ بالأحمر. */
        .error-message {
            padding: 12px;
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
            border-radius: 4px;
        }

        /* بطاقة نموذج الإضافة وعناوين حقوله. */
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

    /* إظهار حالة الطلب داخل شارة صغيرة. */
.status-badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    background-color: #e5e7eb;
    color: #374151;
    font-size: 14px;
    font-weight: bold;
}

    /* لم أقدم بعد: رمادي. */
.status-pending {
    background-color: #e5e7eb;
    color: #374151;
}

/* قيد التجهيز: أصفر فاتح. */
.status-preparing {
    background-color: #fef3c7;
    color: #92400e;
}

/* تم التقديم: أزرق فاتح. */
.status-applied {
    background-color: #dbeafe;
    color: #1e40af;
}

/* مقابلة: أخضر فاتح. */
.status-interview {
    background-color: #dcfce7;
    color: #166534;
}
    </style>
</head>
<body>
    <!-- عنوان التطبيق والترحيب بالمستخدم. -->
    <h1>متتبع طلبات الوظائف</h1>
    <p>مرحبًا، <?php echo htmlspecialchars($name, ENT_QUOTES, "UTF-8"); ?>!</p>

    <!-- رسالة نجاح تظهر مرة واحدة بعد العملية الناجحة. -->
    <?php if ($success !== ""): ?>
        <p class="success-message">
            <?php echo htmlspecialchars($success, ENT_QUOTES, "UTF-8"); ?>
        </p>
    <?php endif; ?>

    <!-- نموذج إضافة وظيفة مع الاحتفاظ بالمدخلات عند الخطأ. -->
    <h2>إضافة وظيفة</h2>
    <form method="post" class="add-job-form">
        <input type="hidden" name="action" value="add_job">
<input
    type="hidden"
    name="csrf_token"
    value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, "UTF-8"); ?>"
>

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

<!-- رابط إعلان الوظيفة؛ حقل اختياري. -->
<p>
    <label for="url">رابط إعلان الوظيفة:</label>
    <input
        type="url"
        id="url"
        name="url"
        value="<?php echo htmlspecialchars($url, ENT_QUOTES, "UTF-8"); ?>"
        maxlength="2048"
        placeholder="https://example.com/job"
    >
</p>

        <button type="submit">إضافة الوظيفة</button>
    </form>

    <!-- رسالة توضح سبب رفض الطلب عند فشل التحقق. -->
    <?php if ($error !== ""): ?>
        <p class="error-message">
            <?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?>
        </p>
    <?php endif; ?>

    <!-- البحث بالنص واختيار حالة الطلب. -->
<form method="get">
    <label for="search">ابحث باسم الشركة أو المسمى الوظيفي:</label>

    <input
        type="text"
        id="search"
        name="search"
        value="<?php echo htmlspecialchars($search, ENT_QUOTES, "UTF-8"); ?>"
    >

    <label for="status-filter">الحالة:</label>

    <select name="status_filter" id="status-filter">
        <option value="" <?php if ($statusFilter === ""): ?>selected<?php endif; ?>>
            جميع الحالات
        </option>

        <?php foreach ($allowedStatuses as $option): ?>
            <option
                value="<?php echo htmlspecialchars($option, ENT_QUOTES, "UTF-8"); ?>"
                <?php if ($statusFilter === $option): ?>selected<?php endif; ?>
            >
                <?php echo htmlspecialchars($option, ENT_QUOTES, "UTF-8"); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit">بحث</button>
    <a href="/job-tracker/">عرض جميع الوظائف</a>
</form>

    <!-- عدد نتائج البحث مقارنة بعدد الوظائف الكلي. -->
    <h2>قائمة الوظائف</h2>
    <p>
        عدد النتائج: <?php echo count($filteredJobs); ?>
        من أصل <?php echo count($jobs); ?> وظيفة
    </p>

    <!-- التمييز بين قائمة فارغة وبحث لا توجد له نتائج. -->
    <?php if (count($jobs) === 0): ?>
        <p>لم تضف أي وظائف بعد. استخدم نموذج الإضافة للبدء.</p>
    <?php elseif (count($filteredJobs) === 0): ?>
        <p>لا توجد وظائف مطابقة للبحث.</p>
    <?php endif; ?>

    <!-- عرض بطاقة لكل وظيفة مع الحفاظ على مفتاحها الأصلي. -->
    <?php foreach ($filteredJobs as $index => $job): ?>
        <?php
// ربط كل حالة باسم فئة CSS ثابت.
$statusClasses = [
    "لم أقدم بعد" => "status-pending",
    "قيد التجهيز" => "status-preparing",
    "تم التقديم" => "status-applied",
    "مقابلة" => "status-interview"
];

$statusClass = $statusClasses[$job["status"]] ?? "";
?>
        <div class="job-card">
<p>معرّف الوظيفة: <?php echo (int) $job["id"]; ?></p>
<!-- تنسيق تاريخ الإضافة للعرض دون تغيير القيمة المحفوظة. -->
<p>
    تاريخ الإضافة:
    <?php
    $createdAt = new DateTimeImmutable($job["created_at"]);
    echo $createdAt->format("d/m/Y H:i");
    ?>
</p>
<?php if (isset($job["id"])): ?>
 
<?php endif; ?>
            <h3><?php echo htmlspecialchars($job["company"], ENT_QUOTES, "UTF-8"); ?></h3>
            <p>المسمى الوظيفي: <?php echo htmlspecialchars($job["title"], ENT_QUOTES, "UTF-8"); ?></p>
            <!-- إظهار رابط الإعلان إذا كان موجودًا وبصيغة مسموحة. -->
<?php
$jobUrl = $job["url"] ?? "";

$hasValidUrl = is_string($jobUrl)
    && $jobUrl !== ""
    && filter_var($jobUrl, FILTER_VALIDATE_URL) !== false
    && in_array(
        strtolower((string) parse_url($jobUrl, PHP_URL_SCHEME)),
        ["http", "https"],
        true
    );
?>

<?php if ($hasValidUrl): ?>
    <p>
        <a
            href="<?php echo htmlspecialchars($jobUrl, ENT_QUOTES, "UTF-8"); ?>"
            target="_blank"
            rel="noopener noreferrer"
        >
            فتح إعلان الوظيفة
        </a>
    </p>
<?php endif; ?>
            <!-- عرض حالة الطلب داخل شارة قابلة للتنسيق. -->
<p>
    حالة الطلب:
    <span class="status-badge <?php echo htmlspecialchars($statusClass, ENT_QUOTES, "UTF-8"); ?>">
        <?php echo htmlspecialchars($job["status"], ENT_QUOTES, "UTF-8"); ?>
    </span>
</p>

            <!-- توضيح الخطوة التالية بحسب حالة الوظيفة. -->
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

            <!-- نموذج تعديل الحالة وإرسال نص البحث للعودة إلى نتائجه. -->
            <form method="post">
                <input type="hidden" name="action" value="update_status">
<input
    type="hidden"
    name="csrf_token"
    value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, "UTF-8"); ?>"
>
               <input type="hidden" name="job_index" value="<?php echo (int) $job["id"]; ?>">
                <input
                    type="hidden"
                    name="search"
                    value="<?php echo htmlspecialchars($search, ENT_QUOTES, "UTF-8"); ?>"
                >

                <!-- إرسال فلتر الحالة للعودة إلى النتائج نفسها بعد الحفظ. -->
<input
    type="hidden"
    name="status_filter"
    value="<?php echo htmlspecialchars($statusFilter, ENT_QUOTES, "UTF-8"); ?>"
>

                <label for="status-<?php echo $index; ?>">تغيير الحالة:</label>
                <select name="status" id="status-<?php echo $index; ?>">
                    <?php foreach ($allowedStatuses as $option): ?>
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

            <!-- نموذج الحذف مع تأكيد المستخدم والاحتفاظ بنص البحث. -->
            <form method="post" onsubmit="return confirm('هل تريد حذف هذه الوظيفة؟');">
                <input type="hidden" name="action" value="delete_job">
<input
    type="hidden"
    name="csrf_token"
    value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, "UTF-8"); ?>"
>
               <input type="hidden" name="job_index" value="<?php echo (int) $job["id"]; ?>">
                <input
                    type="hidden"
                    name="search"
                    value="<?php echo htmlspecialchars($search, ENT_QUOTES, "UTF-8"); ?>"
                >

                <!-- الاحتفاظ بفلتر الحالة بعد الحذف. -->
<input
    type="hidden"
    name="status_filter"
    value="<?php echo htmlspecialchars($statusFilter, ENT_QUOTES, "UTF-8"); ?>"
>

                <button type="submit" class="delete-button">حذف الوظيفة</button>
            </form>
        </div>
    <?php endforeach; ?>
</body>
</html>
