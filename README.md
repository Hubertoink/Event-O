# Event-O

A modern WordPress plugin for event management with beautiful Gutenberg blocks.

![WordPress](https://img.shields.io/badge/WordPress-5.9+-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4+-purple.svg)
![License](https://img.shields.io/badge/License-GPL--2.0+-green.svg)

## Features

### 🎨 Three Gutenberg Blocks

- **Event List** – Accordion-style collapsible list with smooth animations
- **Event Carousel** – Horizontal scrollable carousel with navigation
- **Event Grid** – Modern card grid with date badges and mobile slider dots

### 📅 Full Event Management

- Custom post type for events with start/end dates and times
- Recurring events support
- Event categories and tags
- Venue management (address, coordinates, map link)
- Organizer management (name, contact info, social links)

### 🌙 Dark Mode Support

- Fully configurable theme compatibility
- Works with Neve, Tailwind-based themes, and any theme with CSS selectors
- Auto-detect, always light, or always dark mode options

### 📱 Responsive Design

- Mobile-first approach
- Touch-friendly carousel and grid navigation
- Dot navigation for mobile grid slider

### 🔗 Social Sharing

- Facebook, X (Twitter), WhatsApp, LinkedIn, Email, Instagram
- Calendar export (iCal download, Google Calendar, Outlook)
- Copy URL to clipboard

## Installation

1. Download or clone this repository
2. Upload the `event-o` folder to `/wp-content/plugins/`
3. Activate the plugin through the WordPress admin
4. Go to **Settings → Event_O** to configure colors and options

## Usage

### Creating Events

1. Navigate to **Events → Add New** in your WordPress admin
2. Fill in event details: title, description, dates, venue, organizer
3. Add a featured image
4. Publish your event

### Adding Blocks

In the Gutenberg editor, search for:
- `Event List` – For accordion-style event listings
- `Event Carousel` – For horizontal event carousels
- `Event Grid` – For card-based event grids

### Block Options

Each block has customizable options in the sidebar:
- Number of events to display
- Show/hide images, venue, organizer, category
- Accent color selection
- Open first item expanded (List block)
- Columns count (Grid block)
- Slides per view (Carousel block)

## Configuration

Navigate to **Settings → Event_O** in your WordPress admin:

### Design
- Primary color
- Accent color
- Text color
- Muted color

### Behavior
- Enable/disable single event template
- Configure share buttons

### Theme Compatibility
- Color mode: Auto / Light / Dark
- Dark mode CSS selector (e.g., `html[data-neve-theme="dark"]`)
- Light mode CSS selector

## Requirements

- WordPress 5.9+
- PHP 7.4+

## Screenshots

*Coming soon*

## Changelog

### 0.7.0
- Added Event Grid block with date badges
- Added configurable dark/light mode selectors
- Improved accordion animations
- Mobile dots navigation for grid
- Customizable display options (organizer, category, venue)

### 0.6.0
- Initial public release
- Event List, Carousel blocks
- Venue and Organizer meta boxes
- Social sharing integration

## License

GPL-2.0-or-later

## Author

Made with ❤️ for WordPress
