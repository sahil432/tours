# Module Renamed: j_navi_entity_clone

## Summary of Changes

The module has been successfully renamed from `entity_clone_mapper` to `j_navi_entity_clone`.

## What Changed

### Module Machine Name
- **Old:** `entity_clone_mapper`
- **New:** `j_navi_entity_clone`

### Module Display Name
- **Old:** Entity Clone Mapper
- **New:** J Navi Entity Clone

### File Renames

**Root Files:**
- `entity_clone_mapper.info.yml` → `j_navi_entity_clone.info.yml`
- `entity_clone_mapper.module` → `j_navi_entity_clone.module`
- `entity_clone_mapper.routing.yml` → `j_navi_entity_clone.routing.yml`
- `entity_clone_mapper.permissions.yml` → `j_navi_entity_clone.permissions.yml`
- `entity_clone_mapper.services.yml` → `j_navi_entity_clone.services.yml`
- `entity_clone_mapper.links.menu.yml` → `j_navi_entity_clone.links.menu.yml`

**Config Files:**
- `config/install/entity_clone_mapper.settings.yml` → `j_navi_entity_clone.settings.yml`
- `config/schema/entity_clone_mapper.schema.yml` → `j_navi_entity_clone.schema.yml`

**Class File:**
- `src/Form/EntityCloneMapperConfigForm.php` → `JNaviEntityCloneConfigForm.php`

### Code Changes

**Namespaces:**
```php
// Old
namespace Drupal\entity_clone_mapper\Form;

// New
namespace Drupal\j_navi_entity_clone\Form;
```

**Class Names:**
```php
// Old
class EntityCloneMapperConfigForm extends ConfigFormBase

// New
class JNaviEntityCloneConfigForm extends ConfigFormBase
```

**Service Names:**
```yaml
# Old
services:
  entity_clone_mapper.clone_service:
    class: Drupal\entity_clone_mapper\Service\EntityCloneService

# New
services:
  j_navi_entity_clone.clone_service:
    class: Drupal\j_navi_entity_clone\Service\EntityCloneService
```

**Config Names:**
```php
// Old
$config = \Drupal::config('entity_clone_mapper.settings');

// New
$config = \Drupal::config('j_navi_entity_clone.settings');
```

**Route Names:**
```yaml
# Old
entity_clone_mapper.settings:
  path: '/admin/config/content/entity-clone-mapper'

# New
j_navi_entity_clone.settings:
  path: '/admin/config/content/j-navi-entity-clone'
```

**Permission Names:**
```yaml
# Old
administer entity clone mapper:
  title: 'Administer Entity Clone Mapper'

# New
administer j navi entity clone:
  title: 'Administer J Navi Entity Clone'
```

**Form IDs:**
```php
// Old
public function getFormId() {
  return 'entity_clone_mapper_selection_form';
}

// New
public function getFormId() {
  return 'j_navi_entity_clone_selection_form';
}
```

## Installation

### Fresh Installation

```bash
# Extract the module
unzip j_navi_entity_clone.zip
# OR
tar -xzf j_navi_entity_clone.tar.gz

# Move to Drupal
mv j_navi_entity_clone /path/to/drupal/web/modules/custom/

# Enable module
drush en j_navi_entity_clone -y

# Clear cache
drush cr
```

### Configuration URL

Access the configuration at:
- **Old URL:** `/admin/config/content/entity-clone-mapper`
- **New URL:** `/admin/config/content/j-navi-entity-clone`

## Updated URLs

All URLs have been updated to use the new machine name:

- **Configuration:** `/admin/config/content/j-navi-entity-clone`
- **Clone endpoint:** `/j-navi-entity-clone/clone/{entity_type}/{entity}`

## Updated Permissions

Permissions have been renamed:

- **Admin permission:** `administer j navi entity clone`
- **Clone permission:** `clone entities` (unchanged)

After installation, update user roles at:
`/admin/people/permissions`

## Service References

When using the service programmatically:

```php
// Old
$service = \Drupal::service('entity_clone_mapper.clone_service');

// New
$service = \Drupal::service('j_navi_entity_clone.clone_service');
```

## Configuration

When accessing configuration:

```php
// Old
$config = \Drupal::config('entity_clone_mapper.settings');

// New
$config = \Drupal::config('j_navi_entity_clone.settings');
```

## Logger Channel

Logger channel name updated:

```php
// Old
$this->logger('entity_clone_mapper')->info('Message');

// New
$this->logger('j_navi_entity_clone')->info('Message');
```

## File Structure

```
j_navi_entity_clone/
├── j_navi_entity_clone.info.yml
├── j_navi_entity_clone.module
├── j_navi_entity_clone.routing.yml
├── j_navi_entity_clone.permissions.yml
├── j_navi_entity_clone.services.yml
├── j_navi_entity_clone.links.menu.yml
├── README.md
├── INSTALL.md
├── QUICKSTART.md
├── TECHNICAL.md
├── MULTILINGUAL.md
├── MULTILINGUAL_UPDATE.md
├── CHANGELOG.md
├── config/
│   ├── install/
│   │   └── j_navi_entity_clone.settings.yml
│   └── schema/
│       └── j_navi_entity_clone.schema.yml
└── src/
    ├── Controller/
    │   └── EntityCloneController.php
    ├── Form/
    │   ├── JNaviEntityCloneConfigForm.php
    │   ├── CloneSelectionForm.php
    │   └── CloneLanguageSelectionForm.php
    └── Service/
        └── EntityCloneService.php
```

## Verification

After installation, verify everything is working:

### 1. Check Module Status
```bash
drush pm:list --status=enabled | grep j_navi
```

Expected: `J Navi Entity Clone (j_navi_entity_clone)     Enabled`

### 2. Check Routes
```bash
drush route:get j_navi_entity_clone.settings
```

Should display the route configuration.

### 3. Check Services
```bash
drush debug:container j_navi_entity_clone.clone_service
```

Should display the service definition.

### 4. Access Configuration
Navigate to: `/admin/config/content/j-navi-entity-clone`

### 5. Test Clone Functionality
1. Create a mapping in configuration
2. Edit a node/term with that content type
3. Look for "Clone to Another Type" button
4. Test the clone operation

## Features (Unchanged)

All features remain the same:
✅ Clone between entity types
✅ Configurable field mapping
✅ Paragraph support (with nesting)
✅ Multilingual support
✅ Taxonomy reference preservation
✅ Unpublished state for clones

## Documentation

All documentation has been updated with the new module name:
- README.md
- INSTALL.md
- QUICKSTART.md
- TECHNICAL.md
- MULTILINGUAL.md
- CHANGELOG.md

## Support

The functionality is identical to the previous version. Only the naming has changed.

For issues:
1. Check logs: `/admin/reports/dblog`
2. Verify configuration: `/admin/config/content/j-navi-entity-clone`
3. Check permissions: `/admin/people/permissions`

---

**Module successfully renamed to j_navi_entity_clone!**
