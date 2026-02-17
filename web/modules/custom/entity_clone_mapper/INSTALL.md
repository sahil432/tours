# Installation Instructions for Entity Clone Mapper

## Quick Installation

### Option 1: Using Drush (Recommended)

1. Copy the module to your Drupal installation:
   ```bash
   cp -r entity_clone_mapper /path/to/drupal/web/modules/custom/
   ```

2. Enable the module:
   ```bash
   drush en entity_clone_mapper -y
   ```

3. Clear cache:
   ```bash
   drush cr
   ```

### Option 2: Using Drupal UI

1. Copy the `entity_clone_mapper` directory to `/modules/custom/`

2. Go to **Admin > Extend** (`/admin/modules`)

3. Find "Entity Clone Mapper" in the Custom package

4. Check the checkbox next to it

5. Click "Install" at the bottom of the page

6. Clear cache at **Admin > Configuration > Development > Performance**

## Post-Installation Setup

### 1. Configure Permissions

Navigate to **Admin > People > Permissions** (`/admin/people/permissions`)

Assign permissions based on your needs:

- **Administer Entity Clone Mapper**: Give to site administrators who will configure mappings
- **Clone Entities**: Give to content editors who will use the clone functionality

### 2. Configure Your First Mapping

1. Go to **Admin > Configuration > Content > Entity Clone Mapper** (`/admin/config/content/entity-clone-mapper`)

2. Configure the first mapping:
   - **Source Entity Type**: e.g., "Content (Node)"
   - **Source Bundle**: e.g., "Article"
   - **Target Entity Type**: e.g., "Content (Node)"
   - **Target Bundle**: e.g., "Basic Page"

3. The Field Mappings section will appear automatically

4. Map each source field to a target field:
   - `title` → `title`
   - `body` → `body`
   - `field_image` → `field_image`
   - etc.

5. Click "Save configuration"

### 3. Test the Clone Functionality

1. Edit an existing node of the source type (e.g., an Article)

2. Look for the "Clone to Another Type" button in the actions area

3. Click it to test the cloning process

4. Verify the cloned entity was created with:
   - Correct field mappings
   - Unpublished status
   - Proper paragraph cloning (if applicable)

## Verifying Installation

Run these checks to ensure proper installation:

### Check Module is Enabled
```bash
drush pm:list --status=enabled | grep entity_clone_mapper
```

Expected output: `Entity Clone Mapper (entity_clone_mapper)     Enabled`

### Check Routes are Available
```bash
drush route:get entity_clone_mapper.settings
```

Should display the route information.

### Check Services are Registered
```bash
drush debug:container entity_clone_mapper.clone_service
```

Should display the service definition.

## Troubleshooting Installation

### Module doesn't appear in Extend page

**Solution**: Clear cache
```bash
drush cr
```

### "Class not found" errors

**Solutions**:
1. Verify all files were copied correctly
2. Check file permissions (should be readable by web server)
3. Rebuild cache:
   ```bash
   drush cr
   drush cc drush
   ```

### Configuration page shows 404 error

**Solution**: Clear routing cache
```bash
drush cr router
```

Or full cache clear:
```bash
drush cr
```

### Paragraphs not cloning

**Solution**: Ensure Paragraphs module is installed
```bash
drush en paragraphs -y
```

## Uninstallation

To completely remove the module:

1. Disable the module:
   ```bash
   drush pm:uninstall entity_clone_mapper -y
   ```

2. Remove the module directory:
   ```bash
   rm -rf /path/to/drupal/web/modules/custom/entity_clone_mapper
   ```

3. Clear cache:
   ```bash
   drush cr
   ```

Note: Uninstalling will remove all configuration including saved mappings.

## Updating the Module

If you receive an updated version:

1. Put site in maintenance mode (optional but recommended):
   ```bash
   drush state:set system.maintenance_mode 1 --input-format=integer
   ```

2. Backup your configuration:
   ```bash
   drush config:export
   ```

3. Replace module files:
   ```bash
   rm -rf /path/to/drupal/web/modules/custom/entity_clone_mapper
   cp -r entity_clone_mapper /path/to/drupal/web/modules/custom/
   ```

4. Run database updates (if any):
   ```bash
   drush updb -y
   ```

5. Clear cache:
   ```bash
   drush cr
   ```

6. Take site out of maintenance mode:
   ```bash
   drush state:set system.maintenance_mode 0 --input-format=integer
   ```

## Next Steps

After installation:

1. Review the [README.md](README.md) for detailed usage instructions
2. Configure your mappings at `/admin/config/content/entity-clone-mapper`
3. Set up appropriate permissions for your user roles
4. Test with sample content before using in production
5. Monitor logs at `/admin/reports/dblog` for any issues
