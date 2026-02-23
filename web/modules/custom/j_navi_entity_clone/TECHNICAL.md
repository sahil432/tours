# J Navi Entity Clone - Technical Specification

## Module Information

**Name:** J Navi Entity Clone  
**Machine Name:** j_navi_entity_clone  
**Version:** 1.0.0  
**Drupal Version:** 11.x  
**Package:** Custom  
**Type:** Module  

## Overview

J Navi Entity Clone is a Drupal 11 module that provides sophisticated entity cloning capabilities with configurable field mappings. It enables site administrators to define relationships between different entity types and bundles, allowing content editors to clone entities across types while maintaining data integrity and field relationships.

## Architecture

### Design Pattern
The module follows Drupal 11 best practices with:
- Service-oriented architecture
- Dependency injection
- Configuration management via Config API
- PSR-4 autoloading
- Separation of concerns

### Core Components

1. **Configuration Layer**
   - `EntityCloneMapperConfigForm`: Admin UI for managing mappings
   - Configuration entity storage for persistent mappings
   - Schema validation for data integrity

2. **Service Layer**
   - `EntityCloneService`: Core business logic for cloning
   - Field mapping engine
   - Recursive paragraph cloning
   - Entity reference handling

3. **Controller Layer**
   - `EntityCloneController`: Routing and request handling
   - Mapping selection logic
   - User feedback and redirects

4. **Form Layer**
   - `CloneSelectionForm`: Multi-mapping selection interface
   - AJAX-enhanced user experience

5. **Integration Layer**
   - Hook implementations for entity operations
   - Form alterations for clone buttons
   - Permission system integration

## Technical Features

### Entity Support

| Entity Type | Source | Target | Status |
|------------|--------|--------|--------|
| Node | ✅ | ✅ | Fully supported |
| Taxonomy Term | ✅ | ✅ | Fully supported |
| Media | ❌ | ❌ | Planned |
| User | ❌ | ❌ | Not planned |
| Paragraph | ✅ (via clone) | ✅ (via clone) | Supported as field |

### Field Type Compatibility

| Field Type | Direct Copy | Conversion | Notes |
|-----------|-------------|------------|-------|
| Text (string) | ✅ | ✅ | Auto-converts between text types |
| Text (long) | ✅ | ✅ | Preserves format when possible |
| Text (formatted) | ✅ | ✅ | Maintains text format |
| Number | ✅ | ❌ | Type must match exactly |
| Boolean | ✅ | ❌ | Direct copy only |
| Date/Time | ✅ | ❌ | Direct copy only |
| Email | ✅ | ✅ | Can convert to text |
| Telephone | ✅ | ✅ | Can convert to text |
| Link | ✅ | ❌ | Maintains URL and title |
| Entity Reference | ✅ | ✅ | If target types match |
| Paragraph Reference | ✅ (clone) | ✅ (clone) | Full recursive cloning |
| File/Image | ✅ | ✅ | References same file |
| Taxonomy Term Ref | ✅ | ✅ | Preserves term references |

### Cloning Behavior

#### Standard Fields
- **Cloned:** Title, body, custom fields (as mapped)
- **Generated New:** UUID, ID, revision ID, created timestamp, changed timestamp
- **Preserved:** Author (current user), published status (set to unpublished)
- **Not Cloned:** Menu links, URL aliases, workflows, comments, votes

#### Paragraph Fields
- **Behavior:** Complete deep cloning
- **Process:**
  1. Source paragraph is duplicated
  2. New paragraph entity created
  3. Nested paragraphs recursively cloned
  4. Target entity references the new paragraph
- **Result:** Independent copy with no shared data

#### Entity Reference Fields
- **Taxonomy Terms:** Referenced (not cloned)
- **Nodes:** Referenced (not cloned)
- **Users:** Referenced (not cloned)
- **Files/Images:** Referenced (not cloned)

### Configuration Schema

```yaml
mappings:
  - enabled: boolean
    source_entity_type: string  # 'node' | 'taxonomy_term'
    source_bundle: string       # content type | vocabulary
    target_entity_type: string  # 'node' | 'taxonomy_term'
    target_bundle: string       # content type | vocabulary
    field_mappings:
      source_field_name: target_field_name
```

## API Reference

### Services

#### j_navi_entity_clone.clone_service

```php
\Drupal::service('j_navi_entity_clone.clone_service')
```

**Methods:**

```php
/**
 * Clone an entity based on mapping configuration.
 *
 * @param \Drupal\Core\Entity\EntityInterface $source_entity
 *   The source entity to clone.
 * @param array $mapping
 *   The mapping configuration array.
 *
 * @return \Drupal\Core\Entity\EntityInterface
 *   The cloned entity.
 *
 * @throws \Exception
 *   If cloning fails.
 */
public function cloneEntity(EntityInterface $source_entity, array $mapping);
```

### Routes

| Route Name | Path | Purpose |
|-----------|------|---------|
| j_navi_entity_clone.settings | /admin/config/content/j-navi-entity-clone | Configuration form |
| j_navi_entity_clone.clone_entity | /j-navi-entity-clone/clone/{entity_type}/{entity} | Clone handler |

### Permissions

