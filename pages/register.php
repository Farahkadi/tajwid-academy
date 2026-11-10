
<?php
session_start();

require_once __DIR__ . "/../config/database.php";

$error = "";
$success = "";

// Langue sélectionnée
$lang = $_GET["lang"] ?? $_SESSION["lang"] ?? "fr";

if (!in_array($lang, ["fr", "ar", "en"], true)) {
    $lang = "fr";
}

$direction = ($lang === "ar") ? "rtl" : "ltr";

// ======================================
// TRADUCTIONS
// ======================================

$texts = [

    "fr" => [
        "title" => "Créer un compte",
        "subtitle" => "Rejoins Tajwid Academy et commence ton apprentissage.",
        "name" => "Nom complet",
        "email" => "Adresse e-mail",
        "password" => "Mot de passe",
        "language" => "Langue préférée",
        "submit" => "Créer mon compte",
        "already" => "Tu as déjà un compte ?",
        "login" => "Se connecter",
        "home" => "Accueil",
        "welcome" => "Apprends le Coran avec sérénité",
        "required" => "Veuillez remplir tous les champs.",
        "invalid_email" => "Adresse e-mail invalide.",
        "short_password" => "Le mot de passe doit contenir au moins 8 caractères.",
        "invalid_language" => "Langue invalide.",
        "existing_email" => "Cette adresse e-mail est déjà utilisée.",
        "success" => "Votre compte a été créé avec succès !",
        "success_login" => "Connectez-vous ici.",
        "français" => "Français",
        "arabic" => "العربية",
        "english" => "English"
    ],

    "en" => [
        "title" => "Create an account",
        "subtitle" => "Join Tajwid Academy and start learning.",
        "name" => "Full name",
        "email" => "Email address",
        "password" => "Password",
        "language" => "Preferred language",
        "submit" => "Create my account",
        "already" => "Already have an account?",
        "login" => "Log in",
        "home" => "Home",
        "welcome" => "Learn the Quran with peace and serenity",
        "required" => "Please fill in all fields.",
        "invalid_email" => "Invalid email address.",
        "short_password" => "Password must contain at least 8 characters.",
        "invalid_language" => "Invalid language.",
        "existing_email" => "This email address is already in use.",
        "success" => "Your account has been created successfully!",
        "success_login" => "Log in here.",
        "français" => "French",
        "arabic" => "Arabic",
        "english" => "English"
    ],

    "ar" => [
        "title" => "إنشاء حساب",
        "subtitle" => "انضم إلى أكاديمية التجويد وابدأ رحلتك التعليمية.",
        "name" => "الاسم الكامل",
        "email" => "البريد الإلكتروني",
        "password" => "كلمة المرور",
        "language" => "اللغة المفضلة",
        "submit" => "إنشاء حسابي",
        "already" => "لديك حساب بالفعل؟",
        "login" => "تسجيل الدخول",
        "home" => "الرئيسية",
        "welcome" => "تعلم القرآن الكريم بكل طمأنينة",
        "required" => "يرجى ملء جميع الحقول.",
        "invalid_email" => "البريد الإلكتروني غير صالح.",
        "short_password" => "يجب أن تتكون كلمة المرور من 8 أحرف على الأقل.",
        "invalid_language" => "اللغة غير صالحة.",
        "existing_email" => "هذا البريد الإلكتروني مستخدم بالفعل.",
        "success" => "تم إنشاء حسابك بنجاح!",
        "success_login" => "سجل الدخول من هنا.",
        "français" => "الفرنسية",
        "arabic" => "العربية",
        "english" => "الإنجليزية"
    ]
];

$t = $texts[$lang];

