
<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// Récupérer la langue
$lang = $_GET["lang"] ?? "fr";

if (!in_array($lang, ["fr", "ar", "en"])) {
    $lang = "fr";
}

// Récupérer l'identifiant du quiz
$quizId = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($quizId <= 0) {
    die("Quiz invalide.");
}

// Colonnes selon la langue
$titleColumn = "title_" . $lang;
$descriptionColumn = "description_" . $lang;
$questionColumn = "question_" . $lang;
$answerColumn = "answer_" . $lang;
$explanationColumn = "explanation_" . $lang;

// Traductions
$text = [
    "fr" => [
        "back" => "Retour aux quiz",
        "questions" => "Questions",
        "submit" => "Valider mes réponses",
        "choose" => "Choisis une réponse",
        "login" => "Tu dois être connecté pour passer un quiz.",
        "not_found" => "Quiz introuvable.",
        "no_questions" => "Aucune question disponible.",
        "result" => "Résultat du quiz",
        "correct" => "Bonnes réponses",
        "score" => "Ton score",
        "success" => "Félicitations ! Tu as réussi le quiz.",
        "failed" => "Continue à apprendre et réessaie !",
        "correct_answer" => "Bonne réponse",
        "your_answer" => "Ta réponse",
        "unanswered" => "Non répondue",
        "explanation" => "Explication",
        "retry" => "Réessayer",
        "error" => "Une erreur est survenue. Réessaie.",
        "csrf" => "Session expirée. Recharge la page."
    ],
    "ar" => [
        "back" => "العودة إلى الاختبارات",
        "questions" => "الأسئلة",
        "submit" => "تأكيد الإجابات",
        "choose" => "اختر إجابة",
        "login" => "يجب تسجيل الدخول لإجراء الاختبار.",
        "not_found" => "الاختبار غير موجود.",
        "no_questions" => "لا توجد أسئلة متاحة.",
        "result" => "نتيجة الاختبار",
        "correct" => "الإجابات الصحيحة",
        "score" => "نتيجتك",
        "success" => "تهانينا! لقد نجحت في الاختبار.",
        "failed" => "واصل التعلم وحاول مرة أخرى!",
        "correct_answer" => "الإجابة الصحيحة",
        "your_answer" => "إجابتك",
        "unanswered" => "بدون إجابة",
        "explanation" => "التفسير",
        "retry" => "إعادة المحاولة",
        "error" => "حدث خطأ. حاول مرة أخرى.",
        "csrf" => "انتهت الجلسة. أعد تحميل الصفحة."
    ],
    "en" => [
        "back" => "Back to quizzes",
        "questions" => "Questions",
        "submit" => "Submit my answers",
        "choose" => "Choose an answer",
        "login" => "You must be logged in to take a quiz.",
        "not_found" => "Quiz not found.",
        "no_questions" => "No questions available.",
        "result" => "Quiz result",
        "correct" => "Correct answers",
        "score" => "Your score",
        "success" => "Congratulations! You passed the quiz.",
        "failed" => "Keep learning and try again!",
        "correct_answer" => "Correct answer",
        "your_answer" => "Your answer",
        "unanswered" => "Not answered",
        "explanation" => "Explanation",
        "retry" => "Try again",
        "error" => "An error occurred. Please try again.",
        "csrf" => "Session expired. Reload the page."
    ]
];

$t = $text[$lang];

