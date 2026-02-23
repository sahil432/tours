# Installation Instructions for J Navi Entity Clone

## Quick Installation

### Option 1: Using Drush (Recommended)

1. Copy the module to your Drupal installation:
   ```bash
   cp -r j_navi_entity_clone /path/to/drupal/web/modules/custom/
   ```

2. Enable the module:
   ```bash
   drush en j_navi_entity_clone -y
   ```

3. Clear cache:
   ```bash
   drush cr
   ```

### Option 2: Using Drupal UI

1. Copy the `j_navi_entity_clone` directory to `/modules/custom/`

2. Go to **Admin > Extend** (`/admin/modules`)

3. Find "J Navi Entity Clone" in the Custom package

4. Check the checkbox next to it

5. Click "Install" at the bottom of the page

6. Clear cache at **Admin > Configuration > Development > Performance**

## Post-Installation Setup

### 1. Configure Permissions

Navigate to **Admin > People > Permissions** (`/admin/people/permissions`)

Assign permissions based on your needs:

- **Administer J Navi Entity Clone**: Give to site administrators who will configure mappings
- **Clone Entities**: Give to content editors who will use the clone functionality

### 2. Configure Your First Mapping

1. Go to **Admin > Configuration > Content > J Navi Entity Clone** (`/admin/config/content/j-navi-entity-clone`)

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
drush pm:list --status=enabled | grep j_navi_entity_clone
```

Expected output: `J Navi Entity Clone (j_navi_entity_clone)     Enabled`

### Check Routes are Available
```bash
drush route:get j_navi_entity_clone.settings
```

Should display the route information.

### Check Services are Registered
```bash
drush debug:container j_navi_entity_clone.clone_service
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
   drush pm:uninstall j_navi_entity_clone -y
   ```

2. Remove the module directory:
   ```bash
   rm -rf /path/to/drupal/web/modules/custom/j_navi_entity_clone
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
   rm -rf /path/to/drupal/web/modules/custom/j_navi_entity_clone
   cp -r j_navi_entity_clone /path/to/drupal/web/modules/custom/
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
2. Configure your mappings at `/admin/config/content/j-navi-entity-clone`
3. Set up appropriate permissions for your user roles
4. Test with sample content before using in production
5. Monitor logs at `/admin/reports/dblog` for any issues
