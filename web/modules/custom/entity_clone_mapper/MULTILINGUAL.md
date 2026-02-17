# Multilingual Support Documentation

## Overview

Entity Clone Mapper provides comprehensive multilingual support, allowing you to clone entities with all their translations or select specific language versions. This is particularly useful for content migration, multilingual workflows, and content type restructuring in multilingual Drupal sites.

## Requirements

For multilingual cloning to work, you need:

1. **Content Translation** module enabled (core)
2. **Language** module enabled (core)
3. At least one additional language configured on your site
4. Source and target bundles configured as translatable
5. Content with translations available

## Configuration

### Enable Multilingual Support for a Mapping

1. Navigate to **Admin > Configuration > Content > Entity Clone Mapper**
2. Create or edit a mapping
3. Ensure both source and target bundles are translatable content types/vocabularies
4. Check the **"Clone all translations"** checkbox
5. Save the configuration

When this option is enabled:
- Users can choose to clone a single language or all translations
- All translatable fields will be cloned for each language
- Non-translatable fields are only copied once to the base entity
- Content Translation metadata is set appropriately

### Configuring Translatable Content Types

Before cloning multilingual content, ensure your content types are properly configured:

1. **Enable Content Translation for Content Type**:
   - Navigate to **Admin > Structure > Content types**
   - Edit your content type
   - Go to the **Language settings** tab
   - Check "Enable translation"
   - Configure default language settings
   - Save

2. **Configure Field Translatability**:
   - Go to **Admin > Structure > Content types > [Your type] > Manage fields**
   - Edit each field
   - In the field settings, check "Users may translate this field"
   - Save the field

## How It Works

### Single Language Cloning

When cloning a single language version:

1. User selects the entity to clone
2. Language selection form appears (if entity has multiple translations)
3. User selects desired language
4. Only that language version is cloned
5. Cloned entity is created with the selected language as default

**Example Flow**:
```
Source Entity (Article):
├── English (en) - Title: "Hello World"
├── French (fr) - Title: "Bonjour le Monde"
└── Spanish (es) - Title: "Hola Mundo"

User selects: French (fr)

Result (Blog Post):
└── French (fr) - Title: "Bonjour le Monde"
```

### All Translations Cloning

When "Clone all translations" is enabled in the mapping:

1. User selects the entity to clone
2. User chooses "Clone all translations" option
3. Base language is cloned first
4. Each additional translation is cloned
5. All translations are created in unpublished state

**Example Flow**:
```
Source Entity (Article):
├── English (en) - Title: "Hello World", Body: "Welcome..."
├── French (fr) - Title: "Bonjour le Monde", Body: "Bienvenue..."
└── Spanish (es) - Title: "Hola Mundo", Body: "Bienvenido..."

User selects: Clone all translations

Result (Blog Post):
├── English (en) - Title: "Hello World", Body: "Welcome..." [unpublished]
├── French (fr) - Title: "Bonjour le Monde", Body: "Bienvenue..." [unpublished]
└── Spanish (es) - Title: "Hola Mundo", Body: "Bienvenido..." [unpublished]
```

## Field Translation Handling

### Translatable Fields

Fields marked as translatable are cloned for each language:

- **Text fields**: Each translation's text is preserved
- **Rich text fields**: Formatting and content maintained per language
- **Entity references**: Can reference different entities per language (if configured)
- **Images/Files**: Can be different per translation

### Non-Translatable Fields

Fields that are not translatable are only cloned once:

- Values set on the base entity
- Shared across all translations
- Examples: Created date, author, status flags

### Special Field Types

#### Paragraphs
- Paragraphs can be translatable or non-translatable
- **Translatable paragraphs**: Each language version is cloned separately
- **Non-translatable paragraphs**: Cloned once and shared across translations
- Nested paragraph translations are handled recursively

#### Taxonomy Terms
- Term references are maintained per translation
- If a field allows different terms per language, those differences are preserved
- The actual taxonomy terms are not cloned, only referenced

#### Entity References
- Reference fields can be translatable or shared
- Translatable references: Each language can reference different entities
- Non-translatable references: All translations share the same reference

## Content Translation Metadata

The module properly handles Content Translation metadata:

- `content_translation_source`: Set to base language for translations
- `content_translation_outdated`: Set to FALSE for all cloned translations
- `content_translation_status`: Set to 0 (unpublished) for all translations
- `content_translation_uid`: Set to current user
- `content_translation_created`: Set to current timestamp
- `content_translation_changed`: Set to current timestamp

## User Interface

### Language Selection Screen

When cloning a multilingual entity, users see:

**Single Translation Mode**:
- Dropdown to select which language to clone
- Information about available languages
- Clear indication of which version will be cloned

**All Translations Mode** (if enabled):
- Radio buttons: "Clone a single language" vs "Clone all translations"
- Language selector (shown when "single language" is selected)
- Information message showing how many translations will be cloned
- Warning if "Clone all translations" is not enabled in configuration

## Programmatic Usage

### Clone with Specific Language

```php
$clone_service = \Drupal::service('entity_clone_mapper.clone_service');
$node = \Drupal\node\Entity\Node::load(123);

$mapping = [
  'source_entity_type' => 'node',
  'source_bundle' => 'article',
  'target_entity_type' => 'node',
  'target_bundle' => 'blog_post',
  'clone_all_translations' => FALSE,
  'field_mappings' => [
    'title' => 'title',
    'body' => 'body',
  ],
];

// Clone only French version
$cloned = $clone_service->cloneEntity($node, $mapping, 'fr');
```

