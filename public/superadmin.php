<?php
require_once __DIR__ . '/../includes/Auth.php';

$session = Auth::requireRole('superadmin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Superadmin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <nav class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <h1 class="text-xl font-semibold text-gray-900">Superadmin Dashboard</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-600">Welcome, <?php echo htmlspecialchars($session['username']); ?></span>
                        <button onclick="logout()" class="px-4 py-2 text-sm font-medium text-white bg-red-500 rounded-lg hover:bg-red-600">
                            Logout
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-6">
                <div class="border-b border-gray-200">
                    <nav class="flex space-x-8">
                        <button onclick="showTab('admins')" class="tab-button py-4 px-1 border-b-2 border-blue-500 font-medium text-sm text-blue-600">
                            Admin Management
                        </button>
                        <button onclick="showTab('tags')" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            Tags
                        </button>
                        <button onclick="showTab('system')" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            System Controls
                        </button>
                    </nav>
                </div>
            </div>

            <div id="admins-tab" class="tab-content">
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Create New Admin</h2>
                    <form id="create-admin-form" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                            <input type="text" name="username" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" name="email" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                            <input type="password" name="password" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tags (select multiple)</label>
                            <select name="tags" multiple class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" id="admin-tags-select">
                            </select>
                        </div>
                        <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 font-medium">
                            Create Admin
                        </button>
                    </form>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Admins List</h2>
                    <div id="admins-list" class="space-y-3"></div>
                </div>
            </div>

            <div id="tags-tab" class="tab-content hidden">
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Create New Tag</h2>
                    <form id="create-tag-form" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tag Name</label>
                            <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>
                        <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 font-medium">
                            Create Tag
                        </button>
                    </form>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Tags List</h2>
                    <div id="tags-list" class="space-y-3"></div>
                </div>
            </div>

            <div id="system-tab" class="tab-content hidden">
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">System Controls</h2>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div>
                                <h3 class="text-sm font-medium text-gray-900">Global System Toggle</h3>
                                <p class="text-sm text-gray-500">Enable or disable the entire redirect system</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="system-toggle" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            $('.tab-content').addClass('hidden');
            $('#' + tabName + '-tab').removeClass('hidden');
            
            $('.tab-button').removeClass('border-blue-500 text-blue-600').addClass('border-transparent text-gray-500');
            event.target.classList.remove('border-transparent', 'text-gray-500');
            event.target.classList.add('border-blue-500', 'text-blue-600');
        }

        function logout() {
            $.ajax({
                url: '/api/auth.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ action: 'logout' }),
                success: function() {
                    window.location.href = '/public/login.php';
                }
            });
        }

        function loadTags() {
            $.get('/api/tags.php', function(data) {
                $('#admin-tags-select').empty();
                data.tags.forEach(tag => {
                    $('#admin-tags-select').append(`<option value="${tag.id}">${tag.name}</option>`);
                });
                
                $('#tags-list').empty();
                data.tags.forEach(tag => {
                    $('#tags-list').append(`
                        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                            <div>
                                <h3 class="text-sm font-medium text-gray-900">${tag.name}</h3>
                                <p class="text-sm text-gray-500">${tag.description || 'No description'}</p>
                            </div>
                            <button onclick="deleteTag(${tag.id})" class="px-3 py-1 text-sm text-red-600 hover:bg-red-50 rounded">
                                Delete
                            </button>
                        </div>
                    `);
                });
            });
        }

        function loadAdmins() {
            $.get('/api/admins.php', function(data) {
                $('#admins-list').empty();
                data.admins.forEach(admin => {
                    $('#admins-list').append(`
                        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                            <div>
                                <h3 class="text-sm font-medium text-gray-900">${admin.username}</h3>
                                <p class="text-sm text-gray-500">${admin.email}</p>
                                <p class="text-xs text-gray-400">Tags: ${admin.tags || 'None'}</p>
                            </div>
                            <button onclick="deleteAdmin(${admin.id})" class="px-3 py-1 text-sm text-red-600 hover:bg-red-50 rounded">
                                Delete
                            </button>
                        </div>
                    `);
                });
            });
        }

        function deleteTag(id) {
            if (!confirm('Are you sure you want to delete this tag?')) return;
            
            $.ajax({
                url: '/api/tags.php',
                method: 'DELETE',
                contentType: 'application/json',
                data: JSON.stringify({ id: id }),
                success: function() {
                    loadTags();
                }
            });
        }

        function deleteAdmin(id) {
            if (!confirm('Are you sure you want to delete this admin?')) return;
            
            $.ajax({
                url: '/api/admins.php',
                method: 'DELETE',
                contentType: 'application/json',
                data: JSON.stringify({ id: id }),
                success: function() {
                    loadAdmins();
                }
            });
        }

        $('#create-tag-form').on('submit', function(e) {
            e.preventDefault();
            
            const formData = {
                name: $(this).find('[name="name"]').val(),
                description: $(this).find('[name="description"]').val()
            };
            
            $.ajax({
                url: '/api/tags.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(formData),
                success: function() {
                    $('#create-tag-form')[0].reset();
                    loadTags();
                    alert('Tag created successfully');
                },
                error: function(xhr) {
                    alert('Error: ' + (xhr.responseJSON?.error || 'Failed to create tag'));
                }
            });
        });

        $('#create-admin-form').on('submit', function(e) {
            e.preventDefault();
            
            const tags = $('#admin-tags-select').val() || [];
            
            const formData = {
                username: $(this).find('[name="username"]').val(),
                email: $(this).find('[name="email"]').val(),
                password: $(this).find('[name="password"]').val(),
                tags: tags.map(id => parseInt(id))
            };
            
            $.ajax({
                url: '/api/admins.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(formData),
                success: function() {
                    $('#create-admin-form')[0].reset();
                    loadAdmins();
                    alert('Admin created successfully');
                },
                error: function(xhr) {
                    alert('Error: ' + (xhr.responseJSON?.error || 'Failed to create admin'));
                }
            });
        });

        $(document).ready(function() {
            loadTags();
            loadAdmins();
        });
    </script>
</body>
</html>
