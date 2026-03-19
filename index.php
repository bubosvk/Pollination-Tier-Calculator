<?php
/**
 * GitHub Tier Calculator
 * This script analyzes a public GitHub profile, calculates points (with decimals)
 * according to the predefined formula, and displays the stats in a clean UI.
 */

// Initialize variables
$username = $_GET['username'] ?? '';
$username = trim($username);
$userData = null;
$error = null;
$scoreData = null;

// Level system configuration
$threshold = 8.0; // Points required to qualify
$maxPoints = 14.0; // Maximum possible points (6+5+2+1)

$levelThresholds = [
    1 => ['min' => 0.0,  'name' => 'Beginner'],
    2 => ['min' => 2.0,  'name' => 'Amateur'],
    3 => ['min' => 4.0,  'name' => 'Intermediate'],
    4 => ['min' => 6.0,  'name' => 'Advanced'],
    5 => ['min' => 8.0,  'name' => 'Qualified']
];

// If a username is provided, query the GitHub API
if (!empty($username)) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.github.com/users/" . urlencode($username));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, "GitHub-Tier-Calculator");
    
    // Disable SSL verification (useful for local development environments)
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        $error = "Connection error (cURL): " . htmlspecialchars($curlError);
    } elseif ($httpCode === 200) {
        $userData = json_decode($response, true);
        
        // --- ADDITIONAL API CALLS FOR STARS AND COMMITS ---
        
        // 1. Fetch Stars from repositories
        $chRepos = curl_init();
        curl_setopt($chRepos, CURLOPT_URL, "https://api.github.com/users/" . urlencode($username) . "/repos?per_page=100");
        curl_setopt($chRepos, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($chRepos, CURLOPT_USERAGENT, "GitHub-Tier-Calculator");
        curl_setopt($chRepos, CURLOPT_SSL_VERIFYPEER, false);
        $reposResponse = curl_exec($chRepos);
        curl_close($chRepos);
        
        $totalStars = 0;
        $validReposCount = 0;
        if ($reposResponse) {
            $reposData = json_decode($reposResponse, true);
            if (is_array($reposData)) {
                foreach ($reposData as $repo) {
                    // Filter: Only count original repos (not forks) that are not empty (size > 0)
                    if (empty($repo['fork']) && !empty($repo['size'])) {
                        $totalStars += $repo['stargazers_count'] ?? 0;
                        $validReposCount++;
                    }
                }
            }
        }

        // 2. Fetch total Commits (using Search API - Last 90 days only)
        $ninetyDaysAgo = (new DateTime())->modify('-90 days')->format('Y-m-d');
        $chCommits = curl_init();
        curl_setopt($chCommits, CURLOPT_URL, "https://api.github.com/search/commits?q=author:" . urlencode($username) . "+committer-date:>" . $ninetyDaysAgo);
        curl_setopt($chCommits, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($chCommits, CURLOPT_USERAGENT, "GitHub-Tier-Calculator");
        curl_setopt($chCommits, CURLOPT_HTTPHEADER, ['Accept: application/vnd.github.cloak-preview+json']);
        curl_setopt($chCommits, CURLOPT_SSL_VERIFYPEER, false);
        $commitsResponse = curl_exec($chCommits);
        curl_close($chCommits);
        
        $totalCommits = 0;
        if ($commitsResponse) {
            $commitsData = json_decode($commitsResponse, true);
            $totalCommits = $commitsData['total_count'] ?? 0;
        }

        // --- CALCULATE POINTS ---
        
        // 1. Account age - 0.5 pts per month, max 6 points
        $createdAt = new DateTime($userData['created_at']);
        $now = new DateTime();
        $diff = $now->diff($createdAt);
        $months = ($diff->y * 12) + $diff->m;
        $agePoints = min(6.0, round($months * 0.5, 1));

        // 2. Stars - 0.1 pts per star, max 5 points
        $starsPoints = min(5.0, round($totalStars * 0.1, 1));

        // 3. Commits - 0.1 pts per commit, max 2 points
        $commitsPoints = min(2.0, round($totalCommits * 0.1, 1));

        // 4. Repositories - 0.5 pts per original non-empty repo, max 1 point
        $repoPoints = min(1.0, round($validReposCount * 0.5, 1));

        // Total points
        $totalPoints = $agePoints + $starsPoints + $commitsPoints + $repoPoints;

        // Determine Level based on total points
        $level = 1;
        $levelName = 'Beginner';
        foreach (array_reverse($levelThresholds, true) as $lvl => $data) {
            if ($totalPoints >= $data['min']) {
                $level = $lvl;
                $levelName = $data['name'];
                break;
            }
        }

        // Calculate missing points
        $nextLevelPoints = 0.0;
        if ($level < 5) {
            $nextLevelPoints = max(0, $levelThresholds[$level + 1]['min'] - $totalPoints);
        }
        $pointsToQualify = max(0, $threshold - $totalPoints);
        
        // Determine the emoji based on qualification
        $levelEmoji = ($totalPoints >= 8.0) ? '🌱' : '🍄';

        // Data for category breakdown
        $categories = [
            ['label' => 'Account Age', 'score' => $agePoints, 'max' => 6.0],
            ['label' => 'Stars', 'score' => $starsPoints, 'max' => 5.0],
            ['label' => 'Commits', 'score' => $commitsPoints, 'max' => 2.0],
            ['label' => 'Repositories', 'score' => $repoPoints, 'max' => 1.0],
        ];

    } elseif ($httpCode === 403) {
        $error = "GitHub API rate limit exceeded. Please try again later.";
    } elseif ($httpCode === 404) {
        $error = "User '@" . htmlspecialchars($username) . "' was not found.";
    } else {
        $error = "GitHub API communication error. HTTP Code: " . $httpCode;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GitHub Tier Calculator</title>
    <style>
        /* Custom CSS - No external dependencies */
        :root {
            --bg-color: #fafafa;
            --text-main: #111827;
            --text-muted: #6b7280;
            --text-light: #9ca3af;
            --border-color: #e5e7eb;
            --border-light: #f3f4f6;
            --primary: #111827;
            --primary-hover: #000000;
            --success: #10b981;
            --success-bg: #ecfdf5;
            --success-border: #d1fae5;
            --success-text: #047857;
            --error-bg: #fef2f2;
            --error-border: #fee2e2;
            --error-text: #dc2626;
        }

        *, *::before, *::after {
            box-sizing: inherit;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 4rem 1.5rem;
            margin: 0;
            -webkit-font-smoothing: antialiased;
            box-sizing: border-box;
        }

        .container {
            max-width: 42rem;
            width: 100%;
        }

        /* Utility Spacing & Align */
        .text-center { text-align: center; }
        .mb-12 { margin-bottom: 3rem; }
        .mb-8 { margin-bottom: 2rem; }
        .mb-6 { margin-bottom: 1.5rem; }
        .mb-5 { margin-bottom: 1.25rem; }
        .mb-2 { margin-bottom: 0.5rem; }
        .mb-1 { margin-bottom: 0.25rem; }

        /* Header */
        .logo {
            height: 3rem;
            margin: 0 auto 1.25rem;
            display: block;
        }
        
        .title {
            font-size: 1.875rem;
            font-weight: 700;
            letter-spacing: -0.025em;
            margin: 0 0 0.75rem;
        }

        .subtitle {
            color: var(--text-muted);
            font-size: 0.875rem;
            margin: 0 0 2rem;
        }

        /* Search Form */
        .search-form {
            display: flex;
            gap: 0.75rem;
            max-width: 28rem;
            margin: 0 auto;
        }

        .search-input {
            flex: 1;
            padding: 0.625rem 1rem;
            border-radius: 0.5rem;
            border: 1px solid var(--border-color);
            background: #fff;
            font-size: 0.875rem;
            font-family: inherit;
            transition: all 0.2s;
            box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 1px var(--primary);
        }

        .search-btn {
            background: var(--primary);
            color: #fff;
            padding: 0.625rem 1.5rem;
            border-radius: 0.5rem;
            border: none;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
            box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05);
        }

        .search-btn:hover {
            background: var(--primary-hover);
        }

        /* Error */
        .error-msg {
            background: var(--error-bg);
            color: var(--error-text);
            border: 1px solid var(--error-border);
            border-radius: 0.5rem;
            padding: 1rem;
            font-size: 0.875rem;
            text-align: center;
            margin-bottom: 2rem;
        }

        /* Profile Header */
        .profile-header {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        .avatar {
            width: 4rem;
            height: 4rem;
            border-radius: 50%;
            border: 1px solid var(--border-color);
            box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05);
        }

        .profile-info {
            flex: 1;
        }

        .profile-name {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }

        .profile-link {
            font-size: 0.875rem;
            color: var(--text-muted);
            text-decoration: none;
            transition: color 0.2s;
        }

        .profile-link:hover {
            color: var(--primary);
        }

        .badge {
            height: 1.75rem;
            opacity: 0.9;
            transition: opacity 0.2s;
        }

        .badge:hover {
            opacity: 1;
        }

        /* Card */
        .card {
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }

        /* Level Display */
        .level-display {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid var(--border-light);
        }

        .label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.25rem;
        }

        .level-value-container {
            display: flex;
            align-items: baseline;
            gap: 0.75rem;
        }

        .level-number {
            font-size: 3rem;
            font-weight: 700;
            letter-spacing: -0.05em;
            line-height: 1;
        }

        .level-emoji {
            font-size: 2.25rem;
            margin-left: 0.25rem;
        }

        .level-name {
            font-size: 1.25rem;
            font-weight: 500;
            color: var(--text-light);
        }

        .score-value {
            font-size: 1.875rem;
            font-weight: 600;
            letter-spacing: -0.025em;
            line-height: 1;
        }

        .score-max {
            font-size: 1.125rem;
            color: var(--text-light);
            font-weight: 400;
        }

        .text-right { text-align: right; }

        /* Categories */
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem 3rem;
            margin-bottom: 2.5rem;
        }

        .cat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 0.5rem;
        }

        .cat-name {
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
        }

        .cat-score {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .cat-score-val { color: var(--primary); }

        /* Progress Bars */
        .progress-track {
            width: 100%;
            background: var(--border-light);
            border-radius: 9999px;
            overflow: hidden;
        }

        .progress-track.sm { height: 0.375rem; }
        .progress-track.lg { height: 0.625rem; }

        .progress-bar {
            height: 100%;
            border-radius: 9999px;
            background: var(--primary);
            transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .progress-bar.success { background: var(--success); }

        /* Missing Info Boxes */
        .missing-info {
            display: flex;
            gap: 1rem;
        }

        .info-box {
            flex: 1;
            background: #f9fafb;
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .info-box.success {
            background: var(--success-bg);
            border-color: var(--success-border);
        }

        .info-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-bottom: 0.25rem;
        }

        .info-label.success { color: #059669; }

        .info-value {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--primary);
        }

        .info-value.success { color: var(--success-text); }

        /* Responsive */
        @media (max-width: 640px) {
            .level-display {
                flex-direction: column;
                align-items: flex-start;
                gap: 1.5rem;
            }
            .level-display > div:last-child {
                text-align: left;
            }
            .text-right {
                text-align: left;
            }
            .missing-info {
                flex-direction: column;
            }
            .badge {
                height: 1.5rem;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        
        <!-- Header & Search -->
        <div class="mb-12 text-center">
            <img src="https://raw.githubusercontent.com/pollinations/pollinations/main/assets/logo.svg" alt="Pollinations Logo" class="logo">
            <h1 class="title">Tier Calculator</h1>
            <p class="subtitle">Analyze your GitHub profile and discover your seed tier.</p>
            
            <form method="GET" action="" class="search-form">
                <input type="text" name="username" value="<?= htmlspecialchars($username) ?>" 
                       placeholder="GitHub username..." required class="search-input">
                <button type="submit" class="search-btn">Analyze</button>
            </form>
        </div>

        <!-- Error Message -->
        <?php if ($error): ?>
            <div class="error-msg">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Results -->
        <?php if ($userData && isset($totalPoints)): ?>
            
            <!-- User Profile Header -->
            <div class="profile-header">
                <img src="<?= htmlspecialchars($userData['avatar_url']) ?>" alt="Avatar" class="avatar">
                <div class="profile-info">
                    <h2 class="profile-name"><?= htmlspecialchars($userData['name'] ?? $userData['login']) ?></h2>
                    <a href="<?= htmlspecialchars($userData['html_url']) ?>" target="_blank" class="profile-link">@<?= htmlspecialchars($userData['login']) ?></a>
                </div>
                <img src="https://img.shields.io/badge/Built%20with-Pollinations-8a2be2?style=for-the-badge&logo=data:image/png;base64,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&logoColor=white&labelColor=6a0dad" alt="Built with Pollinations" class="badge">
            </div>

            <!-- Main Stats Card -->
            <div class="card">
                
                <!-- Level Display -->
                <div class="level-display">
					<div>
                        <div class="label">Pollinations</div>
                        <div class="level-value-container">
							<span class="level-emoji"><?= $levelEmoji ?></span>
                        </div>
                    </div>
                    <div>
                        <div class="label">GitHub Level</div>
                        <div class="level-value-container">
                            <span class="level-number"><?= $level ?></span>
                            <span class="level-name"><?= $levelName ?></span>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="label">Total Score</div>
                        <div class="score-value">
                            <?= number_format($totalPoints, 1, '.', '') ?> <span class="score-max">/ <?= number_format($maxPoints, 1, '.', '') ?></span>
                        </div>
                    </div>
                </div>

                <!-- Categories Grid -->
                <div class="categories-grid">
                    <?php foreach ($categories as $cat): ?>
                        <div>
                            <div class="cat-header">
                                <span class="cat-name"><?= htmlspecialchars($cat['label']) ?></span>
                                <span class="cat-score">
                                    <span class="cat-score-val"><?= number_format($cat['score'], 1, '.', '') ?></span> / <?= number_format($cat['max'], 1, '.', '') ?>
                                </span>
                            </div>
                            <div class="progress-track sm">
                                <div class="progress-bar" style="width: <?= min(100, ($cat['score'] / $cat['max']) * 100) ?>%;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Qualification Progress -->
                <div>
                    <div class="cat-header">
                        <span class="label mb-0">Qualification Progress</span>
                        <span class="cat-score"><?= number_format($totalPoints, 1, '.', '') ?> / <?= number_format($threshold, 1, '.', '') ?> required</span>
                    </div>
                    <div class="progress-track lg">
                        <div class="progress-bar success" style="width: <?= min(100, ($totalPoints / $threshold) * 100) ?>%;"></div>
                    </div>
                </div>

            </div>

            <!-- Missing Points Info -->
            <div class="missing-info">
                <div class="info-box">
                    <span class="info-label">To next level GitHub</span>
                    <span class="info-value">
                        <?php if ($level < 5): ?>
                            <?= number_format($nextLevelPoints, 1, '.', '') ?> pts
                        <?php else: ?>
                            Maxed out
                        <?php endif; ?>
                    </span>
                </div>
                <div class="info-box <?= ($pointsToQualify == 0) ? 'success' : '' ?>">
                    <span class="info-label <?= ($pointsToQualify == 0) ? 'success' : '' ?>">To Seed Tier</span>
                    <span class="info-value <?= ($pointsToQualify == 0) ? 'success' : '' ?>">
                        <?php if ($pointsToQualify > 0): ?>
                            <?= number_format($pointsToQualify, 1, '.', '') ?> pts
                        <?php else: ?>
                            Qualified ✓
                        <?php endif; ?>
                    </span>
                </div>
            </div>

        <?php endif; ?>
    </div>
</body>
</html>