// Récupérer le quiz
$stmt = $pdo->prepare("
    SELECT id, $titleColumn AS title,
           $descriptionColumn AS description,
           passing_score
    FROM quizzes
    WHERE id = ?
");

$stmt->execute([$quizId]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quiz) {
    die(htmlspecialchars($t["not_found"]));
}

// Récupérer les questions
$stmt = $pdo->prepare("
    SELECT id,
           $questionColumn AS question,
           $explanationColumn AS explanation,
           points,
           position
    FROM questions
    WHERE quiz_id = ?
    ORDER BY position ASC, id ASC
");

$stmt->execute([$quizId]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($questions)) {
    die(htmlspecialchars($t["no_questions"]));
}

// Récupérer les réponses proposées
$answersByQuestion = [];

$stmtAnswers = $pdo->prepare("
    SELECT id, $answerColumn AS answer
    FROM answers
    WHERE question_id = ?
    ORDER BY id ASC
");

foreach ($questions as $question) {
    $stmtAnswers->execute([$question["id"]]);

    $answersByQuestion[$question["id"]] =
        $stmtAnswers->fetchAll(PDO::FETCH_ASSOC);
}

// Créer un jeton de sécurité
if (empty($_SESSION["quiz_token"])) {
    $_SESSION["quiz_token"] = bin2hex(random_bytes(32));
}

$error = "";

// Résultat après redirection
$result = null;

if (
    isset($_GET["result"]) &&
    $_GET["result"] == "1" &&
    isset($_SESSION["quiz_result"]) &&
    $_SESSION["quiz_result"]["quiz_id"] == $quizId
) {
    $result = $_SESSION["quiz_result"];
    unset($_SESSION["quiz_result"]);
}

// Traitement des réponses
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["quiz_token"], $_POST["csrf_token"])
    ) {
        $error = $t["csrf"];

    } else {

        try {

            $submittedAnswers = $_POST["answers"] ?? [];

            if (!is_array($submittedAnswers)) {
                $submittedAnswers = [];
            }

            $totalPoints = 0;
            $earnedPoints = 0;
            $correctAnswers = 0;
            $feedback = [];

            // Vérifier chaque question côté serveur
            foreach ($questions as $question) {

                $questionId = (int)$question["id"];
                $points = (int)$question["points"];

                if ($points < 0) {
                    $points = 0;
                }

                $totalPoints += $points;

                $selectedId = isset($submittedAnswers[$questionId])
                    ? (int)$submittedAnswers[$questionId]
                    : 0;

                $selectedAnswer = null;
                $correctAnswer = null;
                $isCorrect = false;

                // Vérifier que la réponse choisie appartient
                // bien à cette question et à ce quiz
                if ($selectedId > 0) {

                    $stmtCheck = $pdo->prepare("
                        SELECT a.id, a.$answerColumn AS answer,
                               a.is_correct
                        FROM answers a
                        INNER JOIN questions q
                            ON q.id = a.question_id
                        WHERE a.id = ?
                          AND q.id = ?
                          AND q.quiz_id = ?
                    ");

                    $stmtCheck->execute([
                        $selectedId,
                        $questionId,
                        $quizId
                    ]);

                    $selectedAnswer = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                }

                // Chercher la bonne réponse
                $stmtCorrect = $pdo->prepare("
                    SELECT $answerColumn AS answer
                    FROM answers
                    WHERE question_id = ?
                      AND is_correct = 1
                    LIMIT 1
                ");

                $stmtCorrect->execute([$questionId]);
                $correctAnswer = $stmtCorrect->fetchColumn();

                if (
                    $selectedAnswer &&
                    (int)$selectedAnswer["is_correct"] === 1
                ) {
                    $isCorrect = true;
                    $correctAnswers++;
                    $earnedPoints += $points;
                }

                $feedback[] = [
                    "question" => $question["question"],
                    "your_answer" => $selectedAnswer
                        ? $selectedAnswer["answer"]
                        : "",
                    "correct_answer" => $correctAnswer ?: "",
                    "is_correct" => $isCorrect,
                    "explanation" => $question["explanation"]
                ];
            }

            // Calculer le pourcentage
            $percentage = $totalPoints > 0
                ? round(($earnedPoints / $totalPoints) * 100, 2)
                : 0;

            // Enregistrer le résultat dans la base
            $stmtInsert = $pdo->prepare("
                INSERT INTO quiz_results
                (
                    user_id,
                    quiz_id,
                    score,
                    total_points,
                    correct_answers,
                    total_questions
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmtInsert->execute([
                $_SESSION["user_id"],
                $quizId,
                $percentage,
                $totalPoints,
                $correctAnswers,
                count($questions)
            ]);

            // Préparer l'affichage du résultat
            $_SESSION["quiz_result"] = [
                "quiz_id" => $quizId,
                "percentage" => $percentage,
                "earned_points" => $earnedPoints,
                "total_points" => $totalPoints,
                "correct_answers" => $correctAnswers,
                "total_questions" => count($questions),
                "feedback" => $feedback,
                "passed" => $percentage >= (int)$quiz["passing_score"]
            ];

            // Éviter de renvoyer le formulaire au rafraîchissement
            header(
                "Location: take_quiz.php?id=" . $quizId .
                "&lang=" . $lang . "&result=1"
            );
            exit;

        } catch (PDOException $e) {
            $error = $t["error"];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>"
      dir="<?= $lang === "ar" ? "rtl" : "ltr" ?>">

<head> <link rel="stylesheet" href="../assets/css/style.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($quiz["title"]) ?> - Tajwid Academy</title>

   
</head>

<body class="quiz-page">

<div class="container">

    <div class="heading">
        <h1><?= htmlspecialchars($quiz["title"]) ?></h1>
        <p><?= htmlspecialchars($quiz["description"] ?? "") ?></p>
        <strong>
            <?= count($questions) ?> <?= htmlspecialchars($t["questions"]) ?>
        </strong>
    </div>

    <?php if ($error): ?>
        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($result !== null): ?>

        <!-- Affichage du résultat -->

        <div class="result-card">

            <h2><?= htmlspecialchars($t["result"]) ?></h2>

            <div class="score">
                <?= number_format($result["percentage"], 0) ?>%
            </div>

            <p>
                <?= htmlspecialchars($t["correct"]) ?> :
                <?= (int)$result["correct_answers"] ?>
                /
                <?= (int)$result["total_questions"] ?>
            </p>

            <p>
                <?= (int)$result["earned_points"] ?>
                /
                <?= (int)$result["total_points"] ?>
                points
            </p>

            <h3>
                <?= $result["passed"]
                    ? htmlspecialchars($t["success"])
                    : htmlspecialchars($t["failed"]) ?>
            </h3>

        </div>

        <h2><?= htmlspecialchars($t["result"]) ?></h2>

        <?php foreach ($result["feedback"] as $index => $item): ?>

            <div class="feedback-card <?= $item["is_correct"] ? "good" : "bad" ?>">

                <h3>
                    <?= $index + 1 ?>.
                    <?= htmlspecialchars($item["question"]) ?>
                </h3>

                <p>
                    <strong><?= htmlspecialchars($t["your_answer"]) ?> :</strong>
                    <?= htmlspecialchars(
                        $item["your_answer"] !== ""
                            ? $item["your_answer"]
                            : $t["unanswered"]
                    ) ?>
                </p>

                <p>
                    <strong><?= htmlspecialchars($t["correct_answer"]) ?> :</strong>
                    <span class="correct-text">
                        <?= htmlspecialchars($item["correct_answer"]) ?>
                    </span>
                </p>

                <?php if ($item["explanation"]): ?>
                    <p>
                        <strong><?= htmlspecialchars($t["explanation"]) ?> :</strong>
                        <?= htmlspecialchars($item["explanation"]) ?>
                    </p>
                <?php endif; ?>

                <strong class="<?= $item["is_correct"] ? "correct-text" : "wrong-text" ?>">
                    <?= $item["is_correct"] ? "✓" : "✗" ?>
                </strong>

            </div>

        <?php endforeach; ?>

        <a class="btn"
           href="take_quiz.php?id=<?= $quizId ?>&lang=<?= $lang ?>">
            <?= htmlspecialchars($t["retry"]) ?>
        </a>

    <?php else: ?>

        <!-- Formulaire du quiz -->

        <form method="POST">

            <input type="hidden"
                   name="csrf_token"
                   value="<?= htmlspecialchars($_SESSION["quiz_token"]) ?>">

            <?php foreach ($questions as $index => $question): ?>

                <div class="question-card">

                    <h3>
                        <?= $index + 1 ?>.
                        <?= htmlspecialchars($question["question"]) ?>
                    </h3>

                    <?php foreach ($answersByQuestion[$question["id"]] as $answer): ?>

                        <label class="answer">

                            <input
                                type="radio"
                                name="answers[<?= (int)$question["id"] ?>]"
                                value="<?= (int)$answer["id"] ?>"
                            >

                            <?= htmlspecialchars($answer["answer"]) ?>

                        </label>

                    <?php endforeach; ?>

                </div>

            <?php endforeach; ?>

            <button type="submit" class="btn">
                <?= htmlspecialchars($t["submit"]) ?>
            </button>

        </form>

    <?php endif; ?>

    <a class="back" href="quizzes.php?lang=<?= $lang ?>">
        ← <?= htmlspecialchars($t["back"]) ?>
    </a>

</div>

</body>
</html>
