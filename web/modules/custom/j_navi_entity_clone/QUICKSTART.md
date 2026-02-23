# J Navi Entity Clone - Quick Start Guide

## What This Module Does

J Navi Entity Clone allows you to clone Drupal entities (nodes and taxonomy terms) from one type to another with configurable field mappings. Perfect for:
- Migrating content between content types
- Creating content variations
- Converting taxonomy terms to nodes (or vice versa)
- Cloning multilingual content with all translations

## 5-Minute Setup

### 1. Install the Module

**Via Drush:**
```bash
cd /path/to/drupal
cp -r j_navi_entity_clone web/modules/custom/
drush en j_navi_entity_clone -y
drush cr
```

**Via UI:**
- Upload to `/modules/custom/j_navi_entity_clone`
- Visit Admin > Extend
- Enable "J Navi Entity Clone"

### 2. Set Permissions

Visit **Admin > People > Permissions** (`/admin/people/permissions`)

Give appropriate roles these permissions:
- ✅ **Administer J Navi Entity Clone** (for admins)
- ✅ **Clone Entities** (for content editors)

### 3. Configure Your First Mapping

Visit **Admin > Config > Content > J Navi Entity Clone** (`/admin/config/content/j-navi-entity-clone`)

**Example: Clone Article to Page**

1. **Source Entity Type:** Content (Node)
2. **Source Bundle:** Article
3. **Target Entity Type:** Content (Node)
4. **Target Bundle:** Basic Page

5. **Field Mappings** (will auto-populate):
   - Title → Title
   - Body → Body
   - Field Image → Field Image
   - Field Tags → (select appropriate field or "Do not map")

6. Click **Save configuration**

### 4. Clone Your First Entity

**Method A - From Edit Form:**
1. Edit any Article node
2. Click **"Clone to Another Type"** button
3. View the newly created (unpublished) Page node

**Method B - From Content List:**
1. Go to Admin > Content
2. Find an Article
3. Click Operations dropdown → **"Clone to another type"**

## Common Scenarios

### Scenario 1: Clone Articles to Blog Posts

```yaml
Source: node -> article
Target: node -> blog_post
Mappings:
  title -> title
  body -> body
  field_image -> field_featured_image
  field_tags -> field_categories
```

### Scenario 2: Clone Taxonomy Terms to Content

```yaml
Source: taxonomy_term -> product_category
Target: node -> product_category_page
Mappings:
  name -> title
  description -> body
```

### Scenario 3: Clone with Paragraphs

The module automatically handles paragraphs:
- Source has paragraph field → Target has paragraph field
- All paragraphs are CLONED (not referenced)
- Nested paragraphs are recursively cloned
- Each cloned entity gets its own paragraph copies

### Scenario 4: Multilingual Content

For sites with multiple languages:
- Enable "Clone all translations" in mapping configuration
- Clone will include all language versions
- Each translation maintains language-specific content
- Perfect for content type migrations on multilingual sites

## Important Notes

✓ **Cloned entities are UNPUBLISHED** - You must review and publish them manually

✓ **Paragraphs are CLONED** - Full copies are created, not references

✓ **Taxonomy terms are REFERENCED** - Same term is used in both entities

✓ **Field type conversion** - The module tries intelligent conversion between compatible field types

✗ **Not cloned**: Workflows, menu links, URL aliases, comments

## Troubleshooting

**Q: Clone button doesn't appear?**
- Check you have "clone entities" permission
- Verify a mapping exists for this entity type/bundle
- Ensure the mapping is enabled

**Q: Some fields didn't copy?**
- Check field types are compatible
- Review logs at Admin > Reports > Recent log messages
- Verify field mappings are configured

**Q: Paragraphs not working?**
- Install and enable the Paragraphs module: `drush en paragraphs -y`
- Ensure both source and target fields are paragraph reference fields

## File Structure

```
j_navi_entity_clone/
├── j_navi_entity_clone.info.yml          # Module definition
├── j_navi_entity_clone.module            # Hooks and form alterations
├── j_navi_entity_clone.routing.yml       # URL routes
├── j_navi_entity_clone.permissions.yml   # Permission definitions
├── j_navi_entity_clone.services.yml      # Service container
├── j_navi_entity_clone.links.menu.yml   # Admin menu links
├── README.md                             # Full documentation
├── INSTALL.md                            # Installation guide
├── CHANGELOG.md                          # Version history
├── config/
│   ├── install/
│   │   └── j_navi_entity_clone.settings.yml
│   └── schema/
│       └── j_navi_entity_clone.schema.yml
└── src/
    ├── Controller/
    │   └── EntityCloneController.php     # Handles clone routing
    ├── Form/
    │   ├── EntityCloneMapperConfigForm.php  # Main config form
    │   └── CloneSelectionForm.php        # Multi-mapping selection
    └── Service/
        └── EntityCloneService.php        # Core cloning logic
```

## Advanced Usage

### Programmatic Cloning

```php
$clone_service = \Drupal::service('j_navi_entity_clone.clone_service');
$node = \Drupal\node\Entity\Node::load(123);

$mapping = [
  'source_entity_type' => 'node',
  'source_bundle' => 'article',
  'target_entity_type' => 'node',
  'target_bundle' => 'page',
  'field_mappings' => [
    'title' => 'title',
    'body' => 'body',
  ],
];

$cloned = $clone_service->cloneEntity($node, $mapping);
```

## Getting Help

- 📖 Read the full [README.md](README.md)
- 📋 Check [INSTALL.md](INSTALL.md) for installation details
- 📝 Review [CHANGELOG.md](CHANGELOG.md) for version info
- 🐛 Check Drupal logs: Admin > Reports > Recent log messages

## Module Compatibility

- ✅ Drupal 11
- ✅ Node module (core)
- ✅ Taxonomy module (core)
- ✅ Field module (core)
- ✅ Paragraphs module (optional, for paragraph cloning)

## What's Next?

1. Configure additional mappings for other content types
2. Set up bulk cloning workflows
3. Integrate with your content migration strategy
4. Train content editors on the clone feature

---

**Ready to start cloning? Visit the configuration page:**
`/admin/config/content/j-navi-entity-clone`
