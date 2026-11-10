
<?php
session_start();


// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
require_once __DIR__ . "/../config/database.php";

// ======================================
// 1. LANGUE ET IDENTIFIANT
// ======================================

$lang = $_GET["lang"] ?? $_POST["lang"] ?? $_SESSION["lang"] ?? "fr";

if (!in_array($lang, ["fr", "ar", "en"], true)) {
    $lang = "fr";
}

$_SESSION["lang"] = $lang;

$direction = ($lang === "ar") ? "rtl" : "ltr";

$exerciseId = isset($_GET["id"])
    ? (int) $_GET["id"]
    : (int) ($_POST["id"] ?? 0);

if ($exerciseId <= 0) {
    die("Identifiant de l'exercice invalide.");
}

// ======================================
// 2. TRADUCTIONS
// ======================================

$texts = [

    "fr" => [
        "title" => "Exercice de Tajwid",
        "back" => "Retour aux exercices",
        "question" => "Question",
        "choose" => "Choisis une réponse :",
        "submit" => "Valider mes réponses",
        "score" => "Ton résultat",
        "correct" => "Bonne réponse !",
        "incorrect" => "Mauvaise réponse",
        "explanation" => "Explication",
        "right_answer" => "Bonne réponse :",
        "retry" => "Recommencer",
        "empty" => "Aucune question disponible pour cet exercice.",
        "error" => "Une erreur est survenue.",
        "out_of" => "sur",
        "points" => "points",
        "not_answered" => "Non répondu",
        "home" => "Accueil",
        "tagline" => "Apprends le Coran et améliore ta récitation",
        "completed" => "Exercice terminé",
        "progress" => "Résultat obtenu"
    ],

    "en" => [
        "title" => "Tajwid Exercise",
        "back" => "Back to exercises",
        "question" => "Question",
        "choose" => "Choose an answer:",
        "submit" => "Submit my answers",
        "score" => "Your result",
        "correct" => "Correct answer!",
        "incorrect" => "Wrong answer",
        "explanation" => "Explanation",
        "right_answer" => "Correct answer:",
        "retry" => "Try again",
        "empty" => "No questions available for this exercise.",
        "error" => "An error occurred.",
        "out_of" => "out of",
        "points" => "points",
        "not_answered" => "Not answered",
        "home" => "Home",
        "tagline" => "Learn the Quran and improve your recitation",
        "completed" => "Exercise completed",
        "progress" => "Your score"
    ],

    "ar" => [
        "title" => "تمرين التجويد",
        "back" => "العودة إلى التمارين",
        "question" => "السؤال",
        "choose" => "اختر إجابة:",
        "submit" => "إرسال الإجابات",
        "score" => "نتيجتك",
        "correct" => "إجابة صحيحة!",
        "incorrect" => "إجابة خاطئة",
        "explanation" => "التفسير",
        "right_answer" => "الإجابة الصحيحة:",
        "retry" => "إعادة المحاولة",
        "empty" => "لا توجد أسئلة متاحة لهذا التمرين.",
        "error" => "حدث خطأ.",
        "out_of" => "من",
        "points" => "نقاط",
        "not_answered" => "لم تتم الإجابة",
        "home" => "الرئيسية",
        "tagline" => "تعلم القرآن وحسّن تلاوتك",
        "completed" => "اكتمل التمرين",
        "progress" => "نتيجتك"
    ]
];

$t = $texts[$lang];

// ======================================
// 3. RÉCUPÉRER L'EXERCICE ET LES QUESTIONS
// ======================================

$titleColumn = "title_" . $lang;

