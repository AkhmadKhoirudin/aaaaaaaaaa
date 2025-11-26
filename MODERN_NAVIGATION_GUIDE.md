
# Modern Navigation System - CBT Online

## Overview
Sistem navigation modern menggunakan Bootstrap 5 dengan sidebar collapsible, responsive design, dan fitur-fitur interaktif untuk aplikasi CBT Online.

## Features

### 🎨 Design Features
- **Bootstrap 5.3.2** - Framework CSS terbaru
- **Responsive Design** - Mobile-first approach
- **Collapsible Sidebar** - Dapat dilipat/expand
- **Smooth Animations** - Transisi halus
- **Gradient Backgrounds** - Desain modern
- **Shadow Effects** - Efek bayangan pada cards

### 🚀 Functional Features
- **Role-based Menu** - Menu disesuaikan dengan role user
- **Active Menu Highlighting** - Menu aktif ditandai
- **Breadcrumb Navigation** - Navigasi breadcrumb otomatis
- **User Dropdown Menu** - Menu user di pojok kanan
- **Notification System** - Sistem notifikasi
- **Keyboard Shortcuts** - Ctrl+B untuk toggle sidebar
- **State Persistence** - Status sidebar tersimpan di localStorage
- **Mobile Responsive** - Adaptif untuk semua ukuran layar

## File Structure

```
admin/
├── includes/
│   ├── header-modern.php    # Header & Navigation
│   └── footer-modern.php    # Footer & Scripts
├── dashboard.php            # Updated with modern nav
├── users.php               # Updated with modern nav
├── sekolah.php             # Updated with modern nav
├── kelas.php               # Updated with modern nav
└── demo-modern-nav.php     # Demo & showcase
```

## Usage

### 1. Include Header
```php
<?php
$page_title = 'Your Page Title';
include 'includes/header-modern.php';
?>
```

### 2. Page Content
```php
<!-- Page Header -->
<div class="content-header">
    <div class="row align-items-center">
        <div class="col">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-icon text-gradient me-2"></i>
                Page Title
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Current Page</li>
                </ol>
            </nav>
        </div>
        <div class="col-auto">
            <!-- Action buttons here -->
        </div>
```

### 3. CSS Variables
Modern navigation menggunakan CSS variables untuk mudah dikustomisasi:

```css
:root {
    --primary-color: #6366f1;
    --primary-dark: #4f46e5;
    --secondary-color: #64748b;
    --accent-color: #06b6d4;
    --sidebar-width: 280px;
    --border-radius: 12px;
    --transition-speed: 0.3s;
}
```

### 4. JavaScript Configuration
```javascript
// Automatic initialization
document.addEventListener('DOMContentLoaded', () => {
    new ModernNavbar();
});

// Manual initialization with options
const navbar = new ModernNavbar({
    autoCloseMobile: true,
    enableRipple: true,
    enableSwipe: true
});
```

## Usage Examples

### Basic Navigation Link
```html
<a class="nav-link" href="dashboard.php">
    <i class="fas fa-tachometer-alt"></i>
    <span>Dashboard</span>
</a>
```

### Navigation with Submenu
```html
<a class="nav-link has-submenu" data-bs-toggle="collapse" href="#masterDataMenu">
    <i class="fas fa-database"></i>
    <span>Data Master</span>
    <i class="fas fa-chevron-down transition-icon"></i>
</a>
<div class="collapse" id="masterDataMenu">
    <ul class="nav-menu">
        <li class="submenu-item">
            <a class="submenu-link" href="sekolah.php">
                <i class="fas fa-school"></i>
                Data Sekolah
            </a>
        </li>
    </ul>
</div>
```

### Active State Management
```php
<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<a class="nav-link <?= $current_page == 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
    Dashboard
</a>
```

## Customization

### Color Scheme
```css
/* Override primary color */
:root {
    --primary-color: #your-color;
    --primary-dark: #your-dark-color;
}

/* Custom gradient */
.bg-gradient-custom {
    background: linear-gradient(135deg, #your-color1 0%, #your-color2 100%);
}
```

### Animation Speed
```css
:root {
    --transition-speed: 0.5s; /* Slower animation */
}
```

### Border Radius
```css
:root {
    --border-radius: 8px; /* Smaller radius */
}
```

## Mobile Features

### Touch Swipe Gestures
- **Swipe right** from left edge: Open sidebar
- **Swipe left**: Close sidebar
- **Smooth transitions** with momentum

### Responsive Breakpoints
- **Desktop**: ≥ 768px (sidebar visible)
- **Tablet**: 576px - 767px (collapsible sidebar)
- **Mobile**: < 576px (overlay sidebar)

## Performance Optimization

### CSS Optimizations
- **Hardware acceleration** dengan `transform` dan `opacity`
- **Efficient selectors** untuk minimal reflow
- **CSS variables** untuk konsistensi
- **Minified animations** dengan keyframes

### JavaScript Optimizations
- **Event delegation** untuk memory efficiency
- **Debounced resize** handler
- **Lazy loading** untuk submenu
- **Error boundaries** untuk stability

## Browser Support
- ✅ Chrome 80+
- ✅ Firefox 75+
- ✅ Safari 13+
- ✅ Edge 80+
- ✅ Mobile browsers

## Troubleshooting

### Common Issues

1. **Navigation not working**
   - Check if JavaScript file is loaded
   - Verify CSS classes are correct
   - Check browser console for errors

2. **Mobile menu not opening**
   - Ensure viewport meta tag is present
   - Check if touch events are supported
   - Verify CSS media queries

3. **Submenu not toggling**
   - Check Bootstrap 5 compatibility
   - Verify data attributes are correct
   - Ensure unique IDs for submenus

4. **Active states not working**
   - Verify PHP variable scope
   - Check basename() function usage
   - Ensure proper echo statements

### Debug Mode
```javascript
// Enable debug mode
window.modernNavbarDebug = true;

// Check initialization
console.log('Modern Navbar initialized:', window.modernNavbarInitialized);
```

## Migration Guide

### From Old Navigation
1. Backup existing files
2. Replace header/footer includes
3. Update CSS classes
4. Test functionality
5. Gradual rollout

### Hybrid Approach
```php
<?php
// Use modern navigation for new pages
$use_modern_nav = true;

if ($use_modern_nav) {
    include 'includes/header-modern.php';
} else {
    include 'includes/header.php';
}
?>
```

## Best Practices

### 1. Semantic HTML
- Use proper `<nav>` elements
- Implement ARIA labels
- Maintain heading hierarchy

### 2. Performance
- Minimize DOM manipulation
- Use CSS transitions over JavaScript
- Optimize images and icons

### 3. Accessibility
- Test with screen readers
- Ensure keyboard navigation
- Provide focus indicators

### 4. Mobile First
- Design for mobile first
- Test on real devices
- Optimize touch targets

## Future Enhancements

### Planned Features
- **Dark mode support**
- **Customizable themes**
- **Advanced animations**
- **PWA integration**
- **Voice navigation**

### API Integration
```javascript
// Future API for dynamic menu
ModernNavbar.loadMenu('/api/menu.json');
ModernNavbar.updateNotifications();
ModernNavbar.setTheme('dark');
```

## Support

Untuk bantuan atau pertanyaan:
- Cek browser console untuk errors
- Review dokumentasi ini
- Test di demo page: `admin/demo-modern-nav.php`

---

**Last Updated**: November 2025  
**Version**: 1.0.0  
**Author**: CBT Admin Development Team