| Permission | Machine Name | Description |
|-----------|--------------|-------------|
| Administer J Navi Entity Clone | administer entity clone mapper | Access configuration |
| Clone Entities | clone entities | Use clone functionality |

### Hooks

#### Implemented Hooks

- `hook_entity_operation()`: Adds clone operation to entity lists
- `hook_form_alter()`: Adds clone button to edit forms

## Database Impact

### Configuration Storage
All mappings stored in configuration system:
- File: `j_navi_entity_clone.settings`
- No custom database tables required
- Exportable via config sync

### Performance Considerations

| Operation | Impact | Mitigation |
|-----------|--------|------------|
| Simple clone (no paragraphs) | Low | Direct field copying |
| Clone with paragraphs | Medium | Recursive cloning, single transaction |
| Clone with many nested paragraphs | Medium-High | Batching recommended for bulk ops |
| Config form load | Low | AJAX for field loading |

## Security

### Access Control
- Permission-based access to clone functionality
- Entity access checks before cloning
- User must have update access to source entity
- User automatically becomes owner of cloned entity

### Data Validation
- Configuration schema validation
- Field type compatibility checking
- Entity type validation
- Bundle existence verification

### Audit Trail
- Full logging of clone operations
- Source and target entity IDs logged
- Field mapping execution logged
- Errors logged with context

## Error Handling

### Graceful Degradation
- Missing fields logged but don't block cloning
- Type conversion failures logged and skipped
- Invalid references logged and skipped
- User receives summary of clone operation

### Logging Levels

| Event | Level | Channel |
|-------|-------|---------|
| Successful clone | Info | j_navi_entity_clone |
| Field mapping error | Warning | j_navi_entity_clone |
| Clone failure | Error | j_navi_entity_clone |
| Missing field | Warning | j_navi_entity_clone |

## Testing Recommendations

### Unit Tests
1. Service methods (cloneEntity, mapField, cloneParagraph)
2. Field type conversion logic
3. Configuration validation

### Functional Tests
1. Config form AJAX operations
2. Clone button appearance
3. Permission enforcement
4. End-to-end clone workflow

### Integration Tests
1. Paragraph cloning with nesting
2. Taxonomy reference preservation
3. Cross-entity-type cloning
4. Multi-mapping scenarios

## Extensibility

### Custom Field Type Support

Create a custom service that decorates the clone service:

```php
<?php

namespace Drupal\my_module\Service;

use Drupal\j_navi_entity_clone\Service\EntityCloneService;

class CustomCloneService extends EntityCloneService {

  protected function mapField(FieldItemListInterface $source_field, EntityInterface $target_entity, $target_field_name) {
    $field_type = $source_field->getFieldDefinition()->getType();
    
    // Handle custom field type.
    if ($field_type === 'my_custom_field') {
      // Custom mapping logic here.
      return;
    }
    
    // Fallback to parent.
    parent::mapField($source_field, $target_entity, $target_field_name);
  }
}
```

### Event Integration (Future)

Planned event dispatching for:
- Pre-clone validation
- Post-clone processing
- Field mapping customization
- Batch operation hooks

## Performance Optimization

### Current Optimizations
- Single database transaction per clone
- Lazy loading of entity references
- Field definition caching
- Configuration caching

### Future Optimizations
- Batch API for bulk cloning
- Queue integration for async cloning
- Field mapping cache
- Paragraph clone optimization

## Dependencies

### Required
- drupal:core (>= 11.0)
- drupal:node (core)
- drupal:taxonomy (core)
- drupal:field (core)

### Optional
- paragraphs:paragraphs (for paragraph cloning)

### No Conflicts
Module is designed to be compatible with:
- Entity Clone module (different use case)
- Node Clone module (different approach)
- Content moderation
- Workflows
- Translation modules

## Upgrade Path

### From Future Versions
Configuration will be automatically updated via update hooks.

### To Future Versions
Config schema ensures forward compatibility.

## Known Limitations

1. **Entity Types:** Currently limited to nodes and taxonomy terms
2. **Batch Operations:** No built-in UI for bulk cloning (use Drush in future)
3. **Field Transformation:** Basic conversion only, no complex transformations
4. **Revisions:** Creates new revision, doesn't maintain revision history
5. **Translation:** Doesn't handle multilingual content (planned)

## Roadmap

### Version 1.1 (Planned)
- Drush commands
- Batch processing UI
- Additional entity type support

### Version 1.2 (Planned)
- Field transformation rules
- Clone templates
- API events/hooks

### Version 2.0 (Planned)
- Multi-site config sharing
- Scheduling and automation
- Clone history tracking
- Translation support

## Support and Contribution

### Issue Reporting
- Check logs first
- Provide entity type, bundle, and field info
- Include error messages
- Describe expected vs actual behavior

### Development Setup
1. Clone repository
2. Place in `/modules/custom/`
3. Enable module
4. Configure test mappings
5. Run tests

## Conclusion

J Navi Entity Clone provides a robust, extensible solution for entity cloning in Drupal 11. Its service-oriented architecture, comprehensive field type support, and intelligent handling of complex field types like paragraphs make it suitable for a wide range of content migration and transformation scenarios.