try {

    $stmt = $pdo->prepare("
        SELECT id, $titleColumn AS title
        FROM exercises
        WHERE id = ?
    ");

    $stmt->execute([$exerciseId]);

    $exercise = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exercise) {
        die("Exercice introuvable.");
    }

    $stmt = $pdo->prepare("
        SELECT *
        FROM exercise_questions
        WHERE exercise_id = ?
        ORDER BY id ASC
    ");

    $stmt->execute([$exerciseId]);

    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    die($t["error"]);

}

// ======================================
// 4. VÉRIFIER ET ENREGISTRER LES RÉPONSES
// ======================================

$submitted = false;

$score = 0;
$total = count($questions);
$percentage = 0;

$results = [];

// --------------------------------------
// Si le formulaire est envoyé
// --------------------------------------

if ($_SERVER["REQUEST_METHOD"] === "POST" && $total > 0) {

    foreach ($questions as $question) {

        $questionId = (int) $question["id"];

        $selected = isset($_POST["answer"][$questionId])
            ? (int) $_POST["answer"][$questionId]
            : 0;

        $correctOption = (int) $question["correct_option"];

        // Vérifier que la réponse est entre 1 et 3
        if ($selected < 1 || $selected > 3) {
            $selected = 0;
        }

        $isCorrect = ($selected === $correctOption);

        if ($isCorrect) {
            $score++;
        }

        $results[$questionId] = [
            "selected" => $selected,
            "correct" => $correctOption,
            "is_correct" => $isCorrect
        ];
    }

    $percentage = round(($score / $total) * 100, 2);

    // ----------------------------------
    // Enregistrer le résultat en BDD
    // ----------------------------------

    if (isset($_SESSION["user_id"])) {

        try {

            $userId = (int) $_SESSION["user_id"];

            $stmt = $pdo->prepare("
                INSERT INTO exercise_results (
                    user_id,
                    exercise_id,
                    score,
                    total_points,
                    correct_answers,
                    total_questions
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $userId,
                $exerciseId,
                $percentage,
                $total,
                $score,
                $total
            ]);

        } catch (PDOException $e) {

            die($t["error"]);

        }
    }

    // ----------------------------------
    // Conserver le résultat en session
    // ----------------------------------

    $_SESSION["last_exercise_result"] = [
        "exercise_id" => $exerciseId,
        "score" => $score,
        "total" => $total,
        "percentage" => $percentage,
        "results" => $results
    ];

    // Redirection pour éviter une double soumission
    header(
        "Location: take_exercise.php?id=" .
        $exerciseId .
        "&lang=" .
        urlencode($lang) .
        "&result=1"
    );

    exit;
}

// ======================================
// 5. RÉCUPÉRER LE RÉSULTAT APRÈS REDIRECTION
// ======================================

if (
    isset($_GET["result"]) &&
    $_GET["result"] == "1" &&
    isset($_SESSION["last_exercise_result"]) &&
    $_SESSION["last_exercise_result"]["exercise_id"] === $exerciseId
) {

    $savedResult = $_SESSION["last_exercise_result"];

    $submitted = true;

    $score = $savedResult["score"];
    $total = $savedResult["total"];
    $percentage = $savedResult["percentage"];

    $results = $savedResult["results"];

} else {

    // Nouvelle tentative : ne pas afficher l'ancien résultat
    if (isset($_SESSION["last_exercise_result"])) {
        unset($_SESSION["last_exercise_result"]);
    }

}

// ======================================
// 6. FONCTION DE SÉCURISATION
// ======================================

function clean($value) {

    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );

}

?>

<!DOCTYPE html>
<html lang="<?= clean($lang) ?>" dir="<?= $direction ?>">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= clean($exercise["title"]) ?> - Tajwid Academy
    </title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body class="take-exercise-page">

<header class="main-header">

    <div class="header-top">

        <div class="brand">

            <h1>تَعَلَّمِ التَّجْوِيدَ</h1>

            <h2>Tajwid Academy</h2>

            <p><?= clean($t["tagline"]) ?></p>

        </div>

        <div class="language-selector">

            <a href="?id=<?= $exerciseId ?>&lang=fr"
               class="<?= $lang === 'fr' ? 'active' : '' ?>">
                FR
            </a>

            <a href="?id=<?= $exerciseId ?>&lang=ar"
               class="<?= $lang === 'ar' ? 'active' : '' ?>">
                AR
            </a>

            <a href="?id=<?= $exerciseId ?>&lang=en"
               class="<?= $lang === 'en' ? 'active' : '' ?>">
                EN
            </a>

        </div>

    </div>

    <nav class="main-nav">

        <a href="../index.php?lang=<?= urlencode($lang) ?>">
            <?= clean($t["home"]) ?>
        </a>

        <a href="exercises.php?lang=<?= urlencode($lang) ?>">
            <?= clean($t["back"]) ?>
        </a>

    </nav>

</header>


