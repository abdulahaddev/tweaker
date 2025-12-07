# Tweaker Developer Guide

## Folder Structure & Naming Conventions

### CRITICAL: Case Sensitivity Rules

**This plugin follows WordPress conventions, NOT strict PSR-4!**

#### Folder Names (Lowercase)
```
tweaker/
├── core/           ← lowercase (WordPress convention)
├── modules/        ← lowercase (WordPress convention)
└── assets/         ← lowercase (WordPress convention)
```

#### Namespace Names (PascalCase)
```php
namespace NabaTech\Tweaker\Core;      // Capital C
namespace NabaTech\Tweaker\Modules;   // Capital M
```

### Why This Matters

- **Windows**: Case-insensitive filesystem (`Core/` = `core/`)
- **Linux**: Case-sensitive filesystem (`Core/` ≠ `core/`)
- **WordPress Standard**: All plugin folders are lowercase
- **PHP Standard (PSR-4)**: Namespaces use PascalCase

### How The Autoloader Works

The custom autoloader (`core/Autoloader.php`) bridges this gap:

```php
// Namespace: NabaTech\Tweaker\Core\Kernel
// Maps to:   core/Kernel.php  (NOT Core/Kernel.php!)

// The autoloader converts the first directory to lowercase
$path_parts[0] = strtolower($path_parts[0]);
```

### Rules for Developers

1. **✅ DO**: Keep all top-level folders lowercase (`core/`, `modules/`)
2. **✅ DO**: Use PascalCase for namespaces (`Core`, `Modules`)
3. **✅ DO**: Use PascalCase for class files (`Kernel.php`, `MenuManager.php`)
4. **❌ DON'T**: Create folders with capital letters at plugin root
5. **❌ DON'T**: Use lowercase in namespaces (`core`, `modules`)

### File Path Reference

Always use lowercase for folder references in `require` statements:

```php
// ✅ CORRECT
require_once __DIR__ . '/core/Constants.php';
require_once NT_PLUGIN_DIR . 'core/Logger.php';

// ❌ WRONG (breaks on Linux)
require_once __DIR__ . '/Core/Constants.php';
require_once NT_PLUGIN_DIR . 'Core/Logger.php';
```

### Testing on Linux

If developing on Windows, always test on Linux before deployment:

```bash
# SSH to Linux server
ssh user@server

# Check folder names
ls -la /path/to/wordpress/wp-content/plugins/tweaker/

# Should see: core/ modules/ (all lowercase)
```

### Common Mistakes

| Mistake | Fix |
|---------|-----|
| Created `Core/` folder | Rename to `core/` |
| Used `require 'Core/File.php'` | Change to `require 'core/File.php'` |
| Namespace `core\Class` | Change to `Core\Class` |

### References

- WordPress Coding Standards: https://developer.wordpress.org/coding-standards/
- PSR-4 Autoloading: https://www.php-fig.org/psr/psr-4/
- WooCommerce Autoloader: Similar approach used

---

**When in doubt: Folders = lowercase, Namespaces = PascalCase**
