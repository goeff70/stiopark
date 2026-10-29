<?php

session_start();

$errors = [
  'login' => $_SESSION['login_error'] ?? '',
  'register' => $_SESSION['register_error'] ?? ''
];
$activateForm = $_SESSION['activate_form'] ?? 'login';

session_unset();

function showError($error) {
  return !empty($error) ? "<p class='form-error'>$error</p>" : '';
}

function isActiveForm($formname, $activateForm) {
  return $formname === $activateForm ? 'active' : '';
}

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login Form | Ludiflex</title>
    <link rel="stylesheet" href="./style.css" />
  </head>
  <body>
    <div class="container">
      <div class="form-container">
        <div class="form-blob">
          <img
            src="../assets/blue blob.svg.webp"
            alt="blob"
            class="blob-image blob-image--1"
          />
          <img
            src="../assets/yellow blob.svg.webp"
            alt="blob"
            class="blob-image blob-image--2"
          />
          <img
            src="../assets/blue blob.svg.webp"
            alt="blob"
            class="blob-image blob-image--3"
          />
        </div>

        <div class="form-header">
          <p>Please enter your details</p>
          <h1>Welcome Back</h1>
        </div>
        <div class="form-box <?= isActiveForm('login', $activateForm) ?>" id="login-form">
          <form action="../login_register.php" method="post">
          <?= showError($errors['login']) ?>
          <div class="input-group">
            <input
              type="text"
              id="plate_number"
              name="plate_number"
              class="input-field"
              placeholder=""
              maxlength="20"
              autocomplete="off"
              required
            />
            <label for="plate_number" class="floating-label">Plate number</label>
          </div>
          <div class="input-group">
            <input
              type="email"
              id="email"
              name="email"
              class="input-field"
              placeholder=""
              autocomplete="email"
              autocapitalize="none"
              spellcheck="false"
              required
            />
            <label for="email" class="floating-label">Email address</label>
          </div>

          <div class="input-group">
            <input
              type="password"
              id="password"
              name="password"
              class="input-field"
              placeholder=""
              required
            />
            <label for="password" class="floating-label">Password</label>
            <div class="eye-icon" onclick="togglePassword()">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.5"
                stroke-linecap="round"
                stroke-linejoin="round"
                class="lucide lucide-eye-icon lucide-eye"
              >
                <path
                  d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 .696 10.75 10.75 0 0 1-19.876 0"
                />
                <circle cx="12" cy="12" r="3" />
              </svg>
            </div>
          </div>

          <div class="input-group checkbox-group">
            <div class="form-col remember-me">
              <input type="checkbox" id="remember-me-checkbox" class="checkbox-field" />
              <label for="remember-me-checkbox">Remember me</label>
            </div>
            <div class="form-col">
              <a href="#" class="form-link">Forgot password?</a>
            </div>
          </div>

          <button type="submit" name="login" class="form-btn form-btn--submit">Sign In</button>
        </form>

        <div class="form-divider">
          <p>Or</p>
        </div>

        <div class="form-bottom">
          <div class="form-socials">
            <a class="form-btn form-btn--social" data-provider="google" href="../oauth_start.php?provider=google&amp;flow=login" aria-label="Continue with Google">
              <img src="../assets/google.svg.webp" class="btn-icon" alt="Google" />
            </a>
            <a class="form-btn form-btn--social" data-provider="facebook" href="../oauth_start.php?provider=facebook&amp;flow=login" aria-label="Continue with Facebook">
              <img
                src="../assets/fb.svg.webp"
                class="btn-icon"
                alt="Facebook"
              />
            </a>
            <a class="form-btn form-btn--social" data-provider="sti" href="../oauth_start.php?provider=sti&amp;flow=login" aria-label="Continue with STI ELMS">
              <img src="../assets/sti.svg.webp" class="btn-icon" alt="STI ELMS" />
            </a>
          </div>
          <p>Don't have an account? <a href="../signup/index.php" class="form-link">Sign up</a></p>
        </div>
      </div>
    </div>

    <script>
      document.querySelectorAll(".form-socials a[data-provider]").forEach((link) => {
        link.addEventListener("click", (event) => {
          const plateNumber = document.getElementById("plate_number");
          if (!plateNumber.reportValidity()) {
            event.preventDefault();
            return;
          }

          event.preventDefault();
          const form = document.createElement("form");
          form.method = "post";
          form.action = link.href;
          const plateField = document.createElement("input");
          plateField.type = "hidden";
          plateField.name = "plate_number";
          plateField.value = plateNumber.value;
          form.appendChild(plateField);
          document.body.appendChild(form);
          form.submit();
        });
      });

      function togglePassword() {
        const passwordInput = document.getElementById("password");
        const eyeIcon = document.querySelector(".eye-icon");

        if (!passwordInput || !eyeIcon) return;

        if (passwordInput.type === "password") {
          passwordInput.type = "text";
          eyeIcon.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-eye-off-icon lucide-eye-off"><path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"/><path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"/><path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"/><path d="m2 2 20 20"/></svg>`;
        } else {
          passwordInput.type = "password";
          eyeIcon.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-eye-icon lucide-eye"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>`;
        }
      }
    </script>
  </body>
</html>