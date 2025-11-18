<?php
require_once __DIR__ . '/../includes/Auth.php';

$session = Auth::getSession();
if ($session) {
    $redirects = [
        'superadmin' => '/public/superadmin.php',
        'admin' => '/public/admin.php',
        'user' => '/public/user.php'
    ];
    
    if (isset($redirects[$session['role']])) {
        header('Location: ' . $redirects[$session['role']]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Dashboard System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-bold text-gray-900">
                    Dashboard System
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Sign in to your account
                </p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-8">
                <form id="login-form" class="space-y-6">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
                            Username
                        </label>
                        <input id="username" name="username" type="text" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                            Password
                        </label>
                        <input id="password" name="password" type="password" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700 mb-1">
                            Role
                        </label>
                        <select id="role" name="role" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select role...</option>
                            <option value="superadmin">Superadmin</option>
                            <option value="admin">Admin</option>
                            <option value="user">User</option>
                        </select>
                    </div>

                    <div id="error-message" class="hidden p-3 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-sm text-red-600"></p>
                    </div>

                    <div>
                        <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Sign in
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $('#login-form').on('submit', function(e) {
            e.preventDefault();
            
            $('#error-message').addClass('hidden');
            
            const formData = {
                action: 'login',
                username: $('#username').val(),
                password: $('#password').val(),
                role: $('#role').val()
            };
            
            $.ajax({
                url: '/api/auth.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(formData),
                success: function(response) {
                    if (response.success) {
                        const redirects = {
                            'superadmin': '/public/superadmin.php',
                            'admin': '/public/admin.php',
                            'user': '/public/user.php'
                        };
                        
                        window.location.href = redirects[response.user.role];
                    }
                },
                error: function(xhr) {
                    const errorMsg = xhr.responseJSON?.error || 'Login failed';
                    $('#error-message p').text(errorMsg);
                    $('#error-message').removeClass('hidden');
                }
            });
        });
    </script>
</body>
</html>
