<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

// Helper function to render markdown beautifully
function renderMarkdown($filePath, $title) {
        $content = File::get($filePath);
        $lines = explode("\n", $content);
        
        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . '</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        .header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .header h1 {
            color: #333;
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        .back-link {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .back-link:hover {
            background: #5568d3;
        }
        .content {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            line-height: 1.8;
        }
        .content h1 {
            color: #333;
            font-size: 2.2em;
            margin: 30px 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 3px solid #667eea;
        }
        .content h2 {
            color: #667eea;
            font-size: 1.8em;
            margin: 25px 0 15px 0;
            padding-top: 20px;
        }
        .content h3 {
            color: #555;
            font-size: 1.4em;
            margin: 20px 0 10px 0;
        }
        .content h4 {
            color: #666;
            font-size: 1.2em;
            margin: 15px 0 8px 0;
        }
        .content p {
            color: #333;
            margin: 15px 0;
        }
        .content code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: "Courier New", monospace;
            font-size: 0.9em;
            color: #e83e8c;
        }
        .content pre {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 20px;
            border-radius: 5px;
            overflow-x: auto;
            margin: 20px 0;
            font-family: "Courier New", monospace;
            font-size: 0.9em;
            line-height: 1.6;
        }
        .content pre code {
            background: transparent;
            color: inherit;
            padding: 0;
        }
        .content table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
        }
        .content table th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        .content table td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
        }
        .content table tr:hover {
            background: #f8f9fa;
        }
        .content ul, .content ol {
            margin: 15px 0 15px 30px;
            color: #333;
        }
        .content li {
            margin: 8px 0;
        }
        .content blockquote {
            border-left: 4px solid #667eea;
            padding-left: 20px;
            margin: 20px 0;
            color: #666;
            font-style: italic;
        }
        .endpoint-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 5px;
            font-weight: bold;
            margin-right: 10px;
            font-size: 0.9em;
        }
        .method-post { background: #007bff; color: white; }
        .method-get { background: #28a745; color: white; }
        .method-put { background: #ffc107; color: #333; }
        .method-delete { background: #dc3545; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>' . htmlspecialchars($title) . '</h1>
            <a href="' . route('docs.index') . '" class="back-link">← Back to Documentation</a>
        </div>
        <div class="content">';
        
        $inCodeBlock = false;
        $codeLanguage = '';
        $codeContent = '';
        
        foreach ($lines as $line) {
            // Handle code blocks
            if (preg_match('/^```(\w+)?$/', $line, $matches)) {
                if ($inCodeBlock) {
                    $html .= '<pre><code class="language-' . htmlspecialchars($codeLanguage) . '">' . htmlspecialchars($codeContent) . '</code></pre>';
                    $codeContent = '';
                    $codeLanguage = '';
                    $inCodeBlock = false;
                } else {
                    $inCodeBlock = true;
                    $codeLanguage = $matches[1] ?? '';
                }
                continue;
            }
            
            if ($inCodeBlock) {
                $codeContent .= $line . "\n";
                continue;
            }
            
            // Headers
            if (preg_match('/^#\s+(.+)$/', $line, $matches)) {
                $html .= '<h1>' . htmlspecialchars($matches[1]) . '</h1>';
            } elseif (preg_match('/^##\s+(.+)$/', $line, $matches)) {
                $html .= '<h2>' . htmlspecialchars($matches[1]) . '</h2>';
            } elseif (preg_match('/^###\s+(.+)$/', $line, $matches)) {
                $html .= '<h3>' . htmlspecialchars($matches[1]) . '</h3>';
            } elseif (preg_match('/^####\s+(.+)$/', $line, $matches)) {
                $html .= '<h4>' . htmlspecialchars($matches[1]) . '</h4>';
            }
            // Endpoint with method
            elseif (preg_match('/^`(GET|POST|PUT|DELETE)\s+(.+?)`$/', $line, $matches)) {
                $method = strtolower($matches[1]);
                $url = $matches[2];
                $html .= '<p><span class="endpoint-badge method-' . $method . '">' . $matches[1] . '</span><code>' . htmlspecialchars($url) . '</code></p>';
            }
            // Inline code
            elseif (preg_match('/`(.+?)`/', $line)) {
                $line = preg_replace('/`(.+?)`/', '<code>$1</code>', $line);
                $html .= '<p>' . $line . '</p>';
            }
            // Tables
            elseif (preg_match('/^\|(.+)\|$/', $line)) {
                if (!isset($inTable)) {
                    $inTable = true;
                    $html .= '<table>';
                }
                $cells = explode('|', trim($line, '|'));
                $html .= '<tr>';
                foreach ($cells as $cell) {
                    $cell = trim($cell);
                    if (preg_match('/^[-:]+$/', $cell)) {
                        continue; // Skip separator row
                    }
                    $tag = (isset($isHeaderRow) && $isHeaderRow) ? 'th' : 'td';
                    $html .= '<' . $tag . '>' . htmlspecialchars($cell) . '</' . $tag . '>';
                }
                $html .= '</tr>';
                $isHeaderRow = false;
            }
            // Empty line ends table
            elseif (empty(trim($line)) && isset($inTable)) {
                $html .= '</table>';
                unset($inTable);
                unset($isHeaderRow);
            }
            // Regular paragraphs
            elseif (!empty(trim($line))) {
                if (!isset($inTable)) {
                    $html .= '<p>' . nl2br(htmlspecialchars($line)) . '</p>';
                }
            }
        }
        
        if (isset($inTable)) {
            $html .= '</table>';
        }
        
        $html .= '</div></div></body></html>';
        
        return $html;
}

Route::get('/', function () {
    return view('welcome');
});

// Admin Routes
Route::prefix('admin')->group(function () {
    // Admin Authentication Routes (Public)
    Route::get('/login', [\App\Http\Controllers\Admin\AuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [\App\Http\Controllers\Admin\AuthController::class, 'login'])->name('admin.login.post');
    
    // Admin Protected Routes
    Route::middleware('auth:admin')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('admin.dashboard');
        Route::post('/logout', [\App\Http\Controllers\Admin\AuthController::class, 'logout'])->name('admin.logout');
        
        // Management Routes
        Route::get('/users', [\App\Http\Controllers\Admin\ManagementController::class, 'users'])->name('admin.users');
        Route::get('/users/{user}', [\App\Http\Controllers\Admin\ManagementController::class, 'userDetail'])->name('admin.users.detail');
        Route::get('/dealers', [\App\Http\Controllers\Admin\ManagementController::class, 'dealers'])->name('admin.dealers');
        Route::get('/dealers/{dealer}', [\App\Http\Controllers\Admin\ManagementController::class, 'dealerDetail'])->name('admin.dealers.detail');
        Route::get('/brands', [\App\Http\Controllers\Admin\ManagementController::class, 'brands'])->name('admin.brands');
        Route::get('/brands/{brand}', [\App\Http\Controllers\Admin\ManagementController::class, 'brandDetail'])->name('admin.brands.detail');
        Route::get('/brands/{brand}/edit', [\App\Http\Controllers\Admin\ManagementController::class, 'brandEdit'])->name('admin.brands.edit');
        Route::put('/brands/{brand}', [\App\Http\Controllers\Admin\ManagementController::class, 'brandUpdate'])->name('admin.brands.update');
        Route::delete('/brands/{brand}', [\App\Http\Controllers\Admin\ManagementController::class, 'brandDelete'])->name('admin.brands.delete');
        Route::get('/converters', [\App\Http\Controllers\Admin\ManagementController::class, 'converters'])->name('admin.converters');
        Route::get('/machine-dealers', [\App\Http\Controllers\Admin\ManagementController::class, 'machineDealers'])->name('admin.machine-dealers');
        
        // Inquiry Routes
        Route::get('/inquiries', [\App\Http\Controllers\Admin\ManagementController::class, 'inquiries'])->name('admin.inquiries');
        Route::get('/inquiries/material', [\App\Http\Controllers\Admin\ManagementController::class, 'materialInquiries'])->name('admin.inquiries.material');
        Route::get('/inquiries/machine', [\App\Http\Controllers\Admin\ManagementController::class, 'machineInquiries'])->name('admin.inquiries.machine');
        Route::get('/inquiries/job', [\App\Http\Controllers\Admin\ManagementController::class, 'jobInquiries'])->name('admin.inquiries.job');
        Route::get('/inquiries/{inquiry}', [\App\Http\Controllers\Admin\ManagementController::class, 'inquiryDetail'])->name('admin.inquiries.detail');
        
        // Session Routes
        Route::get('/sessions/active', [\App\Http\Controllers\Admin\ManagementController::class, 'activeSessions'])->name('admin.sessions.active');
        Route::get('/sessions/completed', [\App\Http\Controllers\Admin\ManagementController::class, 'completedSessions'])->name('admin.sessions.completed');
        Route::get('/sessions/all', [\App\Http\Controllers\Admin\ManagementController::class, 'allSessions'])->name('admin.sessions.all');
        
        // Reference Data Routes - Materials
        Route::get('/reference/materials', [\App\Http\Controllers\Admin\ManagementController::class, 'materials'])->name('admin.reference.materials');
        Route::post('/reference/materials', [\App\Http\Controllers\Admin\ManagementController::class, 'storeMaterial'])->name('admin.reference.materials.store');
        Route::put('/reference/materials/{material}', [\App\Http\Controllers\Admin\ManagementController::class, 'updateMaterial'])->name('admin.reference.materials.update');
        Route::delete('/reference/materials/{material}', [\App\Http\Controllers\Admin\ManagementController::class, 'deleteMaterial'])->name('admin.reference.materials.delete');
        
        // Reference Data Routes - Machines
        Route::get('/reference/machines', [\App\Http\Controllers\Admin\ManagementController::class, 'machines'])->name('admin.reference.machines');
        Route::post('/reference/machines', [\App\Http\Controllers\Admin\ManagementController::class, 'storeMachine'])->name('admin.reference.machines.store');
        Route::put('/reference/machines/{machine}', [\App\Http\Controllers\Admin\ManagementController::class, 'updateMachine'])->name('admin.reference.machines.update');
        Route::delete('/reference/machines/{machine}', [\App\Http\Controllers\Admin\ManagementController::class, 'deleteMachine'])->name('admin.reference.machines.delete');
        
        // Reference Data Routes - Brands
        Route::get('/reference/brands', [\App\Http\Controllers\Admin\ManagementController::class, 'referenceBrands'])->name('admin.reference.brands');
        Route::post('/reference/brands', [\App\Http\Controllers\Admin\ManagementController::class, 'storeReferenceBrand'])->name('admin.reference.brands.store');
        Route::put('/reference/brands/{brand}', [\App\Http\Controllers\Admin\ManagementController::class, 'updateReferenceBrand'])->name('admin.reference.brands.update');
        Route::delete('/reference/brands/{brand}', [\App\Http\Controllers\Admin\ManagementController::class, 'deleteReferenceBrand'])->name('admin.reference.brands.delete');
        
        // Reference Data Routes - Finishes
        Route::get('/reference/finishes', [\App\Http\Controllers\Admin\ManagementController::class, 'finishes'])->name('admin.reference.finishes');
        Route::post('/reference/finishes', [\App\Http\Controllers\Admin\ManagementController::class, 'storeFinish'])->name('admin.reference.finishes.store');
        Route::put('/reference/finishes/{finish}', [\App\Http\Controllers\Admin\ManagementController::class, 'updateFinish'])->name('admin.reference.finishes.update');
        Route::delete('/reference/finishes/{finish}', [\App\Http\Controllers\Admin\ManagementController::class, 'deleteFinish'])->name('admin.reference.finishes.delete');

        // CMS Management Routes
        Route::get('/cms/terms', [\App\Http\Controllers\Admin\ManagementController::class, 'terms'])->name('admin.cms.terms');
        Route::post('/cms/terms', [\App\Http\Controllers\Admin\ManagementController::class, 'storeTerms'])->name('admin.cms.terms.store');
        Route::put('/cms/terms/{id}', [\App\Http\Controllers\Admin\ManagementController::class, 'updateTerms'])->name('admin.cms.terms.update');

        Route::get('/cms/privacy', [\App\Http\Controllers\Admin\ManagementController::class, 'privacy'])->name('admin.cms.privacy');
        Route::post('/cms/privacy', [\App\Http\Controllers\Admin\ManagementController::class, 'storePrivacy'])->name('admin.cms.privacy.store');
        Route::put('/cms/privacy/{id}', [\App\Http\Controllers\Admin\ManagementController::class, 'updatePrivacy'])->name('admin.cms.privacy.update');

        Route::get('/cms/faq', [\App\Http\Controllers\Admin\ManagementController::class, 'faq'])->name('admin.cms.faq');
        Route::post('/cms/faq', [\App\Http\Controllers\Admin\ManagementController::class, 'storeFaq'])->name('admin.cms.faq.store');
        Route::put('/cms/faq/{id}', [\App\Http\Controllers\Admin\ManagementController::class, 'updateFaq'])->name('admin.cms.faq.update');
        Route::delete('/cms/faq/{id}', [\App\Http\Controllers\Admin\ManagementController::class, 'deleteFaq'])->name('admin.cms.faq.delete');

        Route::get('/cms/corporate', [\App\Http\Controllers\Admin\ManagementController::class, 'corporate'])->name('admin.cms.corporate');
        Route::post('/cms/corporate', [\App\Http\Controllers\Admin\ManagementController::class, 'storeCorporate'])->name('admin.cms.corporate.store');
        Route::put('/cms/corporate/{id}', [\App\Http\Controllers\Admin\ManagementController::class, 'updateCorporate'])->name('admin.cms.corporate.update');
        Route::delete('/cms/corporate/{id}', [\App\Http\Controllers\Admin\ManagementController::class, 'deleteCorporate'])->name('admin.cms.corporate.delete');
    });
});

// API Documentation Routes
Route::prefix('docs')->group(function () {
    Route::get('/routes', function () {
        $file = base_path('API_ROUTES.md');
        if (!File::exists($file)) {
            return response('Documentation file not found', 404);
        }
        return renderMarkdown($file, 'API Routes Documentation');
    })->name('docs.routes');

    Route::get('/request-formats', function () {
        $file = base_path('API_REQUEST_FORMATS.md');
        if (!File::exists($file)) {
            return response('Documentation file not found', 404);
        }

        $content = File::get($file);
        
        // Parse markdown sections
        $sections = [];
        $lines = explode("\n", $content);
        $currentSection = null;
        $currentCode = null;
        $inCodeBlock = false;
        
        foreach ($lines as $line) {
            // Check for headers
            if (preg_match('/^##\s+(.+)$/', $line, $matches)) {
                if ($currentSection) {
                    $sections[] = $currentSection;
                }
                $currentSection = [
                    'title' => $matches[1],
                    'content' => [],
                    'codeBlocks' => []
                ];
                $inCodeBlock = false;
            }
            // Check for code blocks
            elseif (preg_match('/^```(json|javascript|php)?$/', $line, $matches)) {
                if ($inCodeBlock) {
                    if ($currentCode) {
                        if ($currentSection) {
                            $currentSection['codeBlocks'][] = $currentCode;
                        }
                        $currentCode = null;
                    }
                    $inCodeBlock = false;
                } else {
                    $inCodeBlock = true;
                    $currentCode = ['language' => $matches[1] ?? 'json', 'code' => ''];
                }
            }
            // Check for field descriptions (table rows)
            elseif (preg_match('/^\|\s*(.+?)\s*\|\s*(.+?)\s*\|\s*(.+?)\s*\|\s*(.+?)\s*\|$/', $line, $matches)) {
                if ($currentSection && !$inCodeBlock) {
                    if (!isset($currentSection['fields'])) {
                        $currentSection['fields'] = [];
                    }
                    // Skip header row
                    if (strtolower($matches[1]) !== 'field') {
                        $currentSection['fields'][] = [
                            'field' => trim($matches[1]),
                            'type' => trim($matches[2]),
                            'required' => trim($matches[3]),
                            'description' => trim($matches[4])
                        ];
                    }
                }
            }
            // Regular content
            else {
                if ($inCodeBlock && $currentCode) {
                    $currentCode['code'] .= $line . "\n";
                } elseif ($currentSection && !$inCodeBlock) {
                    $trimmed = trim($line);
                    if (!empty($trimmed) && !preg_match('/^[-=]+$/', $trimmed)) {
                        $currentSection['content'][] = $line;
                    }
                }
            }
        }
        
        if ($currentSection) {
            $sections[] = $currentSection;
        }

        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Request Formats Documentation</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        .header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .header h1 {
            color: #333;
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        .header p {
            color: #666;
            font-size: 1.1em;
        }
        .section {
            background: white;
            border-radius: 10px;
            margin-bottom: 30px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .section-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 25px;
            font-size: 1.8em;
            font-weight: bold;
        }
        .section-content {
            padding: 25px;
        }
        .endpoint-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        .endpoint-method {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 5px;
            font-weight: bold;
            margin-right: 10px;
            font-size: 0.9em;
        }
        .method-post { background: #007bff; color: white; }
        .method-get { background: #28a745; color: white; }
        .endpoint-url {
            font-family: "Courier New", monospace;
            font-size: 1.1em;
            color: #333;
            font-weight: 500;
        }
        .code-block {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            overflow-x: auto;
            font-family: "Courier New", monospace;
            font-size: 0.9em;
            line-height: 1.6;
        }
        .code-block pre {
            margin: 0;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .fields-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
        }
        .fields-table th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        .fields-table td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
        }
        .fields-table tr:hover {
            background: #f8f9fa;
        }
        .required-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8em;
            font-weight: bold;
        }
        .required-yes {
            background: #dc3545;
            color: white;
        }
        .required-no {
            background: #6c757d;
            color: white;
        }
        .required-conditional {
            background: #ffc107;
            color: #333;
        }
        .text-content {
            color: #333;
            line-height: 1.8;
            margin: 15px 0;
        }
        .text-content p {
            margin: 10px 0;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .back-link:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📝 API Request Formats</h1>
            <p>Complete request and response examples for all API endpoints</p>
            <a href="' . route('docs.index') . '" class="back-link">← Back to Documentation</a>
        </div>';

        foreach ($sections as $section) {
            $html .= '<div class="section">
                <div class="section-header">' . htmlspecialchars($section['title']) . '</div>
                <div class="section-content">';
            
            // Display endpoint info if found in content
            $endpointFound = false;
            foreach ($section['content'] as $line) {
                if (preg_match('/###\s+Endpoint/i', $line)) {
                    $endpointFound = true;
                } elseif ($endpointFound && preg_match('/`(.+?)`/', $line, $matches)) {
                    $endpoint = $matches[1];
                    $method = 'POST';
                    if (strpos($endpoint, 'GET') !== false) {
                        $method = 'GET';
                    } elseif (strpos($endpoint, 'POST') !== false) {
                        $method = 'POST';
                    }
                    $html .= '<div class="endpoint-info">
                        <span class="endpoint-method method-' . strtolower($method) . '">' . $method . '</span>
                        <span class="endpoint-url">' . htmlspecialchars($endpoint) . '</span>
                    </div>';
                    $endpointFound = false;
                }
            }
            
            // Display code blocks
            foreach ($section['codeBlocks'] as $codeBlock) {
                $html .= '<div class="code-block">
                    <pre>' . htmlspecialchars($codeBlock['code']) . '</pre>
                </div>';
            }
            
            // Display fields table
            if (isset($section['fields']) && !empty($section['fields'])) {
                $html .= '<table class="fields-table">
                    <thead>
                        <tr>
                            <th>Field</th>
                            <th>Type</th>
                            <th>Required</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>';
                
                foreach ($section['fields'] as $field) {
                    $requiredClass = 'required-no';
                    $requiredText = $field['required'];
                    if (stripos($field['required'], 'yes') !== false || stripos($field['required'], 'required') !== false) {
                        $requiredClass = 'required-yes';
                    } elseif (stripos($field['required'], 'conditional') !== false) {
                        $requiredClass = 'required-conditional';
                    }
                    
                    $html .= '<tr>
                        <td><strong>' . htmlspecialchars($field['field']) . '</strong></td>
                        <td><code>' . htmlspecialchars($field['type']) . '</code></td>
                        <td><span class="required-badge ' . $requiredClass . '">' . htmlspecialchars($requiredText) . '</span></td>
                        <td>' . htmlspecialchars($field['description']) . '</td>
                    </tr>';
                }
                
                $html .= '</tbody></table>';
            }
            
            // Display text content
            $textContent = implode("\n", array_filter($section['content'], function($line) {
                return !preg_match('/^###|^```|^\|/', trim($line));
            }));
            
            if (!empty(trim($textContent))) {
                $html .= '<div class="text-content">' . nl2br(htmlspecialchars($textContent)) . '</div>';
            }
            
            $html .= '</div></div>';
        }

        $html .= '</div>
    </body>
</html>';

        return $html;
    })->name('docs.request-formats');

    Route::get('/routes-documentation', function () {
        $file = base_path('API_ROUTES_DOCUMENTATION.md');
        if (!File::exists($file)) {
            return response('Documentation file not found', 404);
        }
        return renderMarkdown($file, 'API Routes Documentation');
    })->name('docs.routes-documentation');

    Route::get('/complete', function () {
        $file = base_path('COMPLETE_API_DOCUMENTATION.md');
        if (!File::exists($file)) {
            return response('Documentation file not found', 404);
        }
        return renderMarkdown($file, 'Complete API Documentation');
    })->name('docs.complete');

    Route::get('/dealer', function () {
        $file = base_path('DEALER_API_DOCUMENTATION.md');
        if (!File::exists($file)) {
            return response('Documentation file not found', 404);
        }
        return renderMarkdown($file, 'Dealer API Documentation');
    })->name('docs.dealer');

    Route::get('/wallet', function () {
        $file = base_path('WALLET_API_DOCUMENTATION.md');
        if (!File::exists($file)) {
            return response('Documentation file not found', 404);
        }
        return renderMarkdown($file, 'Wallet & Payment API Documentation');
    })->name('docs.wallet');

    // API Routes with Beautiful Design
    Route::get('/api-routes', function () {
        $routes = [
            [
                'category' => 'Authentication',
                'routes' => [
                    ['method' => 'POST', 'endpoint' => '/api/v1/auth/otp/request', 'auth' => false, 'desc' => 'Request OTP for login'],
                    ['method' => 'POST', 'endpoint' => '/api/v1/auth/otp/verify', 'auth' => false, 'desc' => 'Verify OTP and get token'],
                ]
            ],
            [
                'category' => 'User Profile',
                'routes' => [
                    ['method' => 'GET', 'endpoint' => '/api/v1/user/profile', 'auth' => true, 'desc' => 'Get user profile'],
                    ['method' => 'POST', 'endpoint' => '/api/v1/user/profile', 'auth' => true, 'desc' => 'Update user profile'],
                    ['method' => 'POST', 'endpoint' => '/api/v1/user/switch-role', 'auth' => true, 'desc' => 'Switch user role'],
                ]
            ],
            [
                'category' => 'Reference Data',
                'routes' => [
                    ['method' => 'GET', 'endpoint' => '/api/v1/materials', 'auth' => false, 'desc' => 'Get materials list'],
                    ['method' => 'GET', 'endpoint' => '/api/v1/machines', 'auth' => false, 'desc' => 'Get machines list'],
                    ['method' => 'GET', 'endpoint' => '/api/v1/material-finishes', 'auth' => false, 'desc' => 'Get material finishes'],
                    ['method' => 'GET', 'endpoint' => '/api/v1/material-mills', 'auth' => false, 'desc' => 'Get mills for material'],
                    ['method' => 'GET', 'endpoint' => '/api/v1/brands', 'auth' => false, 'desc' => 'Get mill brands'],
                    ['method' => 'GET', 'endpoint' => '/api/v1/materials/{id}/details', 'auth' => false, 'desc' => 'Get material details'],
                ]
            ],
            [
                'category' => 'Dealer APIs',
                'routes' => [
                    ['method' => 'POST', 'endpoint' => '/api/v1/dealer/mill/add', 'auth' => true, 'desc' => 'Add mill/brand manually'],
                    ['method' => 'POST', 'endpoint' => '/api/v1/dealer/finish/add', 'auth' => true, 'desc' => 'Add finish manually'],
                    ['method' => 'POST', 'endpoint' => '/api/v1/dealer/profile/complete', 'auth' => true, 'desc' => 'Complete dealer profile'],
                    ['method' => 'GET', 'endpoint' => '/api/v1/dealer/dashboard', 'auth' => true, 'desc' => 'Get dealer dashboard'],
                    ['method' => 'POST', 'endpoint' => '/api/v1/dealer/requirement/post', 'auth' => true, 'desc' => 'Post requirement (buy/sell)'],
                    ['method' => 'GET', 'endpoint' => '/api/v1/dealer/requirements', 'auth' => true, 'desc' => 'Get requirements with filters'],
                    ['method' => 'GET', 'endpoint' => '/api/v1/dealer/opportunities', 'auth' => true, 'desc' => 'Get opportunities'],
                    ['method' => 'GET', 'endpoint' => '/api/v1/dealer/opportunity/{id}', 'auth' => true, 'desc' => 'Get opportunity details'],
                    ['method' => 'POST', 'endpoint' => '/api/v1/dealer/opportunity/{id}/accept', 'auth' => true, 'desc' => 'Accept opportunity'],
                    ['method' => 'POST', 'endpoint' => '/api/v1/dealer/opportunity/{id}/decline', 'auth' => true, 'desc' => 'Decline opportunity'],
                    ['method' => 'GET', 'endpoint' => '/api/v1/dealer/session/{id}', 'auth' => true, 'desc' => 'Get session details'],
                    ['method' => 'GET', 'endpoint' => '/api/v1/dealer/history', 'auth' => true, 'desc' => 'Get session history'],
                    ['method' => 'GET', 'endpoint' => '/api/v1/dealer/chat/{id}', 'auth' => true, 'desc' => 'Get chat messages'],
                    ['method' => 'POST', 'endpoint' => '/api/v1/dealer/chat/{id}/message', 'auth' => true, 'desc' => 'Send chat message'],
                    ['method' => 'POST', 'endpoint' => '/api/v1/dealer/quote/submit/{id}', 'auth' => true, 'desc' => 'Submit quotation'],
                    ['method' => 'GET', 'endpoint' => '/api/v1/dealer/notifications', 'auth' => true, 'desc' => 'Get notifications'],
                    ['method' => 'POST', 'endpoint' => '/api/v1/dealer/notification/{id}/read', 'auth' => true, 'desc' => 'Mark notification read'],
                ]
            ],
            [
                'category' => 'Machine Dealer APIs',
                'routes' => [
                    ['method' => 'POST', 'endpoint' => '/api/v1/machine-dealer/profile/complete', 'auth' => true, 'desc' => 'Complete machine dealer profile'],
                    ['method' => 'GET', 'endpoint' => '/api/v1/machine-dealer/dashboard', 'auth' => true, 'desc' => 'Get machine dealer dashboard'],
                    ['method' => 'POST', 'endpoint' => '/api/v1/machine-dealer/machine/post', 'auth' => true, 'desc' => 'Post machine for sale/buy'],
                    ['method' => 'GET', 'endpoint' => '/api/v1/machine-dealer/listings', 'auth' => true, 'desc' => 'Get active listings'],
                    ['method' => 'GET', 'endpoint' => '/api/v1/machine-dealer/requirements', 'auth' => true, 'desc' => 'Get active requirements'],
                ]
            ],
            [
                'category' => 'Converter APIs',
                'routes' => [
                    ['method' => 'POST', 'endpoint' => '/api/v1/converter/profile/complete', 'auth' => true, 'desc' => 'Complete converter profile'],
                    ['method' => 'GET', 'endpoint' => '/api/v1/converter/dashboard', 'auth' => true, 'desc' => 'Get converter dashboard'],
                ]
            ],
            [
                'category' => 'Brand APIs',
                'routes' => [
                    ['method' => 'POST', 'endpoint' => '/api/v1/brand/profile/complete', 'auth' => true, 'desc' => 'Complete brand profile'],
                    ['method' => 'GET', 'endpoint' => '/api/v1/brand/dashboard', 'auth' => true, 'desc' => 'Get brand dashboard'],
                ]
            ],
            [
                'category' => 'Common APIs',
                'routes' => [
                    ['method' => 'GET', 'endpoint' => '/api/v1/dashboard', 'auth' => true, 'desc' => 'Get unified dashboard'],
                ]
            ],
        ];

        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Routes Documentation</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        .header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .header h1 {
            color: #333;
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        .header p {
            color: #666;
            font-size: 1.1em;
        }
        .base-url {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
            font-family: "Courier New", monospace;
            color: #e83e8c;
            font-weight: bold;
        }
        .category {
            background: white;
            border-radius: 10px;
            margin-bottom: 25px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .category-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 25px;
            font-size: 1.5em;
            font-weight: bold;
        }
        .routes-list {
            padding: 0;
        }
        .route-item {
            border-bottom: 1px solid #e9ecef;
            padding: 20px 25px;
            display: flex;
            align-items: center;
            gap: 20px;
            transition: background 0.2s;
        }
        .route-item:hover {
            background: #f8f9fa;
        }
        .route-item:last-child {
            border-bottom: none;
        }
        .method-badge {
            padding: 8px 16px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 0.85em;
            min-width: 70px;
            text-align: center;
            text-transform: uppercase;
        }
        .method-get { background: #28a745; color: white; }
        .method-post { background: #007bff; color: white; }
        .method-put { background: #ffc107; color: #333; }
        .method-delete { background: #dc3545; color: white; }
        .endpoint {
            flex: 1;
            font-family: "Courier New", monospace;
            font-size: 1.1em;
            color: #333;
            font-weight: 500;
        }
        .auth-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75em;
            font-weight: bold;
        }
        .auth-required { background: #ffc107; color: #333; }
        .auth-not-required { background: #28a745; color: white; }
        .description {
            color: #666;
            font-size: 0.95em;
            margin-left: 20px;
        }
        .footer {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-top: 30px;
            text-align: center;
            color: #666;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        @media (max-width: 768px) {
            .route-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .endpoint {
                width: 100%;
                word-break: break-all;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 API Routes Documentation</h1>
            <p>Complete list of all available API endpoints</p>
            <div class="base-url">Base URL: http://127.0.0.1:8000</div>
        </div>';

        foreach ($routes as $category) {
            $html .= '<div class="category">
                <div class="category-header">' . $category['category'] . '</div>
                <div class="routes-list">';
            
            foreach ($category['routes'] as $route) {
                $methodClass = 'method-' . strtolower($route['method']);
                $authClass = $route['auth'] ? 'auth-required' : 'auth-not-required';
                $authText = $route['auth'] ? 'Auth Required' : 'Public';
                
                $html .= '<div class="route-item">
                    <span class="method-badge ' . $methodClass . '">' . $route['method'] . '</span>
                    <span class="endpoint">' . htmlspecialchars($route['endpoint']) . '</span>
                    <span class="auth-badge ' . $authClass . '">' . $authText . '</span>
                    <span class="description">' . htmlspecialchars($route['desc']) . '</span>
                </div>';
            }
            
            $html .= '</div></div>';
        }

        $html .= '<div class="footer">
            <p>📚 For detailed request/response formats, see <a href="' . route('docs.request-formats') . '">Request Formats Documentation</a></p>
        </div>
    </div>
</body>
</html>';

        return $html;
    })->name('docs.api-routes');

    // Documentation index page
    Route::get('/', function () {
        $docs = [
            ['name' => 'API Routes', 'url' => route('docs.api-routes'), 'description' => 'Beautifully designed API routes list', 'icon' => '🚀'],
            ['name' => 'Request Formats', 'url' => route('docs.request-formats'), 'description' => 'Request and response examples', 'icon' => '📝'],
            ['name' => 'Routes Documentation', 'url' => route('docs.routes-documentation'), 'description' => 'Detailed route documentation', 'icon' => '📖'],
            ['name' => 'Complete API Documentation', 'url' => route('docs.complete'), 'description' => 'Complete API documentation', 'icon' => '📚'],
            ['name' => 'Dealer API Documentation', 'url' => route('docs.dealer'), 'description' => 'Dealer specific APIs', 'icon' => '👤'],
            ['name' => 'Wallet & Payment API', 'url' => route('docs.wallet'), 'description' => 'Wallet, credits, and payment APIs', 'icon' => '💳'],
        ];

        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            background: white;
            padding: 40px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            text-align: center;
        }
        .header h1 {
            color: #333;
            font-size: 3em;
            margin-bottom: 10px;
        }
        .header p {
            color: #666;
            font-size: 1.2em;
        }
        .docs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }
        .doc-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .doc-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        .doc-icon {
            font-size: 3em;
            margin-bottom: 15px;
        }
        .doc-card h2 {
            color: #333;
            font-size: 1.5em;
            margin-bottom: 10px;
        }
        .doc-card p {
            color: #666;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📚 API Documentation</h1>
            <p>Complete API documentation for Zupply B2B Platform</p>
        </div>
        <div class="docs-grid">';

        foreach ($docs as $doc) {
            $html .= '<a href="' . $doc['url'] . '" class="doc-card">
                <div class="doc-icon">' . $doc['icon'] . '</div>
                <h2>' . $doc['name'] . '</h2>
                <p>' . $doc['description'] . '</p>
            </a>';
        }

        $html .= '</div>
    </div>
</body>
</html>';

        return $html;
    })->name('docs.index');
});
