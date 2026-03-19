🌱 GitHub Tier Calculator for Pollinations.ai

A lightweight, clean, and dependency-free PHP application that analyzes a public GitHub profile and calculates its qualification tier for the Pollinations.ai Seed Plan.
It utilizes the GitHub REST API to fetch user statistics and applies a specific mathematical formula to determine the user's current level and progress.

✨ Features

No External Dependencies: Built with pure PHP and vanilla CSS. No frameworks, no Tailwind, no external fonts.
Accurate Analytics: Fetches real-time data from the GitHub API (Users, Repositories, Search API).
Intelligent Filtering: Excludes forks and empty repositories from calculations to ensure fair scoring.
Time-Sensitive Data: Specifically calculates commits made within the last 90 days.
Responsive UI: A modern, minimalist, Apple-like interface that looks great on both desktop and mobile devices.
Visual Feedback: Smooth progress bars and dynamic emojis (🍄/🌱) based on qualification status.

🧮 Scoring System

To qualify for the Seed Plan, a user needs to reach a threshold of 8.0 points. The maximum possible score is 14.0 points. The scoring is broken down as follows:

| Metric | Scoring Formula | Max Points |
| Account Age | 0.5 points per month | 6.0 |
| Stars | 0.1 points per star (on original, non-empty repos) | 5.0 |
| Commits | 0.1 points per public commit (last 90 days) | 2.0 |
| Repositories | 0.5 points per original, non-empty repository | 1.0 |

Levels

Level 1 (Beginner): 0.0 - 1.9 pts 
Level 2 (Amateur): 2.0 - 3.9 pts
Level 3 (Intermediate): 4.0 - 5.9 pts
Level 4 (Advanced): 6.0 - 7.9 pts
Level 5 (Qualified): 8.0+ pts

🚀 Requirements

PHP 7.4 or higher
cURL extension enabled (php-curl)



Note on GitHub API Rate Limits
This tool uses unauthenticated requests to the GitHub API, which are subject to a rate limit of 60 requests per hour per IP address. If you encounter errors during heavy testing, you may need to wait for the limit to reset.

🛠 Built With

Backend: PHP (cURL)
Frontend: HTML5, Vanilla CSS3 (Custom Design System)
API: GitHub REST API v3

📄 License

This project is licensed under the MIT License - see the LICENSE file for details.