<main class="take-exercise-container">

    <div class="exercise-page-heading">

        <span class="exercise-page-label">
            TAJWID ACADEMY
        </span>

        <h1>
            📚 <?= clean($exercise["title"]) ?>
        </h1>

        <p>
            <?= clean($t["title"]) ?>
        </p>

    </div>


    <?php if (empty($questions)): ?>

        <div class="exercise-empty">

            <span>📖</span>

            <p><?= clean($t["empty"]) ?></p>

        </div>

    <?php else: ?>


        <?php if ($submitted): ?>

            <section class="exercise-result">

                <div class="result-icon">🏆</div>

                <span class="result-label">
                    <?= clean($t["completed"]) ?>
                </span>

                <h2>
                    <?= clean($t["score"]) ?>
                </h2>

                <div class="result-score">
                    <?= $score ?> / <?= $total ?>
                </div>

                <p class="result-percentage">
                    <?= $percentage ?> %
                </p>

                <div class="result-progress">

                    <div style="width: <?= $percentage ?>%;"></div>

                </div>

                <p class="result-description">
                    <?= clean($t["progress"]) ?>
                </p>

            </section>

        <?php endif; ?>


        <form method="POST"
              action="take_exercise.php?id=<?= $exerciseId ?>&lang=<?= urlencode($lang) ?>">

            <input
                type="hidden"
                name="id"
                value="<?= $exerciseId ?>"
            >

            <input
                type="hidden"
                name="lang"
                value="<?= clean($lang) ?>"
            >


            <?php foreach ($questions as $index => $question): ?>

                <?php

                    $questionId = (int) $question["id"];

                    $questionText = $question["question_" . $lang];

                    $explanation = $question["explanation_" . $lang];

                    $isCorrect = $submitted
                        ? $results[$questionId]["is_correct"]
                        : false;

                    $selected = $submitted
                        ? $results[$questionId]["selected"]
                        : 0;

                    $correctOption = (int) $question["correct_option"];

                ?>


                <div class="exercise-question-card
                    <?php

                    if ($submitted) {

                        echo $isCorrect
                            ? 'question-correct'
                            : 'question-incorrect';

                    }

                    ?>">

                    <div class="question-title-row">

                        <span class="question-number">
                            <?= $index + 1 ?>
                        </span>

                        <h3>

                            <?= clean($t["question"]) ?>

                            <?= $index + 1 ?>

                        </h3>

                        <?php if ($submitted): ?>

                            <span class="question-status">

                                <?= $isCorrect ? "✓" : "✕" ?>

                            </span>

                        <?php endif; ?>

                    </div>


                    <p class="exercise-question-text">

                        <?= clean($questionText) ?>

                    </p>

                    <p class="exercise-choose-text">

                        <?= clean($t["choose"]) ?>

                    </p>


                    <div class="exercise-options">

                        <?php for ($i = 1; $i <= 3; $i++): ?>

                            <?php

                                $optionText = $question["option" . $i . "_" . $lang];

                                $optionClass = "";

                                if ($submitted && $i === $correctOption) {

                                    $optionClass = "option-correct";

                                } elseif (
                                    $submitted &&
                                    $i === $selected &&
                                    !$isCorrect
                                ) {

                                    $optionClass = "option-incorrect";

                                }

                            ?>


                            <label class="exercise-option <?= $optionClass ?>">

                                <input
                                    type="radio"
                                    name="answer[<?= $questionId ?>]"
                                    value="<?= $i ?>"
                                    <?= $selected === $i ? "checked" : "" ?>
                                    <?= $submitted ? "disabled" : "" ?>
                                >

                                <span class="option-text">

                                    <?= clean($optionText) ?>

                                </span>

                                <?php if ($submitted && $i === $correctOption): ?>

                                    <span class="option-mark">
                                        ✓
                                    </span>

                                <?php elseif (
                                    $submitted &&
                                    $i === $selected &&
                                    !$isCorrect
                                ): ?>

                                    <span class="option-mark">
                                        ✕
                                    </span>

                                <?php endif; ?>

                            </label>

                        <?php endfor; ?>

                    </div>


                    <?php if ($submitted): ?>

                        <div class="exercise-feedback">

                            <strong class="<?= $isCorrect ? 'feedback-good' : 'feedback-bad' ?>">

                                <?= $isCorrect
                                    ? clean($t["correct"])
                                    : clean($t["incorrect"]) ?>

                            </strong>


                            <?php if (!$isCorrect): ?>

                                <p class="correct-answer-text">

                                    <?= clean($t["right_answer"]) ?>

                                    <strong>

                                        <?= clean(
                                            $question["option" . $correctOption . "_" . $lang]
                                        ) ?>

                                    </strong>

                                </p>

                            <?php endif; ?>


                            <div class="exercise-explanation">

                                <strong>
                                    📘 <?= clean($t["explanation"]) ?>
                                </strong>

                                <p>
                                    <?= clean($explanation) ?>
                                </p>

                            </div>

                        </div>

                    <?php endif; ?>

                </div>

            <?php endforeach; ?>


            <?php if (!$submitted): ?>

                <div class="exercise-submit-area">

                    <button
                        type="submit"
                        class="exercise-submit-button"
                    >

                        <?= clean($t["submit"]) ?>

                        <span>→</span>

                    </button>

                </div>

            <?php else: ?>

                <div class="exercise-submit-area">

                    <a
                        class="exercise-retry-button"
                        href="take_exercise.php?id=<?= $exerciseId ?>&lang=<?= urlencode($lang) ?>"
                    >

                        ↻ <?= clean($t["retry"]) ?>

                    </a>

                </div>

            <?php endif; ?>

        </form>

    <?php endif; ?>

</main>


<footer class="login-footer">

    <p>
        © <?= date("Y") ?> Tajwid Academy
    </p>

</footer>

</body>
</html>
