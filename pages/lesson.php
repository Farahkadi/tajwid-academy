
<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
require_once __DIR__ . "/../config/database.php";

// ======================================
// 1. GESTION DE LA LANGUE
// ======================================

$lang = $_GET["lang"] ?? $_POST["lang"] ?? "fr";

if (!in_array($lang, ["fr", "ar", "en"], true)) {
    $lang = "fr";
}

$direction = ($lang === "ar") ? "rtl" : "ltr";

// ======================================
// 2. UTILISATEUR CONNECTÉ
// ======================================

$isLoggedIn = isset($_SESSION["user_id"]);
$userId = $isLoggedIn ? (int) $_SESSION["user_id"] : 0;

// ======================================
// 3. IDENTIFIANT DE LA LEÇON
// ======================================

$lessonId = isset($_GET["id"])
    ? (int) $_GET["id"]
    : (int) ($_POST["id"] ?? 0);

if ($lessonId <= 0) {
    die("Leçon introuvable.");
}

// ======================================
// 4. TRADUCTIONS
// ======================================

$texts = [
    "fr" => [
        "back" => "Retour au cours",
        "previous" => "Leçon précédente",
        "next" => "Leçon suivante",
        "not_found" => "Leçon introuvable",
        "complete" => "Marquer comme terminée",
        "completed" => "Leçon terminée ✓",
        "login" => "Connecte-toi pour enregistrer ta progression.",
        "login_button" => "Se connecter",
        "success" => "Bravo ! Ta progression a été enregistrée.",
        "error" => "Une erreur est survenue. Réessaie.",
        "label" => "APPRENDRE LE TAJWID",
        "lesson" => "LEÇON",
        "home" => "Accueil"
    ],

    "en" => [
        "back" => "Back to course",
        "previous" => "Previous lesson",
        "next" => "Next lesson",
        "not_found" => "Lesson not found",
        "complete" => "Mark as completed",
        "completed" => "Lesson completed ✓",
        "login" => "Log in to save your progress.",
        "login_button" => "Log in",
        "success" => "Well done! Your progress has been saved.",
        "error" => "An error occurred. Please try again.",
        "label" => "LEARN TAJWID",
        "lesson" => "LESSON",
        "home" => "Home"
    ],

    "ar" => [
        "back" => "العودة إلى الدورة",
        "previous" => "الدرس السابق",
        "next" => "الدرس التالي",
        "not_found" => "الدرس غير موجود",
        "complete" => "تحديد الدرس كمكتمل",
        "completed" => "تم إكمال الدرس ✓",
        "login" => "سجّل الدخول لحفظ تقدمك.",
        "login_button" => "تسجيل الدخول",
        "success" => "أحسنت! تم حفظ تقدمك.",
        "error" => "حدث خطأ. حاول مرة أخرى.",
        "label" => "تَعَلَّمِ التَّجْوِيدَ",
        "lesson" => "الدرس",
        "home" => "الرئيسية"
    ]
];

$t = $texts[$lang];

// ======================================
// 5. RÉCUPÉRATION DE LA LEÇON
// ======================================

$stmt = $pdo->prepare("SELECT * FROM lessons WHERE id = :id");
$stmt->execute(["id" => $lessonId]);

$lesson = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lesson) {
    die($t["not_found"]);
}

$courseId = (int) $lesson["course_id"];