### Clone All Translations

```php
$mapping = [
  'source_entity_type' => 'node',
  'source_bundle' => 'article',
  'target_entity_type' => 'node',
  'target_bundle' => 'blog_post',
  'clone_all_translations' => TRUE, // Enable all translations
  'field_mappings' => [
    'title' => 'title',
    'body' => 'body',
  ],
];

// Clone base language and all translations
$cloned = $clone_service->cloneEntity($node, $mapping);
```

## Best Practices

### 1. Plan Your Field Configuration

Before cloning multilingual content:
- Decide which fields should be translatable
- Configure field translatability consistently across content types
- Test with a single entity before bulk operations

### 2. Review Cloned Content

All cloned translations are unpublished:
- Review each translation for accuracy
- Check that field mappings worked correctly
- Verify language-specific content is appropriate
- Publish translations individually after review

### 3. Handle Content Translation Workflow

For sites with translation workflows:
- Clone content before entering translation workflow
- Maintain translation source tracking
- Consider cloning only base language initially
- Add translations through normal workflow

### 4. Performance Considerations

Cloning all translations can be resource-intensive:
- For entities with many translations (5+), consider batch processing
- Monitor memory usage with large paragraph structures
- Use drush for bulk operations (when available)

## Troubleshooting

### Translations Not Appearing

**Problem**: Cloned entity doesn't have all translations

**Solutions**:
1. Verify "Clone all translations" is enabled in mapping
2. Check source entity actually has translations: `drush entity:info node [nid]`
3. Ensure target bundle is translatable
4. Check Content Translation is enabled
5. Review logs for errors: `drush watchdog:show --type=entity_clone_mapper`

### Field Values Missing in Translations

**Problem**: Some fields are empty in translations

**Solutions**:
1. Check if fields are configured as translatable
2. Verify source translations have values for those fields
3. Check field mapping configuration
4. Review field type compatibility

### Paragraph Translations Not Working

**Problem**: Paragraph translations not cloned properly

**Solutions**:
1. Ensure Paragraphs module supports translation
2. Check paragraph type is configured as translatable
3. Verify paragraph field is translatable
4. Test with simple (non-nested) paragraphs first

### Language Mismatch Errors

**Problem**: Errors about language codes

**Solutions**:
1. Verify all referenced languages are enabled on the site
2. Check language configuration at **Admin > Configuration > Regional > Languages**
3. Ensure content is associated with valid language codes
4. Review entity language settings

## Advanced Scenarios

### Mixing Translatable and Non-Translatable Fields

When cloning entities with both types of fields:

1. **Base entity creation**: Non-translatable fields set here
2. **Translation addition**: Only translatable fields are populated
3. **Result**: Consistent non-translatable values across all languages

Example:
```
Source Article (translatable + non-translatable):
├── Published: TRUE (non-translatable)
├── Author: User 1 (non-translatable)
├── English: Title: "News", Body: "Today..." (translatable)
└── French: Title: "Nouvelles", Body: "Aujourd'hui..." (translatable)

Cloned Blog Post:
├── Published: FALSE (non-translatable, set to unpublished)
├── Author: Current User (non-translatable, set to cloner)
├── English: Title: "News", Body: "Today..." (translatable)
└── French: Title: "Nouvelles", Body: "Aujourd'hui..." (translatable)
```

### Complex Paragraph Structures

For paragraphs with multiple levels of nesting and translations:

1. Each level is cloned recursively
2. Language context is maintained throughout
3. Translation relationships are preserved
4. Each cloned paragraph gets a new ID and revision

### Partial Translation Scenarios

When source entity has incomplete translations:

1. Only available translations are cloned
2. Missing translations are not created
3. User can add missing translations later
4. Base language is always cloned

## Migration Strategies

### Strategy 1: Staged Migration

For large multilingual sites:

1. Clone base language first (disable "Clone all translations")
2. Review and publish base language content
3. Enable "Clone all translations"
4. Re-clone or add translations separately

### Strategy 2: Complete Migration

For smaller sites or urgent migrations:

1. Enable "Clone all translations"
2. Clone everything at once
3. Review all translations
4. Publish when ready

### Strategy 3: Language-Specific Migration

For targeting specific languages:

1. Keep "Clone all translations" disabled
2. Clone each language separately
3. Choose target language each time
4. Useful for selective content migration

## API Reference

### Service Methods

#### cloneEntity()

```php
/**
 * Clone an entity based on mapping configuration.
 *
 * @param \Drupal\Core\Entity\EntityInterface $source_entity
 *   The source entity to clone.
 * @param array $mapping
 *   The mapping configuration.
 * @param string|null $language_code
 *   Optional language code. If provided, clones specific translation.
 *   If NULL, clones current language or all translations based on config.
 *
 * @return \Drupal\Core\Entity\EntityInterface
 *   The cloned entity (base language version).
 */
public function cloneEntity(EntityInterface $source_entity, array $mapping, $language_code = NULL);
```

### Configuration Schema

```yaml
clone_all_translations:
  type: boolean
  label: 'Clone all translations'
  default: false
  description: 'When enabled, all available translations will be cloned'
```

## See Also

- [README.md](README.md) - General module documentation
- [TECHNICAL.md](TECHNICAL.md) - Technical specifications
- Drupal Content Translation: https://www.drupal.org/docs/8/multilingual
