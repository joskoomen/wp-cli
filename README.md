#YPA WP-CLI

## Calls

### Create local Wordpress
| Available  | Command | Description
|--|--|--
| ✅ | `php ypa-wp create`                   | Create a new WP local Instance
| ✅ | `php ypa-wp create --wpv=5.6.1`       | ⬆️ With fixed version
| ✅ | `php ypa-wp create --install`         | ⬆️ Install and activate plugins
| ❌ | `php ypa-wp create --no-install`      | ⬆️ Without installation

<br>

### Plugin management
| Available  | Command | Description
|--|--|--
| ✅ | `php ypa-wp install`                  | Install plugins
| ✅ | `php ypa-wp require [pluginname ...]` | Add plugin / plugins
| ✅ | `php ypa-wp remove`                   | Remove plugins not listed in wordpress.json
| ✅ | `php ypa-wp remove [pluginname ...]`  | ⬆️ Remove the given plugin / plugins
| ❌ | `php ypa-wp update`                   | Update plugins to latest version

<br>

### Application management
| Available  | Command | Description
|--|--|--
| ✅ | `php ypa-wp serve` | Serve your application at http://localhost:11001

<br>

### Database management
| Available  | Command | Description
|--|--|--
| ✅ | `php ypa-wp db:init`                   | Initialize Migrations
| ✅ | `php ypa-wp make:migration [migration]`| Create a migration file
| ✅ | `php ypa-wp make:seeder [migration]`   | Create a seeder file
| ✅ | `php ypa-wp migrate`                   | Migrate your database
| ✅ | `php ypa-wp migrate --target=2`        | ⬆️ With the version to migrate to
| ✅ | `php ypa-wp migrate --dry-run`         | ⬆️ Dump query instead of execution
| ✅ | `php ypa-wp migrate --fake`            | ⬆️ Mark as run only (no execution)
| ✅ | `php ypa-wp seed`                      | Seed your database with values
| ✅ | `php ypa-wp seed --seed=SeederClass`   | ⬆️ Only given Seeder or array of Seeders
| ✅ | `php ypa-wp db:rollback`               | Rollback migration to previous version
| ✅ | `php ypa-wp db:rollback --target=1`    | ⬆️ With the version to migrate to
| ✅ | `php ypa-wp db:rollback --force`       | ⬆️ Force to ignore breakpoints
| ✅ | `php ypa-wp db:rollback --dry-run`     | ⬆️ Dump query instead of execution
| ✅ | `php ypa-wp db:rollback --fake`        | ⬆️ Mark as run only (no execution)

<br>

### ACF
| Available  | Command | Description
|--|--|--
| ✅ | `php ypa-wp acf-sync` | Import new ACF files

<br>

### Media management
| Available  | Command | Description
|--|--|--
| ❌ | `php ypa-wp download-media` | Download and add media
