<?php
require_once __DIR__ . '/../includes/Auth.php';

$session = Auth::requireRole('admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
                        <h1 class="text-xl font-semibold text-gray-900">Admin Dashboard</h1>
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
                        <button onclick="showTab('users')" class="tab-button py-4 px-1 border-b-2 border-blue-500 font-medium text-sm text-blue-600">
                            User Management
                        </button>
                        <button onclick="showTab('domains')" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            Parked Domains
                        </button>
                        <button onclick="showTab('countries')" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            Countries
                        </button>
                        <button onclick="showTab('targets')" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            Target URLs
                        </button>
                    </nav>
                </div>
            </div>

            <div id="users-tab" class="tab-content">
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Create New User</h2>
                    <form id="create-user-form" class="space-y-4">
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
                        <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 font-medium">
                            Create User
                        </button>
                    </form>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Users List</h2>
                    <div id="users-list" class="space-y-3"></div>
                </div>
            </div>

            <div id="domains-tab" class="tab-content hidden">
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Add Parked Domains</h2>
                    <form id="add-domains-form" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Domains (one per line, max 10 total)</label>
                            <textarea name="domains" rows="5" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="example.com&#10;domain.net&#10;site.org"></textarea>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="cloudflare_sync" id="cloudflare-sync" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="cloudflare-sync" class="ml-2 block text-sm text-gray-700">
                                Sync with Cloudflare DNS
                            </label>
                        </div>
                        <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 font-medium">
                            Add Domains
                        </button>
                    </form>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">My Parked Domains</h2>
                    <div id="domains-list" class="space-y-3"></div>
                </div>
            </div>

            <div id="countries-tab" class="tab-content hidden">
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Manage Countries</h2>
                    <form id="countries-form" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Country Codes (ISO alpha-2, comma-separated)</label>
                            <textarea name="countries" rows="5" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="US, GB, CA, AU, DE"></textarea>
                            <p class="mt-1 text-sm text-gray-500">Leave empty to allow all countries</p>
                        </div>
                        <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 font-medium">
                            Save Countries
                        </button>
                    </form>
                    <div class="mt-6">
                        <h3 class="text-sm font-medium text-gray-900 mb-2">Current Countries:</h3>
                        <div id="countries-list" class="text-sm text-gray-600"></div>
                    </div>
                </div>
            </div>

            <div id="targets-tab" class="tab-content hidden">
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Add Target URL</h2>
                    <form id="add-target-form" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Target URL</label>
                            <input type="text" name="url" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="https://example.com/offer or https://{domain}/path">
                            <p class="mt-1 text-sm text-gray-500">Use {domain} placeholder for dynamic domain replacement</p>
                        </div>
                        <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 font-medium">
                            Add Target URL
                        </button>
                    </form>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">My Target URLs</h2>
                    <div id="targets-list" class="space-y-3"></div>
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
            
            if (tabName === 'domains') loadDomains();
            if (tabName === 'countries') loadCountries();
            if (tabName === 'targets') loadTargets();
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

        function loadUsers() {
            $.get('/api/users.php', function(data) {
                $('#users-list').empty();
                data.users.forEach(user => {
                    $('#users-list').append(`
                        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                            <div>
                                <h3 class="text-sm font-medium text-gray-900">${user.username}</h3>
                                <p class="text-sm text-gray-500">${user.email}</p>
                                <p class="text-xs text-gray-400">Token: ${user.token}</p>
                            </div>
                            <button onclick="deleteUser(${user.id})" class="px-3 py-1 text-sm text-red-600 hover:bg-red-50 rounded">
                                Delete
                            </button>
                        </div>
                    `);
                });
            });
        }

        function loadDomains() {
            $.get('/api/domains.php?action=admin_domains', function(data) {
                $('#domains-list').empty();
                data.domains.forEach(domain => {
                    $('#domains-list').append(`
                        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                            <div>
                                <h3 class="text-sm font-medium text-gray-900">${domain.domain}</h3>
                                <p class="text-xs text-gray-400">Cloudflare: ${domain.cloudflare_synced ? 'Synced' : 'Not synced'}</p>
                            </div>
                            <button onclick="deleteDomain(${domain.id})" class="px-3 py-1 text-sm text-red-600 hover:bg-red-50 rounded">
                                Delete
                            </button>
                        </div>
                    `);
                });
            });
        }

        function loadCountries() {
            $.get('/api/countries.php?action=admin_countries', function(data) {
                const codes = data.countries.map(c => c.iso_code).join(', ');
                $('#countries-list').text(codes || 'All countries allowed');
                $('[name="countries"]').val(codes);
            });
        }

        function loadTargets() {
            $.get('/api/target_urls.php?action=admin_target_urls', function(data) {
                $('#targets-list').empty();
                data.target_urls.forEach(target => {
                    $('#targets-list').append(`
                        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                            <div class="flex-1 mr-4">
                                <p class="text-sm text-gray-900 break-all">${target.url}</p>
                            </div>
                            <button onclick="deleteTarget(${target.id})" class="px-3 py-1 text-sm text-red-600 hover:bg-red-50 rounded">
                                Delete
                            </button>
                        </div>
                    `);
                });
            });
        }

        function deleteUser(id) {
            if (!confirm('Are you sure?')) return;
            $.ajax({
                url: '/api/users.php',
                method: 'DELETE',
                contentType: 'application/json',
                data: JSON.stringify({ id: id }),
                success: function() { loadUsers(); }
            });
        }

        function deleteDomain(id) {
            if (!confirm('Are you sure?')) return;
            $.ajax({
                url: '/api/domains.php',
                method: 'DELETE',
                contentType: 'application/json',
                data: JSON.stringify({ action: 'delete_admin_domain', id: id }),
                success: function() { loadDomains(); }
            });
        }

        function deleteTarget(id) {
            if (!confirm('Are you sure?')) return;
            $.ajax({
                url: '/api/target_urls.php',
                method: 'DELETE',
                contentType: 'application/json',
                data: JSON.stringify({ action: 'delete_admin_target_url', id: id }),
                success: function() { loadTargets(); }
            });
        }

        $('#create-user-form').on('submit', function(e) {
            e.preventDefault();
            const formData = {
                username: $(this).find('[name="username"]').val(),
                email: $(this).find('[name="email"]').val(),
                password: $(this).find('[name="password"]').val()
            };
            
            $.ajax({
                url: '/api/users.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(formData),
                success: function(response) {
                    $('#create-user-form')[0].reset();
                    loadUsers();
                    alert('User created! Token: ' + response.token);
                },
                error: function(xhr) {
                    alert('Error: ' + (xhr.responseJSON?.error || 'Failed'));
                }
            });
        });

        $('#add-domains-form').on('submit', function(e) {
            e.preventDefault();
            const formData = {
                action: 'add_admin_domains',
                domains: $(this).find('[name="domains"]').val(),
                cloudflare_sync: $(this).find('[name="cloudflare_sync"]').is(':checked')
            };
            
            $.ajax({
                url: '/api/domains.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(formData),
                success: function() {
                    $('#add-domains-form')[0].reset();
                    loadDomains();
                    alert('Domains added successfully');
                },
                error: function(xhr) {
                    alert('Error: ' + (xhr.responseJSON?.error || 'Failed'));
                }
            });
        });

        $('#countries-form').on('submit', function(e) {
            e.preventDefault();
            const formData = {
                action: 'set_admin_countries',
                countries: $(this).find('[name="countries"]').val()
            };
            
            $.ajax({
                url: '/api/countries.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(formData),
                success: function() {
                    loadCountries();
                    alert('Countries saved successfully');
                },
                error: function(xhr) {
                    alert('Error: ' + (xhr.responseJSON?.error || 'Failed'));
                }
            });
        });

        $('#add-target-form').on('submit', function(e) {
            e.preventDefault();
            const formData = {
                action: 'add_admin_target_url',
                url: $(this).find('[name="url"]').val()
            };
            
            $.ajax({
                url: '/api/target_urls.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(formData),
                success: function() {
                    $('#add-target-form')[0].reset();
                    loadTargets();
                    alert('Target URL added successfully');
                },
                error: function(xhr) {
                    alert('Error: ' + (xhr.responseJSON?.error || 'Failed'));
                }
            });
        });

        $(document).ready(function() {
            loadUsers();
        });
    </script>
</body>
</html>
