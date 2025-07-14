# Remy - Psychotherapy Patient Exchange Platform

## Project Overview
Remy is a PHP-based forum platform designed for psychotherapy patients to share experiences, exchange information about therapists, and connect with others in their therapeutic journey.

## Core Features
- **User Management**: Registration, authentication, profile management
- **Forum System**: Post creation, commenting with inline replies, text citation
- **Therapist Database**: Searchable therapist profiles and reviews  
- **Messaging**: Private messaging between users
- **Moderation**: Admin panel for content and user management
- **Notifications**: Real-time notifications for interactions

## Technical Stack
- **Backend**: PHP 8+ with PDO (MySQL)
- **Frontend**: Bootstrap 5, vanilla JavaScript
- **Database**: MySQL (database: `pandoc`)
- **Rich Text**: Summernote editor
- **Security**: HTMLPurifier for content sanitization

## Project Structure

### Root Files
- `index.php` - Landing page with language selection
- `forum.php` - Main forum listing
- `post.php` - Individual post view with comments
- `create_post.php` - New post creation
- `user.php` - User profile pages
- `login.php` / `register.php` - Authentication
- `navbar.php` - Global navigation component

### Core Directories

#### `/config/`
- `database.php` - Database connection configuration
- `config.php` - Application settings

#### `/includes/`
- `header.php` / `footer.php` - Layout components
- `auth.php` - Authentication utilities
- `comment_display.php` - Reusable comment rendering
- `notification_helpers.php` - Notification system
- `summernote.php` - Rich text editor integration
- `date_function.php` - Date formatting utilities

#### `/assets/`
- `css/custom.css` - Main stylesheet with dark theme
- `js/comment-manager.js` - Advanced comment system with citations
- `img/` - Icons and graphics (SVG quote icons, avatars)

#### `/panel/`
- Admin/moderation interface
- `moderation.php` - Content moderation
- `user_admin.php` - User management

#### `/languages/`
- Multi-language support (DE/FR/IT)
- Localization files for frontend strings

#### `/uploads/`
- `avatars/` - User profile pictures
- `kantone/` - Swiss canton flags
- `background/` - Theme graphics

### Key Features Implementation

#### Comment System
- **Main Comments**: Direct replies to posts
- **Inline Replies**: Threaded replies to specific comments
- **Edit/Delete**: Full CRUD operations with "bearbeitet" indicators
- **Text Citations**: Select text + quote button system
- **AJAX Processing**: Seamless UX without page reloads

#### Citation Feature
- Context menu on text selection ("Zitat gespeichert")
- Always-visible quote icons in textareas
- Markdown-style `> text` syntax converted to styled blockquotes
- SVG quote icons with enabled/disabled states

#### User Management
- Session-based authentication
- Role-based permissions (users, moderators, admins)
- User profiles with activity tracking
- Ban/restriction system

#### Content Management
- Rich text editing with Summernote
- HTML sanitization for security
- Post categories (Erfahrung, Suche, etc.)
- Tag system for organization
- Draft/publish workflow

## Database Schema
- `users` - User accounts and profiles
- `posts` - Forum posts with categories
- `comments` - Threaded comment system
- `therapists` - Therapist directory
- `notifications` - User notification system
- `messages` - Private messaging
- `categories` - Post categorization

## Security Measures
- HTMLPurifier for content sanitization
- Prepared statements for SQL injection prevention
- Session management for authentication
- Input validation and length limits
- User permission checks

## Recent Major Updates
1. **Advanced Comment System** - Full CRUD operations with inline editing
2. **Citation Feature** - Intuitive text selection and quoting system
3. **AJAX Integration** - Smooth user experience without page reloads
4. **Visual Enhancements** - Custom SVG icons and improved UX
5. **Mobile Optimization** - Responsive design improvements

## Development Notes
- PHP 8+ required for modern syntax features
- Bootstrap 5 for responsive design
- HTMLPurifier dependency managed via Composer
- Custom JavaScript for enhanced interactivity
- Dark theme with CSS custom properties