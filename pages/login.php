
<?php
session_start();

require_once __DIR__ . "/../config/database.php";

// ======================================
// 1. GESTION DE LA LANGUE
// ======================================

$lang = $_GET["lang"] ?? $_SESSION["lang"] ?? "fr";

if (!in_array($lang, ["fr", "ar", "en"], true)) {
    $lang = "fr";
}

$_SESSION["lang"] = $lang;

$direction = ($lang === "ar") ? "rtl" : "ltr";

// ======================================
// 2. TRADUCTIONS
// ======================================

$texts = [

    "fr" => [
        "title" => "Connexion",
        "welcome" => "Bienvenue !",
        "subtitle" => "Connectez-vous à votre compte Tajwid Academy.",
        "email" => "Adresse e-mail",
        "password" => "Mot de passe",
        "login" => "Se connecter",
        "no_account" => "Vous n'avez pas de compte ?",
        "register" => "Créer un compte",
        "error_empty" => "Veuillez remplir tous les champs.",
        "error_login" => "Adresse e-mail ou mot de passe incorrect.",
        "home" => "Accueil",
        "tagline" => "Apprenez le Coran et améliorez votre récitation"
    ],

    "en" => [
        "title" => "Login",
        "welcome" => "Welcome!",
        "subtitle" => "Log in to your Tajwid Academy account.",
        "email" => "Email address",
        "password" => "Password",
        "login" => "Log in",
        "no_account" => "Don't have an account?",
        "register" => "Create an account",
        "error_empty" => "Please fill in all fields.",
        "error_login" => "Incorrect email address or password.",
        "home" => "Home",
        "tagline" => "Learn the Quran and improve your recitation"
    ],

    "ar" => [
        "title" => "تسجيل الدخول",
        "welcome" => "مرحباً بك!",
        "subtitle" => "سجّل الدخول إلى حسابك في أكاديمية التجويد.",
        "email" => "البريد الإلكتروني",
        "password" => "كلمة المرور",
        "login" => "تسجيل الدخول",
        "no_account" => "ليس لديك حساب؟",
        "register" => "إنشاء حساب",
        "error_empty" => "يرجى ملء جميع الحقول.",
        "error_login" => "البريد الإلكتروني أو كلمة المرور غير صحيحة.",
        "home" => "الرئيسية",
        "tagline" => "تعلّم القرآن وحسّن تلاوتك"
    ]

];

$t = $texts[$lang];

// ======================================
// 3. TRAITEMENT DE LA CONNEXION
// ======================================

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {

        $error = $t["error_empty"];

    } else {

        // Rechercher l'utilisateur
        $stmt = $pdo->prepare(
            "SELECT * FROM users WHERE email = :email"
        );

        $stmt->execute(["email" => $email]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Vérifier le mot de passe
        if ($user && password_verify($password, $user["password"])) {

            session_regenerate_id(true);

            $_SESSION["user_id"] = $user["id"];
            $_SESSION["full_name"] = $user["full_name"];
            $_SESSION["email"] = $user["email"];
            $_SESSION["role"] = $user["role"];

            // Récupérer la langue préférée de l'utilisateur
            $preferredLanguage = $user["preferred_language"];

            if (!in_array($preferredLanguage, ["fr", "ar", "en"], true)) {
                $preferredLanguage = "fr";
            }

            $_SESSION["lang"] = $preferredLanguage;

            // Redirection vers l'accueil
            header("Location: ../index.php?lang=" . $preferredLanguage);
            exit;

        } else {

            $error = $t["error_login"];

        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>"
      dir="<?= $direction ?>">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($t["title"]) ?> - Tajwid Academy</title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body class="login-page">

    <!-- HEADER -->

    <header class="main-header">

        <div class="header-top">

            <div class="brand">

                <h1>تَعَلَّمِ التَّجْوِيدَ</h1>

                <h2>Tajwid Academy</h2>

                <p><?= htmlspecialchars($t["tagline"]) ?></p>

            </div>

            <div class="language-selector">

                <a href="?lang=fr" class="<?= $lang === 'fr' ? 'active' : '' ?>">FR</a>

                <a href="?lang=ar" class="<?= $lang === 'ar' ? 'active' : '' ?>">AR</a>

                <a href="?lang=en" class="<?= $lang === 'en' ? 'active' : '' ?>">EN</a>

            </div>

        </div>

        <nav class="main-nav">

            <a href="../index.php?lang=<?= $lang ?>">
                <?= htmlspecialchars($t["home"]) ?>
            </a>

        </nav>

    </header>


    <!-- LOGIN FORM -->

    <main class="login-container">

        <div class="login-card">

            <div class="login-icon">
                <span>📖</span>
            </div>

            <div class="login-heading">

                <span class="login-label">TAJWID ACADEMY</span>

                <h1><?= htmlspecialchars($t["welcome"]) ?></h1>

                <p><?= htmlspecialchars($t["subtitle"]) ?></p>

            </div>


            <?php if ($error !== ""): ?>

                <div class="login-error">

                    <span>⚠️</span>

                    <?= htmlspecialchars($error) ?>

                </div>

            <?php endif; ?>


            <form method="POST" class="login-form">

                <div class="login-field">

                    <label for="email">
                        <?= htmlspecialchars($t["email"]) ?>
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= htmlspecialchars($_POST["email"] ?? "", ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="example@email.com"
                        autocomplete="email"
                        required
                    >

                </div>


                <div class="login-field">

                    <label for="password">
                        <?= htmlspecialchars($t["password"]) ?>
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                        required
                    >

                </div>


                <button type="submit" class="login-button">

                    <span><?= htmlspecialchars($t["login"]) ?></span>

                    <span>→</span>

                </button>

            </form>


            <div class="login-register">

                <p><?= htmlspecialchars($t["no_account"]) ?></p>

                <a href="register.php?lang=<?= $lang ?>">
                    <?= htmlspecialchars($t["register"]) ?>
                </a>

            </div>

        </div>

        <div class="login-decoration">

            <span>✦</span>

            <p>بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ</p>

            <span>✦</span>

        </div>

    </main>


    <footer class="login-footer">

        <p>© <?= date("Y") ?> Tajwid Academy</p>

    </footer>

</body>

</html>
