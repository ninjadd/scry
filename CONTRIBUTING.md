# Contributing to Scry

Thank you for considering contributing to **Scry Database Manager**! We welcome bug reports, feature requests, documentation updates, and pull requests from the community.

---

## Code of Conduct

Please ensure a welcoming, professional, and respectful community environment for all contributors.

---

## How Can I Contribute?

### 1. Reporting Bugs
- Search existing [GitHub Issues](https://github.com/ninjadd/scry/issues) to verify the bug hasn't already been reported.
- Open a new issue with a clear title, reproduction steps, expected vs. actual behavior, and environment details (PHP version, Laravel version, database engine).

### 2. Suggesting Enhancements
- Open a feature request issue describing the proposed functionality and use case.

### 3. Submitting Pull Requests (PRs)
1. **Fork the Repository**: Fork `https://github.com/ninjadd/scry.git` to your GitHub account and clone locally.
2. **Create a Feature Branch**:
   ```bash
   git checkout -b feature/my-new-feature
   ```
3. **Install Dependencies**:
   ```bash
   cd scry-package
   composer install
   npm install
   ```
4. **Make Your Changes**: Ensure code follows PSR-12 standards.
5. **Run the Test Suite**: All PHPUnit tests must pass before submitting:
   ```bash
   composer test
   ```
6. **Rebuild Frontend Assets** (if Vue components were edited):
   ```bash
   npm run build
   ```
7. **Commit & Push**:
   ```bash
   git commit -m "feat: Add my new feature"
   git push origin feature/my-new-feature
   ```
8. **Submit a Pull Request**: Open a PR targeting the `main` branch of `ninjadd/scry`.

---

## Development & Test Setup

### Local Test Execution
```bash
cd scry-package
./vendor/bin/phpunit
```

### Multi-Database Docker Environment
```bash
docker-compose up -d --build
docker exec -w /var/www/html/dummy-app scry_dummy_app php artisan scry:seed-all --fresh
```

Thank you for helping build Scry!
