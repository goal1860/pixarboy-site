# Asset Build System

This project uses an environment-aware asset system that automatically serves minified assets in production and readable assets in development.

## How It Works

1. **Development Mode**: Serves readable CSS/JS files directly from `assets/css/` and `assets/js/`
2. **Production Mode**: Serves minified CSS/JS files from `assets/css/dist/` and `assets/js/dist/`

## Environment Detection

The system detects the environment in this order:

1. Checks for `.env` file with `ENVIRONMENT=production` or `ENVIRONMENT=development`
2. Auto-detects based on hostname:
   - If hostname contains `pixarboy.com` → production
   - Otherwise → development

## Building Assets for Production

Before deploying to production, run the build script to generate minified files:

```bash
php scripts/build-assets.php
```

This will:
- Minify `assets/css/style.css` → `assets/css/dist/style.min.css`
- Minify `assets/js/main.js` → `assets/js/dist/main.min.js`
- Show file size savings

## Usage in Templates

The `getAssetPath()` function automatically handles asset selection:

```php
// In header.php
<link rel="stylesheet" href="<?php echo getAssetPath('css', 'style'); ?>">

// In footer.php
<script src="<?php echo getAssetPath('js', 'main'); ?>"></script>
```

## Adding New Assets

1. Add your CSS/JS file to `assets/css/` or `assets/js/`
2. Add it to the build script in `scripts/build-assets.php`:
   ```php
   $cssFiles = [
       'style' => __DIR__ . '/../assets/css/style.css',
       'newfile' => __DIR__ . '/../assets/css/newfile.css'  // Add here
   ];
   ```
3. Use `getAssetPath('css', 'newfile')` in your templates

## Deployment Workflow

### Recommended: Build Before Deployment

**Step 1: Build assets locally**
```bash
php scripts/build-assets.php
```

**Step 2: Commit minified files**
```bash
git add assets/css/dist/ assets/js/dist/
git commit -m "Build assets for production"
git push
```

**Step 3: Deploy**
- Deploy your code (minified files are already included)
- No build step needed on the server
- Faster deployment, no server dependencies

### Alternative: Build on Server After Deployment

If you prefer to build on the server:

1. Keep minified files in `.gitignore`
2. Deploy your code
3. SSH into production server
4. Run: `php scripts/build-assets.php`
5. Ensure write permissions for `assets/css/dist/` and `assets/js/dist/`

**Note**: This requires PHP and file write permissions on your production server.

## Deployment Checklist

- [ ] Run `php scripts/build-assets.php` to generate minified files
- [ ] Commit minified files (if using pre-deployment build)
- [ ] Ensure `.env` file has `ENVIRONMENT=production` (or rely on auto-detection)
- [ ] Deploy code to production
- [ ] Test that minified assets load correctly

## Notes

- **Recommended approach**: Build before deployment and commit minified files
- Minified files are NOT in `.gitignore` by default (you can add them if using server-side builds)
- Asset versioning is controlled by `ASSET_VERSION` in `config/config.php`
- In development, assets use `?v=timestamp` for cache busting
- In production, assets use `?v=ASSET_VERSION` for versioning

