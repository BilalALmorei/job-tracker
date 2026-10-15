<?php
// بدء الجلسة وقراءة رسالة النجاح مرة واحدة.
session_start();
// تحميل الاتصال بقاعدة البيانات.
require __DIR__ . "/db.php";
// إنشاء رمز لحماية النماذج إذا لم يكن موجودًا في الجلسة.
if (!isset($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["csrf_token"];
$success = $_SESSION["success"] ?? "";
unset($_SESSION["success"]);
$name = "بلال";

// بيانات تجريبية تظهر عند بدء جلسة جديدة فقط.
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

// حفظ القائمة الأولية عند الحاجة، ثم قراءة قائمة الجلسة الحالية.
if (!isset($_SESSION["jobs"])) {
    $_SESSION["jobs"] = $jobs;
}
$jobs = $_SESSION["jobs"];

// إعداد رسالة الخطأ وحقول الإضافة وقراءة نص البحث من العنوان.
$error = "";
$company = "";
$title = "";
$search = trim($_GET["search"] ?? "");

// قائمة الحالات المسموحة للتحقق منها وعرضها في نموذج التعديل.
$allowedStatuses = [
    "لم أقدم بعد",
    "قيد التجهيز",
    "تم التقديم",
    "مقابلة"
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
            $jobs[$index]["status"] = $status;
            $_SESSION["jobs"] = $jobs;
            $_SESSION["success"] = "تم تحديث حالة الوظيفة بنجاح.";

            // الاحتفاظ بنص البحث عند العودة بعد تعديل الحالة.
            $returnSearch = trim($_POST["search"] ?? "");
            $redirectUrl = "/job-tracker/";
            if ($returnSearch !== "") {
                $redirectUrl .= "?search=" . rawurlencode($returnSearch);
            }
            header("Location: " . $redirectUrl);
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
            unset($jobs[$index]);
            $jobs = array_values($jobs);
            $_SESSION["jobs"] = $jobs;
            $_SESSION["success"] = "تم حذف الوظيفة بنجاح.";

            // الاحتفاظ بنص البحث عند العودة بعد الحذف.
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

// تصفية الوظائف بالبحث مع إبقاء مفاتيحها الأصلية للتعديل والحذف.
$filteredJobs = $jobs;
if ($search !== "") {
    $filteredJobs = array_filter(
        $jobs,
        function ($job) use ($search) {
            return mb_stripos($job["company"], $search, 0, "UTF-8") !== false
                || mb_stripos($job["title"], $search, 0, "UTF-8") !== false;
        }
    );
}
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

        <button type="submit">إضافة الوظيفة</button>
    </form>

    <!-- رسالة توضح سبب رفض الطلب عند فشل التحقق. -->
    <?php if ($error !== ""): ?>
        <p class="error-message">
            <?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?>
        </p>
    <?php endif; ?>

    <!-- نموذج البحث يستخدم GET لأنه لا يعدل البيانات. -->
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
        <div class="job-card">
            <p>رقم الوظيفة: <?php echo $index + 1; ?></p>
            <h3><?php echo htmlspecialchars($job["company"], ENT_QUOTES, "UTF-8"); ?></h3>
            <p>المسمى الوظيفي: <?php echo htmlspecialchars($job["title"], ENT_QUOTES, "UTF-8"); ?></p>
            <p>حالة الطلب: <?php echo htmlspecialchars($job["status"], ENT_QUOTES, "UTF-8"); ?></p>

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
                <input type="hidden" name="job_index" value="<?php echo $index; ?>">
                <input
                    type="hidden"
                    name="search"
                    value="<?php echo htmlspecialchars($search, ENT_QUOTES, "UTF-8"); ?>"
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
</body>
</html>
