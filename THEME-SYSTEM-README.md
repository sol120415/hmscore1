# Theme System Documentation

## Overview

This theme system provides a comprehensive light/dark mode toggle for your CoreUI-based hotel management system. It includes automatic system preference detection, localStorage persistence, and smooth transitions between themes.

## Features

- ✅ **Light/Dark Mode Toggle** - Seamless switching between themes
- ✅ **System Preference Detection** - Automatically detects user's OS theme preference
- ✅ **localStorage Persistence** - Remembers user's choice across sessions
- ✅ **Smooth Transitions** - Animated theme changes without page reload
- ✅ **CoreUI Integration** - Works seamlessly with CoreUI components
- ✅ **Wave Animation Support** - Maintains animated backgrounds in both themes
- ✅ **Responsive Design** - Works on all device sizes
- ✅ **Accessibility** - Keyboard navigation and screen reader support
- ✅ **Keyboard Shortcut** - Ctrl/Cmd + Shift + T to toggle theme

## Files Created

### CSS Files
- `css/theme-system.css` - Main theme system stylesheet

### JavaScript Files  
- `js/theme-system.js` - Theme management JavaScript class

## Integration

The theme system has been integrated into the following files:
- `dashboard.php`
- `login.php`
- `register.php`
- `forgot_password.php`
- `verify_2fa.php`
- All module pages (guests, rooms, reservations, etc.)

## Usage

### Automatic Integration
The theme system automatically initializes when the page loads. No additional setup required.

### Manual Theme Control
```javascript
// Get the theme manager instance
const themeManager = window.themeManager;

// Toggle between light and dark
themeManager.toggleTheme();

// Set specific theme
themeManager.setTheme('light');
themeManager.setTheme('dark');

// Get current theme
const currentTheme = themeManager.getCurrentTheme();

// Check theme status
const isLight = themeManager.isLightTheme();
const isDark = themeManager.isDarkTheme();

// Reset to system preference
themeManager.resetToSystemTheme();
```

### Theme Change Events
```javascript
// Listen for theme changes
document.addEventListener('themechange', function(event) {
    console.log('Theme changed to:', event.detail.theme);
    // Your custom logic here
});
```

## Theme Variables

The system uses CSS custom properties for consistent theming:

### Light Theme
```css
--theme-bg-primary: #ffffff;
--theme-bg-secondary: #f8f9fa;
--theme-bg-tertiary: #e9ecef;
--theme-text-primary: #212529;
--theme-text-secondary: #6c757d;
--theme-text-muted: #adb5bd;
--theme-border-color: #dee2e6;
--theme-accent: #0d6efd;
```

### Dark Theme
```css
--theme-bg-primary: #1a1a1a;
--theme-bg-secondary: #2d2d2d;
--theme-bg-tertiary: #3a3a3a;
--theme-text-primary: #ffffff;
--theme-text-secondary: #adb5bd;
--theme-text-muted: #6c757d;
--theme-border-color: #495057;
--theme-accent: #0d6efd;
```

## Components Styled

The theme system automatically styles:
- Body and main containers
- CoreUI components (cards, forms, buttons, navigation)
- Sidebar and navigation
- Tables and alerts
- Form controls and input groups
- Wave animations (maintains blue color in both themes)
- Login/register forms
- All text colors and backgrounds

## Theme Toggle Button

The theme toggle button:
- Appears in the top-right corner of all pages
- Shows sun icon in dark mode, moon icon in light mode
- Includes hover effects and smooth transitions
- Is fully accessible with keyboard navigation
- Works on mobile devices with touch support

## Browser Support

- Chrome 88+
- Firefox 87+
- Safari 14+
- Edge 88+

## Customization

### Adding Custom Theme-Aware Styles
```css
.my-custom-component {
    background-color: var(--theme-bg-primary);
    color: var(--theme-text-primary);
    border-color: var(--theme-border-color);
    transition: all 0.3s ease;
}
```

### Overriding Theme Variables
```css
[data-theme="light"] {
    --theme-accent: #your-custom-color;
}
```

## Troubleshooting

### Theme Not Applying
1. Check that `css/theme-system.css` is loaded after CoreUI CSS
2. Verify `js/theme-system.js` is loaded before the closing `</body>` tag
3. Check browser console for JavaScript errors

### Wave Animation Issues
The wave animation is preserved in both themes with appropriate colors:
- Light mode: Blue waves on white background
- Dark mode: Blue waves on dark background

### localStorage Issues
If localStorage is not available, the system falls back to system preference detection.

## Performance

- Minimal performance impact
- CSS transitions are hardware-accelerated
- JavaScript is optimized for fast theme switching
- No page reloads required

## Security

- No external dependencies
- All code is self-contained
- localStorage is used only for theme preference
- No data is sent to external servers

## Future Enhancements

Potential future improvements:
- Additional theme options (high contrast, colorblind-friendly)
- Theme preview before applying
- Bulk theme changes for admin users
- Theme scheduling (auto-switch based on time)

## Support

For issues or questions:
1. Check the browser console for errors
2. Verify all files are properly loaded
3. Test in different browsers
4. Check localStorage in browser dev tools

The theme system is designed to be robust and handle edge cases gracefully.