// ======================================
// 6. GESTION DE LA PROGRESSION
// ======================================

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && $isLoggedIn) {

    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals(
            $_SESSION["csrf_token"] ?? "",
            $_POST["csrf_token"]
        )
    ) {

        $message = $t["error"];
        $messageType = "error";

    } else {

        try {

            $pdo->beginTransaction();

            // Vérifier si la leçon est déjà terminée
            $stmt = $pdo->prepare(
                "SELECT id FROM lesson_progress
                 WHERE user_id = :user_id
                 AND lesson_id = :lesson_id"
            );

            $stmt->execute([
                "user_id" => $userId,
                "lesson_id" => $lessonId
            ]);

            $existingProgress = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingProgress) {

                $stmt = $pdo->prepare(
                    "UPDATE lesson_progress
                     SET is_completed = 1, completed_at = NOW()
                     WHERE id = :id"
                );

                $stmt->execute([
                    "id" => $existingProgress["id"]
                ]);

            } else {

                $stmt = $pdo->prepare(
                    "INSERT INTO lesson_progress
                     (user_id, lesson_id, is_completed, completed_at)
                     VALUES (:user_id, :lesson_id, 1, NOW())"
                );

                $stmt->execute([
                    "user_id" => $userId,
                    "lesson_id" => $lessonId
                ]);
            }

            // Nombre total de leçons
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM lessons
                 WHERE course_id = :course_id"
            );

            $stmt->execute(["course_id" => $courseId]);

            $totalLessons = (int) $stmt->fetchColumn();

            // Nombre de leçons terminées
            $stmt = $pdo->prepare(
                "SELECT COUNT(*)
                 FROM lesson_progress lp
                 INNER JOIN lessons l ON lp.lesson_id = l.id
                 WHERE lp.user_id = :user_id
                 AND l.course_id = :course_id
                 AND lp.is_completed = 1"
            );

            $stmt->execute([
                "user_id" => $userId,
                "course_id" => $courseId
            ]);

            $completedLessons = (int) $stmt->fetchColumn();

            // Calcul du pourcentage
            $percentage = $totalLessons > 0
                ? ($completedLessons / $totalLessons) * 100
                : 0;

            // Statut du cours
            if ($completedLessons === 0) {
                $status = "not_started";
            } elseif ($completedLessons >= $totalLessons) {
                $status = "completed";
            } else {
                $status = "in_progress";
            }

            // Vérifier la progression du cours
            $stmt = $pdo->prepare(
                "SELECT id FROM user_progress
                 WHERE user_id = :user_id
                 AND course_id = :course_id"
            );

            $stmt->execute([
                "user_id" => $userId,
                "course_id" => $courseId
            ]);

            $existingCourseProgress = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingCourseProgress) {

                $stmt = $pdo->prepare(
                    "UPDATE user_progress
                     SET completed_lessons = :completed,
                         progress_percentage = :percentage,
                         status = :status
                     WHERE id = :id"
                );

                $stmt->execute([
                    "completed" => $completedLessons,
                    "percentage" => $percentage,
                    "status" => $status,
                    "id" => $existingCourseProgress["id"]
                ]);

            } else {

                $stmt = $pdo->prepare(
                    "INSERT INTO user_progress
                     (user_id, course_id, completed_lessons,
                      progress_percentage, status)
                     VALUES (:user_id, :course_id, :completed,
                             :percentage, :status)"
                );

                $stmt->execute([
                    "user_id" => $userId,
                    "course_id" => $courseId,
                    "completed" => $completedLessons,
                    "percentage" => $percentage,
                    "status" => $status
                ]);
            }

            $pdo->commit();

            $message = $t["success"];
            $messageType = "success";

        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $message = $t["error"];
            $messageType = "error";
        }
    }
}

// ======================================
// 7. JETON CSRF
// ======================================

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// ======================================
// 8. VÉRIFIER SI LA LEÇON EST TERMINÉE
// ======================================

$isCompleted = false;

if ($isLoggedIn) {

    $stmt = $pdo->prepare(
        "SELECT id FROM lesson_progress
         WHERE user_id = :user_id
         AND lesson_id = :lesson_id
         AND is_completed = 1"
    );

    $stmt->execute([
        "user_id" => $userId,
        "lesson_id" => $lessonId
    ]);

    $isCompleted = (bool) $stmt->fetch();
}

// ======================================
// 9. LEÇONS PRÉCÉDENTE ET SUIVANTE
// ======================================

$stmt = $pdo->prepare(
    "SELECT id, position FROM lessons
     WHERE course_id = :course_id
     ORDER BY position ASC"
);

$stmt->execute(["course_id" => $courseId]);

$allLessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

$currentIndex = 0;

foreach ($allLessons as $index => $item) {

    if ((int) $item["id"] === $lessonId) {
        $currentIndex = $index;
        break;
    }
}

$previousLesson = $allLessons[$currentIndex - 1]["id"] ?? null;
$nextLesson = $allLessons[$currentIndex + 1]["id"] ?? null;

// ======================================
// 10. RÉCUPÉRER LE COURS
// ======================================

$stmt = $pdo->prepare(
    "SELECT title_" . $lang . " AS course_title
     FROM courses
     WHERE id = :id"
);

$stmt->execute(["id" => $courseId]);

$course = $stmt->fetch(PDO::FETCH_ASSOC);

