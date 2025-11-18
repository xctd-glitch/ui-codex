<?php
require_once __DIR__ . '/../includes/Auth.php';

$session = Auth::requireRole('user');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
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
                        <h1 class="text-xl font-semibold text-gray-900">User Dashboard</h1>
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
                        <button onclick="showTab('rules')" class="tab-button py-4 px-1 border-b-2 border-blue-500 font-medium text-sm text-blue-600">
                            Redirect Rules
                        </button>
                        <button onclick="showTab('domains')" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            Domains
                        </button>
                        <button onclick="showTab('targets')" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            Target URLs
                        </button>
                        <button onclick="showTab('countries')" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            Countries
                        </button>
                        <button onclick="showTab('config')" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            Configuration
                        </button>
                        <button onclick="showTab('metrics')" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            Metrics
                        </button>
                    </nav>
                </div>
            </div>

            <div id="rules-tab" class="tab-content">
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Create Redirect Rule</h2>
                    <form id="create-rule-form" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Rule Name</label>
                            <input type="text" name="rule_name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Rule Type</label>
                            <select name="rule_type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="toggleRuleFields(this.value)">
                                <option value="">Select type...</option>
                                <option value="mute_unmute">Mute/Unmute Cycle</option>
                                <option value="random_route">Random Route</option>
                                <option value="static_route">Static Route</option>
                            </select>
                        </div>
                        <div id="static-url-field" style="display:none;">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Target URL</label>
                            <input type="text" name="target_url" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="https://example.com or https://{domain}/path">
                        </div>
                        <div id="mute-fields" style="display:none;">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Active Duration (seconds)</label>
                                    <input type="number" name="mute_duration_on" value="120" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Inactive Duration (seconds)</label>
                                    <input type="number" name="mute_duration_off" value="300" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Priority (higher = evaluated first)</label>
                            <input type="number" name="priority" value="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 font-medium">
                            Create Rule
                        </button>
                    </form>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">My Rules</h2>
                    <div id="rules-list" class="space-y-3"></div>
                </div>
            </div>

            <div id="domains-tab" class="tab-content hidden">
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Add Parked Domains</h2>
                    <form id="add-domains-form" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Domains (one per line, max 10 total)</label>
                            <textarea name="domains" rows="5" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="example.com&#10;domain.net"></textarea>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="cloudflare_sync" id="user-cloudflare-sync" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="user-cloudflare-sync" class="ml-2 block text-sm text-gray-700">
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

            <div id="config-tab" class="tab-content hidden">
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Configuration</h2>
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Device Scope</label>
                            <select id="device-scope" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="ALL">All Devices</option>
                                <option value="WAP">Mobile/Tablet Only</option>
                                <option value="WEB">Desktop Only</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Domain Selection Strategy</label>
                            <select id="selection-type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="toggleSpecificDomain(this.value)">
                                <option value="random_user">Random from My Domains</option>
                                <option value="random_global">Random from Admin Domains</option>
                                <option value="specific">Specific Domain</option>
                            </select>
                        </div>
                        <div id="specific-domain-field" style="display:none;">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Specific Domain</label>
                            <input type="text" id="specific-domain" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="example.com">
                        </div>
                        <button onclick="saveConfig()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 font-medium">
                            Save Configuration
                        </button>
                    </div>
                </div>
            </div>

            <div id="metrics-tab" class="tab-content hidden">
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Date Range</h2>
                    <div class="flex space-x-4">
                        <button onclick="loadMetrics('today')" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 font-medium">Today</button>
                        <button onclick="loadMetrics('yesterday')" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-medium">Yesterday</button>
                        <button onclick="loadMetrics('weekly')" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-medium">Last 7 Days</button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-sm font-medium text-gray-500 mb-2">Total Clicks</h3>
                        <p class="text-3xl font-bold text-gray-900" id="total-clicks">0</p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-sm font-medium text-gray-500 mb-2">Redirects</h3>
                        <p class="text-3xl font-bold text-blue-600" id="redirect-count">0</p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-sm font-medium text-gray-500 mb-2">Conversions</h3>
                        <p class="text-3xl font-bold text-green-600" id="conversion-count">0</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-sm font-medium text-gray-900 mb-4">Top Countries</h3>
                        <div id="country-stats" class="space-y-2"></div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-sm font-medium text-gray-900 mb-4">Device Stats</h3>
                        <div id="device-stats" class="space-y-2"></div>
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
            
            if (tabName === 'rules') loadRules();
            if (tabName === 'domains') loadDomains();
            if (tabName === 'targets') loadTargets();
            if (tabName === 'countries') loadCountries();
            if (tabName === 'metrics') loadMetrics('today');
        }

        function toggleRuleFields(type) {
            $('#static-url-field').hide();
            $('#mute-fields').hide();
            if (type === 'static_route') $('#static-url-field').show();
            if (type === 'mute_unmute') $('#mute-fields').show();
        }

        function toggleSpecificDomain(type) {
            $('#specific-domain-field').toggle(type === 'specific');
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

        function loadRules() {
            $.get('/api/rules.php', function(data) {
                $('#rules-list').empty();
                data.rules.forEach(rule => {
                    const statusBadge = rule.is_active ? '<span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">Active</span>' : '<span class="text-xs bg-gray-100 text-gray-800 px-2 py-1 rounded">Inactive</span>';
                    $('#rules-list').append(`
                        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                            <div>
                                <h3 class="text-sm font-medium text-gray-900">${rule.rule_name} ${statusBadge}</h3>
                                <p class="text-xs text-gray-500">Type: ${rule.rule_type} | Priority: ${rule.priority}</p>
                            </div>
                            <button onclick="deleteRule(${rule.id})" class="px-3 py-1 text-sm text-red-600 hover:bg-red-50 rounded">Delete</button>
                        </div>
                    `);
                });
            });
        }

        function loadDomains() {
            $.get('/api/domains.php?action=user_domains', function(data) {
                $('#domains-list').empty();
                data.domains.forEach(domain => {
                    $('#domains-list').append(`
                        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                            <div>
                                <h3 class="text-sm font-medium text-gray-900">${domain.domain}</h3>
                            </div>
                            <button onclick="deleteDomain(${domain.id})" class="px-3 py-1 text-sm text-red-600 hover:bg-red-50 rounded">Delete</button>
                        </div>
                    `);
                });
            });
        }

        function loadTargets() {
            $.get('/api/target_urls.php?action=user_target_urls', function(data) {
                $('#targets-list').empty();
                data.target_urls.forEach(target => {
                    $('#targets-list').append(`
                        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                            <div class="flex-1 mr-4">
                                <p class="text-sm text-gray-900 break-all">${target.url}</p>
                            </div>
                            <button onclick="deleteTarget(${target.id})" class="px-3 py-1 text-sm text-red-600 hover:bg-red-50 rounded">Delete</button>
                        </div>
                    `);
                });
            });
        }

        function loadCountries() {
            $.get('/api/countries.php?action=user_countries', function(data) {
                const codes = data.countries.map(c => c.iso_code).join(', ');
                $('#countries-list').text(codes || 'All countries allowed');
                $('[name="countries"]').val(codes);
            });
        }

        function loadMetrics(range) {
            $.get('/api/metrics.php?date_range=' + range, function(data) {
                $('#total-clicks').text(data.total_clicks);
                $('#redirect-count').text(data.redirect_count);
                $('#conversion-count').text(data.conversion_count);
                
                $('#country-stats').empty();
                data.country_stats.forEach(stat => {
                    $('#country-stats').append(`
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">${stat.country_iso}</span>
                            <span class="font-medium text-gray-900">${stat.count}</span>
                        </div>
                    `);
                });
                
                $('#device-stats').empty();
                data.device_stats.forEach(stat => {
                    $('#device-stats').append(`
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">${stat.device_type}</span>
                            <span class="font-medium text-gray-900">${stat.count}</span>
                        </div>
                    `);
                });
            });
        }

        function deleteRule(id) {
            if (!confirm('Are you sure?')) return;
            $.ajax({
                url: '/api/rules.php',
                method: 'DELETE',
                contentType: 'application/json',
                data: JSON.stringify({ id: id }),
                success: function() { loadRules(); }
            });
        }

        function deleteDomain(id) {
            if (!confirm('Are you sure?')) return;
            $.ajax({
                url: '/api/domains.php',
                method: 'DELETE',
                contentType: 'application/json',
                data: JSON.stringify({ action: 'delete_user_domain', id: id }),
                success: function() { loadDomains(); }
            });
        }

        function deleteTarget(id) {
            if (!confirm('Are you sure?')) return;
            $.ajax({
                url: '/api/target_urls.php',
                method: 'DELETE',
                contentType: 'application/json',
                data: JSON.stringify({ action: 'delete_user_target_url', id: id }),
                success: function() { loadTargets(); }
            });
        }

        $('#create-rule-form').on('submit', function(e) {
            e.preventDefault();
            const formData = {
                rule_name: $(this).find('[name="rule_name"]').val(),
                rule_type: $(this).find('[name="rule_type"]').val(),
                target_url: $(this).find('[name="target_url"]').val() || null,
                mute_duration_on: parseInt($(this).find('[name="mute_duration_on"]').val()) || 120,
                mute_duration_off: parseInt($(this).find('[name="mute_duration_off"]').val()) || 300,
                priority: parseInt($(this).find('[name="priority"]').val()) || 0
            };
            
            $.ajax({
                url: '/api/rules.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(formData),
                success: function() {
                    $('#create-rule-form')[0].reset();
                    loadRules();
                    alert('Rule created successfully');
                },
                error: function(xhr) {
                    alert('Error: ' + (xhr.responseJSON?.error || 'Failed'));
                }
            });
        });

        $('#add-domains-form').on('submit', function(e) {
            e.preventDefault();
            const formData = {
                action: 'add_user_domains',
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

        $('#add-target-form').on('submit', function(e) {
            e.preventDefault();
            const formData = {
                action: 'add_user_target_url',
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

        $('#countries-form').on('submit', function(e) {
            e.preventDefault();
            const formData = {
                action: 'set_user_countries',
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

        $(document).ready(function() {
            loadRules();
        });
    </script>
</body>
</html>
