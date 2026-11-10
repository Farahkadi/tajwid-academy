
<?php
session_start();


// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
require_once __DIR__ . "/../config/api.php";
require_once __DIR__ . "/../config/database.php";

// Langue sélectionnée
$lang = $_GET["lang"] ?? $_SESSION["lang"] ?? "fr";

if (!in_array($lang, ["fr", "ar", "en"])) {
    $lang = "fr";
}

$_SESSION["lang"] = $lang;

// Traductions
$texts = [
    "fr" => [
        "back" => "← Retour aux sourates",
        "invalid" => "Numéro de sourate invalide.",
        "error" => "Une erreur est survenue.",
        "unavailable" => "Les versets ne sont pas disponibles.",
        "verses" => "Nombre de versets :",
        "verse" => "Verset",
        "add" => "🤍 Ajouter aux favoris",
        "remove" => "❤️ Retirer des favoris",
        "login" => "🔐 Connecte-toi pour ajouter ce verset aux favoris.",
        "audio_error" => "Votre navigateur ne supporte pas l'audio.",
        "invalid_request" => "Requête invalide."
    ],
    "en" => [
        "back" => "← Back to surahs",
        "invalid" => "Invalid surah number.",
        "error" => "An error occurred.",
        "unavailable" => "The verses are not available.",
        "verses" => "Number of verses:",
        "verse" => "Verse",
        "add" => "🤍 Add to favorites",
        "remove" => "❤️ Remove from favorites",
        "login" => "🔐 Log in to add this verse to your favorites.",
        "audio_error" => "Your browser does not support audio.",
        "invalid_request" => "Invalid request."
    ],
    "ar" => [
        "back" => "← العودة إلى السور",
        "invalid" => "رقم السورة غير صحيح.",
        "error" => "حدث خطأ.",
        "unavailable" => "الآيات غير متوفرة.",
        "verses" => "عدد الآيات:",
        "verse" => "الآية",
        "add" => "🤍 أضف إلى المفضلة",
        "remove" => "❤️ إزالة من المفضلة",
        "login" => "🔐 سجل الدخول لإضافة هذه الآية إلى المفضلة.",
        "audio_error" => "متصفحك لا يدعم تشغيل الصوت.",
        "invalid_request" => "طلب غير صالح."
    ]
];

$t = $texts[$lang];

// Numéro de sourate
$number = isset($_GET["number"]) ? (int)$_GET["number"] : 0;

if ($number < 1 || $number > 114) {
    die(safeText($t["invalid"]));
}

// Vérifier la connexion
$userId = isset($_SESSION["user_id"])
    ? (int)$_SESSION["user_id"]
    : null;

// Protection CSRF
if (empty($_SESSION["csrf_favorites"])) {
    $_SESSION["csrf_favorites"] = bin2hex(random_bytes(32));
}

