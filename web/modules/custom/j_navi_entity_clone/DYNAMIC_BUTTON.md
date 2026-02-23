# Dynamic Button Text Feature

## Overview

The clone button now displays the target entity type name instead of generic text "Clone to Another Type".

## Button Text Examples

### Single Target Mapping
When there's only one mapping configured for a content type:

**Example:**
- Source: Article
- Target: Blog Post
- **Button Text:** "Clone to Blog Post"

### Multiple Target Mappings (2 targets)
When there are two mappings configured:

**Example:**
- Source: Article
- Targets: Blog Post, News Item
- **Button Text:** "Clone to Blog Post/News Item"

### Multiple Target Mappings (3+ targets)
When there are three or more mappings configured:

**Example:**
- Source: Article
- Targets: Blog Post, News Item, Press Release
- **Button Text:** "Clone to 3 types"

## Implementation

The button text is dynamically generated based on:
1. The configured mappings for the source entity
2. The target bundle labels (e.g., "Blog Post", "News Item")
3. The number of available target types

## Code Logic

```php
// Single target
t('Clone to @type', ['@type' => 'Blog Post'])
// Result: "Clone to Blog Post"

// Two targets
t('Clone to @types', ['@types' => 'Blog Post/News Item'])
// Result: "Clone to Blog Post/News Item"

// Three or more targets
t('Clone to @count types', ['@count' => 3])
// Result: "Clone to 3 types"
```

## Where It Appears

The dynamic button text appears in:

1. **Entity Edit Forms**
   - Node edit forms: `/node/{nid}/edit`
   - Taxonomy term forms: `/taxonomy/term/{tid}/edit`
   - Button location: In the actions section at the bottom

2. **Entity Operations**
   - Content listing: `/admin/content`
   - Taxonomy term listing: `/admin/structure/taxonomy/manage/{vocabulary}/overview`
   - Location: In the operations dropdown

## Benefits

✅ **User-friendly**: Users immediately know where content will be cloned  
✅ **Contextual**: Button text adapts to available mappings  
✅ **Clear**: No confusion about what will happen when clicking  
✅ **Efficient**: Reduces clicks when only one target exists  

## User Experience

### Scenario 1: Single Mapping
```
User editing Article node
Button shows: "Clone to Blog Post"
User clicks → Directly clones to Blog Post (no selection needed)
```

### Scenario 2: Multiple Mappings
```
User editing Article node  
Button shows: "Clone to Blog Post/News Item"
User clicks → Selection form appears with both options
User selects target → Clones to selected type
```

### Scenario 3: Many Mappings
```
User editing Article node
Button shows: "Clone to 5 types"
User clicks → Selection form with all 5 options
User selects target → Clones to selected type
```

## Configuration Impact

The button text automatically updates when:
- New mappings are added
- Mappings are enabled/disabled
- Target bundles are changed
- Cache is cleared

No additional configuration needed!

## Translation Support

The button text is translatable:
- `t('Clone to @type')` - For single target
- `t('Clone to @types')` - For two targets  
- `t('Clone to @count types')` - For multiple targets

Translation files can provide localized versions of these strings.

## Performance

The target labels are loaded only when:
- The entity edit form is displayed
- The entity operations are rendered
- User has "clone entities" permission

Labels are cached by Drupal's entity system for efficiency.

## Fallback Behavior

If target entity type cannot be loaded:
- The mapping is skipped
- Other valid mappings are still shown
- If no valid mappings remain, button is not displayed

## Testing

To test the dynamic button text:

1. **Create single mapping:**
   ```
   Source: Article → Target: Blog Post
   Edit an Article → See "Clone to Blog Post"
   ```

2. **Create multiple mappings:**
   ```
   Source: Article → Target: Blog Post
   Source: Article → Target: News Item
   Edit an Article → See "Clone to Blog Post/News Item"
   ```

3. **Create many mappings:**
   ```
   Source: Article → 5 different targets
   Edit an Article → See "Clone to 5 types"
   ```

## Customization

To customize the button text format, edit `j_navi_entity_clone.module`:

**Location:** Lines 69-78 (entity operations) and 154-163 (form alter)

**Current format:**
```php
// Single target
$button_text = t('Clone to @type', ['@type' => $label]);

// Two targets
$button_text = t('Clone to @types', ['@types' => $label1 . '/' . $label2]);

// Multiple targets
$button_text = t('Clone to @count types', ['@count' => $count]);
```

**Custom examples:**
```php
// Show arrow
$button_text = t('Clone → @type', ['@type' => $label]);

// Show all labels with comma
$button_text = t('Clone to: @types', ['@types' => implode(', ', $target_labels)]);

// Show emoji
$button_text = t('📋 Clone to @type', ['@type' => $label]);
```

## Accessibility

The button text is:
- Screen reader friendly (uses proper t() function)
- Semantically correct (uses link/button elements)
- Keyboard accessible (standard form controls)
- WCAG compliant

## Related Features

This feature works in conjunction with:
- Mapping configuration (`/admin/config/content/j-navi-entity-clone`)
- Clone selection form (when multiple targets exist)
- Language selection form (for multilingual content)
- Permission system (respects "clone entities" permission)

## Future Enhancements

Potential improvements:
- Show icons for different entity types
- Display preview of target fields
- Add tooltip with mapping details
- Color-code buttons by target type
- Show target entity count or other stats

---

**This feature makes cloning more intuitive and user-friendly!**
