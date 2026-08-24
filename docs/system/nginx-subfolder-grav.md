# Nginx subfolder fix for Grav media routes

This guide fixes broken Grav media URLs when the site is served from a subfolder (example: `/grav`).

## Symptoms

- Pages render, but media URLs like `/grav/infos/equipe/webJolie_si.jpg` return `404`.
- Direct static file access under `/grav/user/pages/...` returns `200`.
- Twig variables are rendered correctly, but images remain broken.

## Root cause

With a subfolder deployment, Nginx must route unknown URLs under `/grav/` to `/grav/index.php`.
If `try_files` is not subfolder-aware, Grav media URLs bypass routing and return `404`.

## Canonical config template

Use [webserver-configs/nginx-subfolder-grav.conf](../../webserver-configs/nginx-subfolder-grav.conf).

Key points:

- `root` points to the parent directory containing the `grav` folder.
- `location ^~ /grav/` uses `try_files $uri $uri/ /grav/index.php?$query_string;`.
- Security rules are prefixed with `/grav/`.
- PHP handler is restricted to `^/grav/.*\.php$`.

## Rollout checklist

1. Backup current Nginx vhost file.
2. Apply subfolder-aware config.
3. Validate syntax:
   - `nginx -t`
4. Reload:
   - `systemctl reload nginx`
5. Validate routing:
   - `curl -I https://your-host/grav/infos/equipe`
   - `curl -I https://your-host/grav/infos/equipe/webJolie_si.jpg`

Expected result: both return `200`.

## Post-fix content cleanup (optional)

If temporary image paths under `/grav/user/pages/...` were used as a workaround,
you can switch back to normal Grav media URLs after Nginx is fixed.

Recommended target pattern for this section:

- Team page images: `{{ base_url_relative }}/infos/equipe/<file>.jpg`
- Bio page images: `{{ base_url_relative }}/infos/equipe/<slug>/<file>.jpg`

## Regression prevention

Add one media URL check to deployment smoke tests, for example:

- `/grav/infos/equipe/webJolie_si.jpg`

This catches subfolder routing regressions early.

In this repository, you can enable it through:

- `MEDIA_CHECK_URLS="infos/equipe/webJolie_si.jpg" ./scripts/deploy.sh --apply`

The `scripts/check-post-deploy.sh` script appends these paths to the existing smoke tests.
