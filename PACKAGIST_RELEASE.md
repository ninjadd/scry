# Packagist.org Release & Distribution Guide (`scry/scry`)

This guide provides step-by-step instructions for registering and submitting `scry/scry` to **[Packagist.org](https://packagist.org)** to make it globally installable via `composer require scry/scry` for **Laravel 10, 11, 12, and 13+** applications.

---

## 1. Prerequisites Checklist

Before submitting to Packagist, verify:
- [x] Package `composer.json` is updated with complete metadata, authors, homepage, and dependencies (`php: ^8.2|^8.3|^8.4`, `illuminate/*: ^10.0|^11.0|^12.0|^13.0`).
- [x] Pre-compiled assets (`resources/dist/app.js` and `resources/dist/app.css`) are committed to the repository so host apps do not require Node.js.
- [x] Automated test suite passes 100% (`composer test`).
- [x] Installation command `php artisan scry:install` works smoothly out-of-the-box.

---

## 2. Step-by-Step Packagist Submission

### Step 1: Create a Packagist Account
1. Go to **[https://packagist.org](https://packagist.org)** and click **Sign Up** or **Log in with GitHub**.
2. Make sure your GitHub account has admin access to the repository (`https://github.com/ninjadd/scry.git`).

### Step 2: Submit Repository
1. Navigate to **[https://packagist.org/packages/submit](https://packagist.org/packages/submit)**.
2. Enter the Repository URL: `https://github.com/ninjadd/scry.git`
3. Click **Check**.
4. Packagist will read `composer.json` and display package details (`scry/scry`). Click **Submit**.

### Step 3: Configure Automated GitHub Webhook
To ensure Packagist automatically receives updates whenever new tags or releases are pushed:
1. On your Packagist package page (`https://packagist.org/packages/scry/scry`), copy your **API Token** from your Profile settings.
2. Go to your GitHub repository settings: `https://github.com/ninjadd/scry/settings/hooks`.
3. Click **Add webhook**.
4. Set Payload URL: `https://packagist.org/api/github?username=YOUR_PACKAGIST_USERNAME`
5. Set Content type: `application/json`
6. Set Secret: Your Packagist API Token.
7. Click **Add webhook**.

---

## 3. Creating & Tagging Releases

Composer uses Git semantic version tags (e.g. `v1.0.0`, `v1.0.1`) to resolve release versions.

### Creating Tag `v1.0.0`
```bash
# 1. Ensure all changes are committed on main
git checkout main
git pull origin main

# 2. Tag semantic release version
git tag -a v1.0.0 -m "Release v1.0.0 - Production ready for Laravel 13+"

# 3. Push tag to GitHub
git push origin v1.0.0
```

Once pushed, Packagist will immediately index version `v1.0.0`!

---

## 4. End-User Installation Experience

Once published on Packagist, any developer can install Scry in their Laravel 10–13+ application with just two simple terminal commands:

```bash
# 1. Require package via Composer
composer require scry/scry

# 2. Run automatic installer
php artisan scry:install
```

### Customizing Authorization in Laravel Host App (`AppServiceProvider.php`)

```php
use Scry\Facades\Scry;

public function boot(): void
{
    Scry::auth(function ($request) {
        return app()->environment('local') || $request->user()?->isAdmin();
    });
}
```
