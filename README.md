# DEMO: https://lcars.sk/polli.php
![Built with Pollinations](https://img.shields.io/badge/Built%20with-Pollinations-8a2be2?style=for-the-badge&logoColor=white&labelColor=6a0dad)

### 🌱 GitHub Tier Calculator for Pollinations.ai

- A lightweight, clean, and dependency-free PHP application that analyzes a public GitHub profile and calculates its qualification tier for the Pollinations.ai Seed Plan.
- It utilizes the GitHub REST API to fetch user statistics and applies a specific mathematical formula to determine the user's current level and progress.

### ✨ Features

- No External Dependencies: Built with pure PHP and vanilla CSS. No frameworks, no Tailwind, no external fonts.
- Accurate Analytics: Fetches real-time data from the GitHub API (Users, Repositories, Search API).
- Intelligent Filtering: Excludes forks and empty repositories from calculations to ensure fair scoring.
- Time-Sensitive Data: Specifically calculates commits made within the last 90 days.
- Responsive UI: A modern, minimalist, Apple-like interface that looks great on both desktop and mobile devices.
- Visual Feedback: Smooth progress bars and dynamic emojis (🍄/🌱) based on qualification status.

### 🧮 Scoring System

- To qualify for the Seed Plan, a user needs to reach a threshold of 8.0 points. The maximum possible score is 14.0 points. The scoring is broken down as follows:

| Metric            | Scoring Formula                       | Max points |
|-------------------|---------------------------------------|------------|
| Account age       | months × 0.5                          | 6.0        |
| GitHub stars      | stars × 0.1                           | 5.0        |
| Commits           | commits (last 90 days) × 0.1          | 2.0        |
| Public repos      | original, non-empty repository × 0.5  | 1.0        |
| **GitHub**        |                                       | **14.0**   |
| **Pollination**   |                                       | **8.0**    |


- Levels

| Level | Name          | Points threshold |
|-------|---------------|------------------|
| 1     | Beginner      | 0–2              |
| 2     | Amateur       | 2–4              |
| 3     | Intermediate  | 4–6              |
| 4     | Advanced      | 6–8              |
| 5     | Qualified     | 8+               |

### 🚀 Requirements

- PHP 7.4 or higher
- cURL extension enabled (php-curl)


Note on GitHub API Rate Limits
> This tool uses unauthenticated requests to the GitHub API, which are subject to a rate limit of 60 requests per hour per IP address. If you encounter errors during heavy testing, you may need to wait for the limit to reset.

### 🛠 Built With

- Backend: PHP (cURL)
- Frontend: HTML5, Vanilla CSS3 (Custom Design System)
API: GitHub REST API v3

📄 License

This project is licensed under the MIT License - see the LICENSE file for details.
