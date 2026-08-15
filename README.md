# WP-CLI Workshops — WordCamp US 2026

This repository was created for the hands-on workshop at **WordCamp US 2026** (Phoenix, AZ).

| | |
|---|---|
| **Session** | [Building WP-CLI Automation for Large WordPress Content Sites](https://us.wordcamp.org/2026/session/building-wp-cli-automation-for-large-wordpress-content-sites/) |
| **Speaker** | Łukasz Wilczak |
| **Track** | Technical WordPress (North 222) |
| **When** | 17 August 2026, 10:15 am MST |
| **Repository** | [lukaszwilczakhostinger/wp-cli-workshops](https://github.com/lukaszwilczakhostinger/wp-cli-workshops) |

Large content sites accumulate debt over years: inconsistent headings, broken or absolute internal links, leftover Gutenberg blocks, missing image alt text. In this workshop we build reusable, production-safe **WP-CLI** commands that process posts in batches — with `--dry-run`, `--limit`, `--offset`, and similar flags — instead of one-off throwaway scripts.

Bring a laptop. This README restores a full testing site in [Local](https://localwp.com/) before the session — WordPress, this plugin, the database, and media. You only clone the plugin separately if you skip the snapshot and use your own stack (for example Docker).

---

## What you will need

- A computer with **8 GB RAM** or more (the sample site archive is large)
- Administrator rights to install software
- [Local](https://localwp.com/) (free WordPress desktop app)
- The Duplicator package from this repo’s **Releases** tab (archive zip + `installer.php`)

Optional but useful during the workshop: [Cursor](https://cursor.com/) or another code editor, and a GitHub account.

---

## 1. Download the workshop testing site

The sample content lives in a **GitHub Release**, not in the git tree (the zip is too large).

1. Open **[Releases](https://github.com/lukaszwilczakhostinger/wp-cli-workshops/releases)** (latest: [v1.0.0](https://github.com/lukaszwilczakhostinger/wp-cli-workshops/releases/tag/v1.0.0)).
2. Download **both** assets into the same folder on your computer, for example `Downloads/wcus26-duplicator/`:

   | File | Role |
   |---|---|
   | `installer.php` | Duplicator installer script |
   | `20260810_traveler_788cd0be09865c8a8377_20260814122912_archive.zip` | Full site: WordPress, plugin, database, media |

3. **Do not rename the zip.** The installer looks for that exact archive name.

The archive is about **510 MB**. Give the download time to finish.

This is a **full WordPress snapshot**, not a plugin-only zip. It includes core, themes, **Maintenance Tools WCUS26 already installed**, the MySQL dump, uploads (photos and media), and the content we will use during the exercises. You do **not** install the plugin again after a Duplicator restore.

The site is for **testing and experimentation only**, not production.

---

## 2. Install Local

1. Go to **[https://localwp.com/](https://localwp.com/)** and click **Download for Free**.
2. Pick the build for your OS:
   - **macOS**: Apple Silicon (M1/M2/M3/M4) vs Intel
   - **Windows**
   - **Linux**
3. Install and open **Local**. You can skip creating a Local account if you only need a site on disk.

Local bundles PHP, Nginx/Apache, MySQL, and WP-CLI. You do not need XAMPP, MAMP, or Docker for this workshop.

---

## 3. Create an empty site in Local

Duplicator needs a running local URL and an empty MySQL database. Local creates both when you add a site.

1. In Local, click **+ Create a new site**.
2. **What’s your site’s name?**  
   Use `wp-cli-workshop` (site URL will be `https://wp-cli-workshop.local`).
3. Environment: **Preferred** is fine. If you choose Custom, use **PHP 8.0 or newer** (the plugin requires PHP 8.0+).
4. WordPress username/password: Local will generate an admin user. **You will not use it after Duplicator runs** — the restored site brings its own users.
5. Click **Add Site** and wait until the site shows as **Running** (green).

### Local database credentials (you will type these into Duplicator)

Every Local site uses the same MySQL defaults:

| Field | Value |
|---|---|
| **Host / Server** | `localhost` |
| **Database** | `local` |
| **Username** | `root` |
| **Password** | `root` |
| **Port** | default (leave empty unless the installer asks; Local uses a socket/local port internally) |

You can confirm them anytime: select the site in Local → **Database** tab.

---

## 4. Put the Duplicator files in the site web root

The web root is `app/public` inside the Local site folder.

1. In Local, select **wp-cli-workshop**.
2. Click **Go to site folder** (or right-click the site → **Show in Finder** / **Show in Explorer**).
3. Open `app` → `public`.
4. **Delete everything** already in `public` (the default WordPress files Local just installed). Leave the `public` folder itself.
5. Copy **both** Duplicator files into `public`:

   ```
   public/
     installer.php
     20260810_traveler_788cd0be09865c8a8377_20260814122912_archive.zip
   ```

6. Make sure the site is still **Running** in Local.

On macOS the folder is typically:

`~/Local Sites/wp-cli-workshop/app/public`

---

## 5. Run the Duplicator installer

1. In Local, click **Open site** (or visit `https://wp-cli-workshop.local/` — you should **not** see a working WordPress home page yet).
2. Open this URL in your browser:

   **https://wp-cli-workshop.local/installer.php**

   If the browser warns about a self-signed SSL certificate, continue to the site (Local’s “Trust” button on the SSL tab can add the certificate).

3. **Step 1 — Setup / Archive**  
   The installer should find the zip automatically. Accept the terms/notices and continue.

4. **Step 2 — Database**  
   Enter the Local credentials from the table above:

   - Host: `localhost`
   - Database: `local`
   - User: `root`
   - Password: `root`

   For **Action**, choose **Connect and Remove All Data** (or equivalent “empty this database”). Local already created a WordPress database; Duplicator must replace those tables with the workshop dump.

   Click **Validate** / **Test Connection**. When it succeeds, continue.

5. **Step 3 — Update URLs**  
   Old URLs from the packaged site should be rewritten to `https://wp-cli-workshop.local` (Duplicator usually fills this in). Keep the table prefix the installer detected. Continue.

6. **Step 4 — Test**  
   Open the site and wp-admin when prompted.

Extraction of a 510 MB archive can take several minutes. Leave the tab open.

### After a successful install

1. Confirm the front-end loads at `https://wp-cli-workshop.local/`.
2. **Delete installer leftovers** from `app/public` (security + Duplicator’s own reminder):
   - `installer.php`
   - `installer-backup.php` (if present)
   - `dup-installer/` (if present)
   - the `*_archive.zip` file
3. Log in at `/wp-login.php` with the **packaged site** account (not the admin Local created when you added the empty site). Local’s **WP Admin** button often fails for that reason.

   | | |
   |---|---|
   | **Username** | `wordpress` |
   | **Password** | `codeispoetry` |

4. The plugin is already in `wp-content/plugins/maintenance-tools-wcus26/`. Confirm it is active:

   ```bash
   wp plugin activate maintenance-tools-wcus26
   wp help wcus26
   ```

   For the workshop, open that plugin folder in Cursor (the Local site’s copy), so `.cursor/rules` apply while we generate commands.

### If the installer fails

- Confirm **both** files are in `public` and the zip name was **not** changed.
- Confirm `public` contained **only** those two files before you started.
- Confirm the site is Running and you used `localhost` / `local` / `root` / `root`.
- To retry: delete everything in `public` except the two Duplicator files, then open `installer.php` again.

---

## 6. WP-CLI from Local

Local → select the site → **Open site shell**. That shell already has `wp` on the PATH and the correct WordPress root.

```bash
wp wcus26
wp wcus26 list-posts --limit=5
wp wcus26 structure-stats --limit=5
wp wcus26 structure-stats-report --limit=20
```

---

## Alternative: your own WordPress (Docker, MAMP, existing site)

Use this path only if you **do not** restore the Duplicator snapshot — for example a Docker Compose stack, MAMP, or a site you already have.

The snapshot is the recommended setup: it ships the plugin **and** the posts, media, and database we will query in the exercises. A blank WordPress install will run the commands, but you will not have the same content to inspect.

1. Clone this repository into `wp-content/plugins/maintenance-tools-wcus26/` (or copy the plugin folder there).
2. Use **PHP 8.0+** and **WordPress 6.0+**.
3. Activate the plugin and confirm WP-CLI:

   ```bash
   wp plugin activate maintenance-tools-wcus26
   wp help wcus26
   ```

4. Point Cursor at the plugin directory inside that site.

Database host, name, user, and password then come from **your** stack (Docker Compose, `.env`, etc.), not from the Local defaults above.

---

# Maintenance Tools WCUS26

WP-CLI maintenance toolkit used as the **workshop base**. It is not a finished content-fixer: we add real commands live (dead-link reports, relative URLs, heading demotion, Gutenberg block swaps, OpenAI alt text, …).

Requires **WordPress 6.0+** and **PHP 8.0+**.

## Built-in commands

| Command | What it does |
|---|---|
| `wp wcus26 list-posts` | TSV: ID, title, category, image count, URL |
| `wp wcus26 structure-stats` | TSV: paragraphs, headings, images, internal/external links |
| `wp wcus26 structure-stats-report` | Same stats saved as a CSV report (download from **Maintenance → Reports**) |

## Shared flags (post commands)

| Flag | Meaning |
|---|---|
| `--post-type` | Default `post`. Comma-separated for multiple types |
| `--post-status` | Default `publish` |
| `--limit` / `--offset` | Cap or skip matching posts (`0` = no limit) |
| `--batch-size` | Posts per query (default **10**) |
| `--sleep` | Pause after each post (seconds, decimals allowed) |
| `--dry-run` | Preview without writing |
| `--save-logs` | Store each CLI output line in **Maintenance → Logs** |
| `--skip-all-hooks` | Disable all actions/filters during `process_post()` (keeps `WP_Query` hooks) |
| `--skip-hooks=save_post,edit_post` | Disable only those hooks |
| Extra `WP_Query` args | e.g. `--category_in=3,5`, `--tag_slug_in=europe` |

## How commands are structured

Drop a class in `includes/cli/commands/class-{name}-command.php`. It is registered automatically as `wp wcus26 {name}`.

- No post loop → extend `Abstract_Command`, implement `run()`.
- Post loop → extend `Abstract_Posts_Command`, implement `process_post()`. Optional: `before_run()`, `after_batch()`, `print_summary()`.
- CSV reports: `Report::create()` once (header included), then `Report::append( Report::to_csv( $rows ) )` **per batch**, not per post. See `structure-stats-report`.
- Gutenberg updates: `parse_blocks()` / `serialize_blocks()`, not `str_replace` on block comments.
- Do not register the `wcus26` root command as a closure; `Root_Command` must stay an empty class.

Admin screens: **Maintenance → Reports** and **Maintenance → Logs**.

## License

GPL v2 or later. See the plugin header in `maintenance-tools-wcus26.php`.
