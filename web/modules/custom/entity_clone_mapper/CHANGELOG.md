# Changelog

All notable changes to the Entity Clone Mapper module will be documented in this file.

## [1.0.0] - 2026-02-10

### Added
- Initial release of Entity Clone Mapper module for Drupal 11
- Configuration form for managing entity clone mappings
- Support for cloning nodes between different content types
- Support for cloning taxonomy terms between different vocabularies
- Dynamic field mapping with auto-population based on entity bundles
- Full paragraph cloning support (including nested paragraphs)
- Taxonomy term reference preservation
- **Multilingual support with translation cloning**
- **Language selection interface for translatable entities**
- **Option to clone all translations or specific language**
- **Support for translatable paragraphs**
- Clone button on entity edit forms
- Clone operation in entity operations dropdown
- Multi-mapping support (multiple source/target combinations)
- Unpublished state for all cloned entities
- Intelligent field type conversion
- Comprehensive logging of clone operations
- Permission system (administer and clone permissions)
- Service-based architecture for programmatic usage

### Features
- Clone from Node to Node (different content types)
- Clone from Taxonomy Term to Taxonomy Term (different vocabularies)
- Clone from Node to Taxonomy Term
- Clone from Taxonomy Term to Node
- Recursive paragraph cloning
- **Clone all translations or select specific language**
- **Translatable field support**
- **Language-aware paragraph cloning**
- Field mapping UI with dropdown selection
- AJAX-enabled configuration form
- Multiple mapping configurations per site
- Per-mapping enable/disable toggle
- Per-mapping translation cloning toggle
- Automatic field type detection and conversion

### Technical Details
- Compatible with Drupal 11
- PSR-4 autoloading
- Dependency injection
- Configuration entity storage
- Schema validation
- Service container registration
- Route system integration
- Permission system integration
- Hook system integration

### Documentation
- Complete README with usage instructions
- Installation guide (INSTALL.md)
- Inline code documentation
- Example use cases
- Troubleshooting guide
- Development guidelines

## [Unreleased]

### Planned Features
- Support for additional entity types (Media, Commerce Products, etc.)
- Batch processing for bulk cloning
- Clone scheduling via Drupal cron
- Field transformation rules (beyond simple mapping)
- Clone templates (pre-configured mappings)
- Import/export of mapping configurations
- Drush commands for CLI-based cloning
- API for third-party module integration
- Clone history tracking
- Revert/undo cloning operations

### Known Issues
- None reported

### Future Enhancements
- UI improvements for better UX
- Performance optimization for large content sets
- Support for custom field types
- Conditional field mapping based on field values
- Multi-site configuration sharing