// Colonnes selon la langue
$titleColumn = "title_" . $lang;
$contentColumn = "content_" . $lang;
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>" dir="<?= $direction ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= htmlspecialchars($lesson[$titleColumn] ?? "") ?> - Tajwid Academy
    </title>

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="lesson-page">

    <!-- HEADER -->
    <header class="main-header">

        <div class="header-top">

            <div class="brand">
                <h1>تَعَلَّمِ التَّجْوِيدَ</h1>
                <h2>Tajwid Academy</h2>
                <p>Learn the Quran, improve your recitation</p>
            </div>

            <div class="language-selector">

                <a href="lesson.php?id=<?= $lessonId ?>&lang=fr"
                   class="<?= $lang === 'fr' ? 'active' : '' ?>">FR</a>

                <a href="lesson.php?id=<?= $lessonId ?>&lang=ar"
                   class="<?= $lang === 'ar' ? 'active' : '' ?>">AR</a>

                <a href="lesson.php?id=<?= $lessonId ?>&lang=en"
                   class="<?= $lang === 'en' ? 'active' : '' ?>">EN</a>

            </div>

        </div>

        <nav class="main-nav">
            <a href="../index.php?lang=<?= urlencode($lang) ?>">
                <?= htmlspecialchars($t["home"]) ?>
            </a>

            <a href="course.php?id=<?= $courseId ?>&lang=<?= urlencode($lang) ?>">
                <?= htmlspecialchars($t["back"]) ?>
            </a>
        </nav>

    </header>

    <!-- MAIN CONTENT -->
    <main class="lesson-container">

        <a class="back-link"
           href="course.php?id=<?= $courseId ?>&lang=<?= urlencode($lang) ?>">
            ← <?= htmlspecialchars($t["back"]) ?>
        </a>

        <!-- LESSON HEADER -->
        <section class="lesson-header">

            <span class="section-label">
                ✦ <?= htmlspecialchars($t["label"]) ?>
            </span>

            <p class="lesson-course-name">
                <?= htmlspecialchars($course["course_title"] ?? "") ?>
            </p>

            <h1>
                <?= htmlspecialchars($lesson[$titleColumn] ?? "") ?>
            </h1>

            <div class="lesson-header-decoration">
                <span></span>
                ✧
                <span></span>
            </div>

        </section>

        <!-- LESSON CONTENT -->
        <section class="lesson-content">

            <?= nl2br(htmlspecialchars($lesson[$contentColumn] ?? "")) ?>

        </section>

        <!-- COMPLETION SECTION -->
        <section class="complete-section">

            <?php if ($message): ?>

                <p class="lesson-message <?= htmlspecialchars($messageType) ?>">
                    <?= htmlspecialchars($message) ?>
                </p>

            <?php endif; ?>

            <?php if (!$isLoggedIn): ?>

                <p><?= htmlspecialchars($t["login"]) ?></p>

                <a class="complete-button"
                   href="login.php?lang=<?= urlencode($lang) ?>">
                    <?= htmlspecialchars($t["login_button"]) ?>
                </a>

            <?php else: ?>

                <form method="POST">

                    <input type="hidden" name="id" value="<?= $lessonId ?>">

                    <input type="hidden" name="lang"
                           value="<?= htmlspecialchars($lang) ?>">

                    <input type="hidden" name="csrf_token"
                           value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

                    <button class="complete-button"
                            type="submit"
                            <?= $isCompleted ? "disabled" : "" ?>>

                        <?= htmlspecialchars(
                            $isCompleted ? $t["completed"] : $t["complete"]
                        ) ?>

                    </button>

                </form>

            <?php endif; ?>

        </section>

        <!-- LESSON NAVIGATION -->
        <div class="lesson-navigation">

            <?php if ($previousLesson): ?>

                <a class="navigation-button"
                   href="lesson.php?id=<?= (int)$previousLesson ?>&lang=<?= urlencode($lang) ?>">
                    ← <?= htmlspecialchars($t["previous"]) ?>
                </a>

            <?php else: ?>

                <span></span>

            <?php endif; ?>

            <?php if ($nextLesson): ?>

                <a class="navigation-button"
                   href="lesson.php?id=<?= (int)$nextLesson ?>&lang=<?= urlencode($lang) ?>">
                    <?= htmlspecialchars($t["next"]) ?> →
                </a>

            <?php endif; ?>

        </div>

    </main>

    <!-- FOOTER -->
    <footer class="main-footer">
        <p>© <?= date("Y") ?> Tajwid Academy — Learn with knowledge and confidence.</p>
    </footer>

</body>
</html>
