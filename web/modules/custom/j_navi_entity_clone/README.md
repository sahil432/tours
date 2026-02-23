# J Navi Entity Clone

A Drupal 11 module that allows cloning entities from one entity type/bundle to another with configurable field mapping support.

## Features

- **Clone Between Entity Types**: Clone content nodes to other content types or taxonomy terms to other vocabularies
- **Field Mapping Configuration**: Configure which source fields map to which target fields via an admin UI
- **Paragraph Support**: Automatically clones paragraph entities (including nested paragraphs)
- **Taxonomy Reference Support**: Maintains taxonomy term references when cloning
- **Multilingual Support**: Clone entities with all their translations or select specific languages
- **Unpublished State**: Cloned entities are created in unpublished state for review
- **Dynamic Field Detection**: Fields are automatically detected and populated in the configuration form
- **Multiple Mapping Support**: Configure multiple clone mappings for different source/target combinations
- **Smart Clone Button**: Displays dynamic button text showing the target entity name (e.g., "Clone to Blog Post" instead of generic text)

## Requirements

- Drupal 11
- Node module (core)
- Taxonomy module (core)
- Field module (core)
- (Optional) Paragraphs module for paragraph field cloning

## Installation

1. Copy the entire `j_navi_entity_clone` directory to your Drupal installation's `modules/custom/` directory.

2. Enable the module:
   ```bash
   drush en j_navi_entity_clone
   ```
   
   Or via the UI: Admin > Extend, find "J Navi Entity Clone" and enable it.

3. Clear cache:
   ```bash
   drush cr
   ```

## Configuration

1. Navigate to **Admin > Configuration > Content > J Navi Entity Clone** (`/admin/config/content/j-navi-entity-clone`)

2. Configure a mapping:
   - **Source Entity Type**: Select the entity type you want to clone from (Node or Taxonomy Term)
   - **Source Bundle**: Select the specific content type or vocabulary
   - **Target Entity Type**: Select the entity type you want to clone to
   - **Target Bundle**: Select the target content type or vocabulary
   
3. Configure field mappings:
   - Once source and target bundles are selected, all available fields will be displayed
   - For each source field, select which target field it should map to
   - Select "- Do not map -" to skip a field
   - The system automatically handles field type conversions where possible

4. Configure multilingual options:
   - Check **"Clone all translations"** to clone all available language versions
   - When enabled, the cloned entity will include all translations from the source
   - Both source and target bundles must be translatable for this to work
   - Each translation is created in unpublished state

5. Add additional mappings by clicking "Add another mapping"

6. Save the configuration

## Usage

### Method 1: From Entity Edit Form

1. Edit a node or taxonomy term that has a clone mapping configured
2. Click the **"Clone to Another Type"** button in the actions area
3. If multiple mappings exist, select the desired target type
4. The cloned entity will be created and you'll be redirected to its edit form

### Method 2: From Entity Operations

1. On entity listing pages (content overview, taxonomy term overview)
2. Find the entity you want to clone
3. Click **"Clone to another type"** from the operations dropdown
4. Follow the same process as above

### Multilingual Cloning

For entities with multiple translations:

1. **Single Language Clone**: 
   - Select which language version to clone from the language selection screen
   - Only that translation will be created in the target entity

2. **All Translations Clone** (if enabled in mapping configuration):
   - Select "Clone all translations" option
   - All language versions will be cloned to the target entity
   - Each translation maintains its language-specific field values
   - All translations are created in unpublished state

3. **Language Selection**:
   - If the source entity has multiple translations, you'll be prompted to select
   - Choose a specific language or clone all (if enabled)
   - The base language determines the entity's default language

## Field Mapping Behavior

### Supported Field Types

- **Text fields**: string, string_long, text, text_long, text_with_summary
- **Number fields**: integer, decimal, float
- **Boolean fields**: boolean
- **Date fields**: datetime, timestamp
- **Entity reference**: taxonomy_term, user, node
- **Entity reference revisions**: paragraph (with full cloning support)
- **Link fields**: link
- **Email fields**: email
- **Telephone fields**: telephone

### Special Handling

#### Paragraphs
- Paragraphs are **fully cloned** (not referenced)
- Nested paragraphs are cloned recursively
- Each cloned entity gets its own copy of all paragraph data
- **Multilingual**: Paragraph translations are cloned when cloning all translations