// ======================================
// TRAITEMENT DU FORMULAIRE
// ======================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $fullName = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $language = $_POST["preferred_language"] ?? "fr";

    if ($fullName === "" || $email === "" || $password === "") {

        $error = $t["required"];

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = $t["invalid_email"];

    } elseif (strlen($password) < 8) {

        $error = $t["short_password"];

    } elseif (!in_array($language, ["fr", "ar", "en"], true)) {

        $error = $t["invalid_language"];

    } else {

        // Vérifier si l'e-mail existe déjà
        $stmt = $pdo->prepare(
            "SELECT id FROM users WHERE email = :email"
        );

        $stmt->execute([
            "email" => $email
        ]);

        if ($stmt->fetch()) {

            $error = $t["existing_email"];

        } else {

            // Protéger le mot de passe
            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // Enregistrer le nouvel étudiant
            $stmt = $pdo->prepare(
                "INSERT INTO users
                (full_name, email, password, role, preferred_language)
                VALUES
                (:full_name, :email, :password, 'student', :language)"
            );

            $stmt->execute([
                "full_name" => $fullName,
                "email" => $email,
                "password" => $hashedPassword,
                "language" => $language
            ]);

            $success = $t["success"];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>"
      dir="<?= $direction ?>">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        <?= htmlspecialchars($t["title"]) ?> - Tajwid Academy
    </title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body class="register-page">

<!-- ======================================
     HEADER
====================================== -->

<header class="main-header">

    <div class="header-top">

        <div class="brand">

            <h1>تَعَلَّمِ التَّجْوِيدَ</h1>

            <h2>Tajwid Academy</h2>

            <p><?= htmlspecialchars($t["welcome"]) ?></p>

        </div>

        <div class="language-selector">

            <a href="register.php?lang=fr"
               class="<?= $lang === 'fr' ? 'active' : '' ?>">
                FR
            </a>

            <a href="register.php?lang=ar"
               class="<?= $lang === 'ar' ? 'active' : '' ?>">
                AR
            </a>

            <a href="register.php?lang=en"
               class="<?= $lang === 'en' ? 'active' : '' ?>">
                EN
            </a>

        </div>

    </div>

    <nav class="main-nav">

        <a href="../index.php?lang=<?= urlencode($lang) ?>">
            🏠 <?= htmlspecialchars($t["home"]) ?>
        </a>

    </nav>

</header>

<!-- ======================================
     FORMULAIRE D'INSCRIPTION
====================================== -->

<main class="register-container">

    <section class="register-card">

        <div class="register-icon">
            ✦
        </div>

        <div class="register-heading">

            <span class="register-label">
                TAJWID ACADEMY
            </span>

            <h1>
                <?= htmlspecialchars($t["title"]) ?>
            </h1>

            <p>
                <?= htmlspecialchars($t["subtitle"]) ?>
            </p>

        </div>

        <!-- Messages -->

        <?php if ($error): ?>

            <div class="register-error">
                ⚠️ <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>

        <?php endif; ?>

        <?php if ($success): ?>

            <div class="register-success">

                <p>
                    ✓ <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
                </p>

                <a href="login.php?lang=<?= urlencode($lang) ?>">
                    <?= htmlspecialchars($t["success_login"]) ?>
                </a>

            </div>

        <?php else: ?>

            <!-- Formulaire -->

            <form method="POST" class="register-form">

                <div class="register-field">

                    <label for="full_name">
                        <?= htmlspecialchars($t["name"]) ?>
                    </label>

                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        value="<?= htmlspecialchars(
                            $_POST["full_name"] ?? "",
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        placeholder="<?= htmlspecialchars($t["name"]) ?>"
                        autocomplete="name"
                        required
                    >

                </div>

                <div class="register-field">

                    <label for="email">
                        <?= htmlspecialchars($t["email"]) ?>
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= htmlspecialchars(
                            $_POST["email"] ?? "",
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        placeholder="example@email.com"
                        autocomplete="email"
                        required
                    >

                </div>

                <div class="register-field">

                    <label for="password">
                        <?= htmlspecialchars($t["password"]) ?>
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >

                    <small>
                        <?= htmlspecialchars($t["short_password"]) ?>
                    </small>

                </div>

                <div class="register-field">

                    <label for="preferred_language">
                        <?= htmlspecialchars($t["language"]) ?>
                    </label>

                    <select
                        id="preferred_language"
                        name="preferred_language"
                        required
                    >

                        <option value="fr"
                            <?= ($_POST["preferred_language"] ?? "fr") === "fr" ? "selected" : "" ?>>
                            <?= htmlspecialchars($t["français"]) ?>
                        </option>

                        <option value="ar"
                            <?= ($_POST["preferred_language"] ?? "fr") === "ar" ? "selected" : "" ?>>
                            <?= htmlspecialchars($t["arabic"]) ?>
                        </option>

                        <option value="en"
                            <?= ($_POST["preferred_language"] ?? "fr") === "en" ? "selected" : "" ?>>
                            <?= htmlspecialchars($t["english"]) ?>
                        </option>

                    </select>

                </div>

                <button type="submit" class="register-button">

                    <?= htmlspecialchars($t["submit"]) ?>

                    <span>→</span>

                </button>

            </form>

        <?php endif; ?>

        <div class="register-login">

            <?= htmlspecialchars($t["already"]) ?>

            <a href="login.php?lang=<?= urlencode($lang) ?>">
                <?= htmlspecialchars($t["login"]) ?>
            </a>

        </div>

    </section>

</main>

<footer class="main-footer">

    <p>
        © <?= date("Y") ?> Tajwid Academy
        — Learn with knowledge and serenity.
    </p>

</footer>

</body>
</html>
