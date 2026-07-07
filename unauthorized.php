<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Access Denied</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="text-center">
        <h1 class="text-4xl font-semibold text-gray-800 mb-3">Access Denied</h1>
        <p class="text-gray-500 mb-6">You do not have permission to view this page.</p>
        <a href="index.html" class="px-6 py-3 bg-gray-800 text-white rounded-md text-sm hover:bg-gray-700">
            Back to Login
        </a>
    </div>
</body>
</html>