// Ajouter ou retirer un favori
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!$userId) {
        header("Location: login.php?lang=" . urlencode($lang));
        exit;
    }

    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_favorites"], $_POST["csrf_token"])
    ) {
        die(safeText($t["invalid_request"]));
    }

    $ayahNumber = (int)($_POST["ayah_number"] ?? 0);

    if ($ayahNumber > 0) {

        $check = $pdo->prepare("
            SELECT id
            FROM favorites
            WHERE user_id = :user_id
            AND surah_number = :surah_number
            AND ayah_number = :ayah_number
        ");

        $check->execute([
            "user_id" => $userId,
            "surah_number" => $number,
            "ayah_number" => $ayahNumber
        ]);

        $favorite = $check->fetch(PDO::FETCH_ASSOC);

        if ($favorite) {

            $delete = $pdo->prepare("
                DELETE FROM favorites
                WHERE id = :id AND user_id = :user_id
            ");

            $delete->execute([
                "id" => $favorite["id"],
                "user_id" => $userId
            ]);

        } else {

            $insert = $pdo->prepare("
                INSERT INTO favorites
                (user_id, surah_number, ayah_number)
                VALUES
                (:user_id, :surah_number, :ayah_number)
            ");

            $insert->execute([
                "user_id" => $userId,
                "surah_number" => $number,
                "ayah_number" => $ayahNumber
            ]);
        }
    }

    // Garder la langue après l'action
    header("Location: surah.php?number=" . $number . "&lang=" . urlencode($lang));
    exit;
}


// Choisir la traduction selon la langue
$translation = ($lang === "en")
    ? "sahih_international"
    : "french";

// Appel de UmmahAPI
$result = callUmmahAPI(
    "/api/quran/surah/" . $number .
    "?script=uthmani&translation=" . $translation
);
$surah = [];
$verses = [];

if ($result["success"]) {
    $data = $result["data"];
    $surah = $data["surah"] ?? [];
    $verses = $data["verses"] ?? [];
}

// Récupérer les favoris
$favoriteNumbers = [];

if ($userId) {

    $stmt = $pdo->prepare("
        SELECT ayah_number
        FROM favorites
        WHERE user_id = :user_id
        AND surah_number = :surah_number
    ");

    $stmt->execute([
        "user_id" => $userId,
        "surah_number" => $number
    ]);

    $favoriteNumbers = array_map(
        "intval",
        $stmt->fetchAll(PDO::FETCH_COLUMN)
    );
}

function safeText($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$direction = ($lang === "ar") ? "rtl" : "ltr";
?>

<!DOCTYPE html>
<html lang="<?= safeText($lang) ?>" dir="<?= $direction ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= safeText($surah["name_english"] ?? "Surah") ?> - Tajwid Academy
    </title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .favorite-form {
            margin-top: 15px;
        }

        .favorite-button {
            background: #fff0f0;
            color: #c0392b;
            border: 1px solid #f1caca;
            padding: 10px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
        }

        .favorite-button:hover {
            background: #ffe0e0;
        }

        .favorite-button.active {
            background: #176b50;
            color: white;
            border-color: #176b50;
        }

        .login-favorite {
            display: inline-block;
            margin-top: 15px;
            color: #176b50;
        }

        .language-selector {
            margin: 15px 0;
            text-align: center;
        }

        .language-selector a {
            margin: 0 8px;
            text-decoration: none;
        }
    </style>
</head>

<body>

<header class="main-header">

    <div class="header-top">

        <div class="brand">
            <h1>تَعَلَّمِ التَّجْوِيدَ</h1>
            <h2>Tajwid Academy</h2>
            <p><?= safeText($surah["name_english"] ?? "Quran") ?></p>
        </div>

        <div class="language-selector">
            <a href="surah.php?number=<?= $number ?>&lang=fr">FR</a>
            <a href="surah.php?number=<?= $number ?>&lang=ar">AR</a>
            <a href="surah.php?number=<?= $number ?>&lang=en">EN</a>
        </div>

    </div>

    <nav class="main-nav">

        <a href="../index.php?lang=<?= safeText($lang) ?>">
            <?= $lang === "ar" ? "الرئيسية" : ($lang === "en" ? "Home" : "Accueil") ?>
        </a>

        <a href="quran.php?lang=<?= safeText($lang) ?>">
            <?= safeText($t["back"]) ?>
        </a>

    </nav>

</header>

<main class="container">

    <?php if (!$result["success"]): ?>

        <p class="error">
            <?= safeText($result["error"] ?? $t["error"]) ?>
        </p>

    <?php elseif (empty($verses)): ?>

        <p><?= safeText($t["unavailable"]) ?></p>

    <?php else: ?>

        <section class="surah-heading">

            <h2>
                <?= safeText($surah["name_arabic"] ?? "") ?>
            </h2>

            <h3>
                <?= safeText($surah["name_english"] ?? "") ?>
            </h3>

           <?php if ($lang === "en" && !empty($surah["name_translation"])): ?>
    <p>
        <?= safeText($surah["name_translation"]) ?>
    </p>
<?php endif; ?>
            <p>
                <?= safeText($t["verses"]) ?>
                <?= safeText($surah["verses_count"] ?? count($verses)) ?>
            </p>

        </section>

        <?php foreach ($verses as $verse): ?>

            <?php
            $ayahNumber = (int)($verse["ayah"] ?? 0);
            $isFavorite = in_array($ayahNumber, $favoriteNumbers, true);
            ?>

            <article class="ayah-card">

                <div class="ayah-number">
                    <?= safeText($t["verse"]) ?>
                    <?= safeText($ayahNumber) ?>
                </div>

                <p class="arabic-text" dir="rtl">
                    <?= safeText($verse["arabic"] ?? "") ?>
                </p>

                <?php if ($lang !== "ar" && !empty($verse["translation"])): ?>
    <p class="translation">
        <?= safeText($verse["translation"]) ?>
    </p>
<?php endif; ?>

                <p class="transliteration">
                    <?= safeText($verse["transliteration"] ?? "") ?>
                </p>

                <?php if (!empty($verse["audio"]["ayah_audio"])): ?>

                    <audio controls>
                        <source
                            src="<?= safeText($verse["audio"]["ayah_audio"]) ?>"
                            type="audio/mpeg">
                        <?= safeText($t["audio_error"]) ?>
                    </audio>

                <?php endif; ?>

                <?php if ($userId): ?>

                    <form method="POST" class="favorite-form">

                        <input
                            type="hidden"
                            name="ayah_number"
                            value="<?= $ayahNumber ?>">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= safeText($_SESSION["csrf_favorites"]) ?>">

                        <button
                            type="submit"
                            class="favorite-button <?= $isFavorite ? 'active' : '' ?>">

                            <?= safeText($isFavorite ? $t["remove"] : $t["add"]) ?>

                        </button>

                    </form>

                <?php else: ?>

                    <a
                        href="login.php?lang=<?= safeText($lang) ?>"
                        class="login-favorite">
                        <?= safeText($t["login"]) ?>
                    </a>

                <?php endif; ?>

            </article>

        <?php endforeach; ?>

    <?php endif; ?>

</main>

</body>
</html>