#### Taxonomy Terms
- Taxonomy terms are **referenced** (not cloned)
- The same term is referenced in the cloned entity
- Only works if the target field accepts the term's vocabulary

#### Other Entity References
- Other entity references (users, nodes) are maintained as references
- The cloned entity will reference the same entities as the source

#### Multilingual Fields
- **Translatable fields**: Each translation's values are cloned separately
- **Non-translatable fields**: Only copied once to the base entity
- **Language-specific content**: Preserved in each translation
- **Content Translation metadata**: Set appropriately for each translation

### Field Type Conversion

The module attempts intelligent field type conversion:
- Text fields with different formats are converted (e.g., text_long to string)
- The main value is extracted and format/summary preserved when possible
- Incompatible field types are logged and skipped

## Permissions

The module provides two permissions:

1. **Administer J Navi Entity Clone**: Access to configuration form
2. **Clone Entities**: Ability to clone entities using the configured mappings

Assign these permissions at **Admin > People > Permissions**

## Cloned Entity Behavior

All cloned entities:
- Are created in **unpublished/inactive state** (status = 0)
- Are owned by the user who performed the clone operation
- Have new creation and modification timestamps
- Have new UUIDs and revision IDs
- Do NOT copy: workflows, menu links, URL aliases, or comments

## Example Use Cases

1. **Content Type Migration**: Clone "Article" nodes to "Blog Post" nodes during a site restructure
2. **Multi-language Workflows**: Clone content to a different type for translation workflows
3. **Content Variations**: Create product variations from a master product content type
4. **Taxonomy to Content**: Convert taxonomy terms into full content nodes
5. **Content to Taxonomy**: Extract data from nodes into taxonomy terms
6. **Multilingual Content Migration**: Migrate all language versions from one content type to another while preserving translations

## Troubleshooting

### Clone button doesn't appear
- Ensure you have "clone entities" permission
- Check that a mapping is configured for this entity type/bundle
- Verify the mapping is enabled in the configuration

### Fields not mapping correctly
- Check the field types are compatible
- Review logs at **Admin > Reports > Recent log messages** for detailed error messages
- Some field types may require custom handling

### Paragraphs not cloning
- Ensure the Paragraphs module is installed and enabled
- Verify both source and target fields are entity_reference_revisions type
- Check that both reference the paragraph entity type

### Translations not cloning
- Verify "Clone all translations" is enabled in the mapping configuration
- Ensure both source and target bundles are translatable
- Check that Content Translation module is enabled
- Verify the entity has translations available

## Development

### Service Usage

You can use the clone service programmatically:

```php
// Get the service.
$clone_service = \Drupal::service('j_navi_entity_clone.clone_service');

// Load an entity.
$node = \Drupal\node\Entity\Node::load(123);

// Define mapping.
$mapping = [
  'source_entity_type' => 'node',
  'source_bundle' => 'article',
  'target_entity_type' => 'node',
  'target_bundle' => 'page',
  'field_mappings' => [
    'title' => 'title',
    'body' => 'body',
    'field_tags' => 'field_categories',
  ],
];

// Clone the entity.
$cloned_entity = $clone_service->cloneEntity($node, $mapping);
```

### Extending the Module

To add custom field mapping logic:

1. Create a service that extends or decorates `EntityCloneService`
2. Override the `mapField()` method to add custom handling
3. Register your service in `j_navi_entity_clone.services.yml`

## Module Files Structure

```
j_navi_entity_clone/
├── j_navi_entity_clone.info.yml
├── j_navi_entity_clone.module
├── j_navi_entity_clone.routing.yml
├── j_navi_entity_clone.permissions.yml
├── j_navi_entity_clone.links.menu.yml
├── j_navi_entity_clone.services.yml
├── README.md
└── src/
    ├── Controller/
    │   └── EntityCloneController.php
    ├── Form/
    │   ├── EntityCloneMapperConfigForm.php
    │   └── CloneSelectionForm.php
    └── Service/
        └── EntityCloneService.php
```

## Support

For bug reports and feature requests, please use the issue queue on the project page.

## License

GPL-2.0-or-later

## Author

Custom development module
