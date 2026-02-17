# Multilingual Support - What's New

## Summary

The Entity Clone Mapper module now includes comprehensive multilingual support! You can now clone entities with all their translations or select specific language versions.

## New Features

### 1. Clone All Translations Option
- New checkbox in mapping configuration: "Clone all translations"
- When enabled, users can clone all language versions at once
- Each translation is created in unpublished state for review

### 2. Language Selection Interface
- New form for selecting which language to clone
- Shows all available translations
- Option to clone single language or all (when enabled)
- Clear UI with helpful information messages

### 3. Translation-Aware Field Mapping
- Translatable fields are cloned for each language
- Non-translatable fields are shared across translations
- Language context maintained throughout the clone process

### 4. Paragraph Translation Support
- Translatable paragraphs are cloned per language
- Non-translatable paragraphs are shared
- Recursive handling of nested paragraph translations

### 5. Content Translation Metadata
- Proper metadata set for all translations
- Source language tracking
- Translation status management
- Correct timestamps and authorship

## Files Added/Modified

### New Files
- `src/Form/CloneLanguageSelectionForm.php` - Language selection UI
- `MULTILINGUAL.md` - Comprehensive multilingual documentation

### Modified Files
- `src/Service/EntityCloneService.php` - Added multilingual cloning logic
- `src/Controller/EntityCloneController.php` - Added language selection flow
- `src/Form/EntityCloneMapperConfigForm.php` - Added translation option
- `entity_clone_mapper.services.yml` - Added language manager dependency
- `config/schema/entity_clone_mapper.schema.yml` - Added translation config
- `entity_clone_mapper.info.yml` - Added language dependencies
- `README.md` - Added multilingual documentation
- `CHANGELOG.md` - Updated with multilingual features
- `QUICKSTART.md` - Added multilingual examples
- `TECHNICAL.md` - (would need updating with technical details)

## How to Use

### Configuration
1. Navigate to `/admin/config/content/entity-clone-mapper`
2. Create or edit a mapping
3. Check "Clone all translations" to enable multilingual support
4. Save configuration

### Cloning Process
1. Edit an entity with multiple translations
2. Click "Clone to Another Type"
3. Select language options:
   - **Single language**: Choose which translation to clone
   - **All translations**: Clone everything at once
4. Review and publish cloned translations

## Technical Implementation

### Service Layer Changes
```php
// New parameters for multilingual support
public function cloneEntity(
  EntityInterface $source_entity, 
  array $mapping, 
  $language_code = NULL  // New parameter
)
```

### New Methods
- `cloneTranslations()` - Handles cloning all translations
- `mapFieldsForLanguage()` - Language-aware field mapping
- Enhanced `cloneParagraph()` - Supports language parameter

### Configuration Schema
```yaml
clone_all_translations:
  type: boolean
  label: 'Clone all translations'
```

## Benefits

✅ **Content Migration**: Easily migrate multilingual content between types  
✅ **Workflow Efficiency**: Clone all translations in one operation  
✅ **Data Integrity**: Maintains language-specific content accurately  
✅ **Flexibility**: Choose to clone specific languages or all  
✅ **Review Process**: All translations unpublished for review  

## Requirements

For multilingual cloning:
- Language module (core) - enabled
- Content Translation module (core) - enabled  
- At least one additional language configured
- Source and target bundles must be translatable
- Content must have translations available

## Use Cases

1. **Content Type Restructuring on Multilingual Sites**
   - Migrate all language versions from "Article" to "Blog Post"
   - Preserve all translations and language-specific content

2. **Multi-language Product Catalogs**
   - Clone product variations with all translations
   - Maintain language-specific descriptions and details

3. **Selective Language Migration**
   - Clone only specific language versions
   - Useful for regional content management

4. **Translation Workflow Integration**
   - Clone base language first
   - Add translations through normal workflow
   - Or clone all at once for bulk operations

## Compatibility

- ✅ Drupal 11
- ✅ Content Translation (core)
- ✅ Language module (core)
- ✅ Paragraphs module (with translation support)
- ✅ Taxonomy with translations
- ✅ All standard Drupal field types

## Documentation

Full documentation available in:
- `MULTILINGUAL.md` - Comprehensive guide
- `README.md` - General usage with multilingual section
- `QUICKSTART.md` - Quick examples
- `CHANGELOG.md` - Version history

## Testing Recommendations

Before deploying to production:

1. **Enable Content Translation**
   - Configure at least 2 languages
   - Make test content types translatable
   - Create test content with translations

2. **Test Single Language Clone**
   - Clone one language version
   - Verify field values are correct
   - Check language metadata

3. **Test All Translations Clone**
   - Enable "Clone all translations"
   - Clone entity with 2+ translations
   - Verify all languages created
   - Check each translation's content

4. **Test with Paragraphs**
   - Create content with translatable paragraphs
   - Clone with translations
   - Verify paragraph translations cloned correctly

5. **Test Field Types**
   - Translatable text fields
   - Non-translatable fields
   - Entity references
   - Complex field structures

## Migration from Previous Version

If updating from a version without multilingual support:

1. **No Data Loss**: Existing mappings will work as before
2. **New Option**: "Clone all translations" defaults to FALSE
3. **Backward Compatible**: Single-language cloning unchanged
4. **No Configuration Changes Required**: Module works immediately

## Known Limitations

1. **Performance**: Cloning many translations can be resource-intensive
2. **Batch Processing**: Not yet available (planned for future release)
3. **Interface Translation**: Only entity content is cloned, not interface translations
4. **Menu Translations**: Menu links are not cloned with translations

## Future Enhancements

Planned for future releases:
- Batch processing for bulk multilingual cloning
- Drush commands for CLI-based multilingual operations
- Translation workflow integration
- Selective field translation cloning
- Translation conflict resolution

## Support

For issues or questions about multilingual cloning:
- Review `MULTILINGUAL.md` documentation
- Check Drupal logs: `/admin/reports/dblog`
- Verify Content Translation is properly configured
- Test with simple content first

---

**Multilingual support is now a core feature of Entity Clone Mapper!**

Enjoy seamless multilingual content cloning! 🌍
