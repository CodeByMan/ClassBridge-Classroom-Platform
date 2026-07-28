<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <x-favicon-links />
    <title>ClassBridge setup required</title>
    <style>
        :root { color-scheme: light; font-family: Arial, Helvetica, sans-serif; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; background: #f8f9fa; color: #202124; }
        main { width: min(720px, 100%); background: #fff; border: 1px solid #dadce0; border-radius: 16px; padding: 32px; box-shadow: 0 8px 28px rgba(60,64,67,.14); }
        h1 { margin: 0 0 12px; font-size: clamp(26px, 5vw, 38px); }
        p { color: #5f6368; line-height: 1.65; }
        code { display: block; overflow-x: auto; margin-top: 18px; padding: 16px; border-radius: 10px; background: #202124; color: #e8eaed; white-space: pre-wrap; }
        a { color: #1a73e8; font-weight: 600; }
    </style>
</head>
<body>
<main>
    <h1>ClassBridge database setup is required</h1>
    <p>
        The application code is available, but the configured database does not contain the required Laravel tables yet.
        Start MySQL in XAMPP, stop the development server, and run the project setup script from PowerShell.
    </p>
    <code>PowerShell -ExecutionPolicy Bypass -File .\scripts\setup-local.ps1</code>
    <p>
        The script creates the database, runs migrations, seeds the teacher/student demo accounts, rebuilds frontend assets,
        and verifies the automated test suite. After it completes, return to <a href="/login">the login page</a>.
    </p>
</main>
</body>
</html>
