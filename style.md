# Webapp Style Guide

## Typography

### Font Family
```css
font-family: "M PLUS Rounded 1c", sans-serif;
```

### Text Colors
- **Primary Text**: `var(--primary)`
- **Meta Text**: `var(--type-meta)`
- **Tag Text**: `var(--tags-color)`

### Headings
- **H2 Titles**: 1.0em, weight 700, line-height 1.2rem, margin 0 0 8px 0

### Content Text
- **Post Content**: weight 500, margin 2rem 0

### Meta Text
- **List User**: 0.775rem, weight 600, line-height 0.8rem

## Layout & Components

### Background
- **Body**: `var(--background-body)`
- **Elements**: `var(--background-element)`

### List Elements
- **Border Radius**: 20px
- **Padding**: 20px 30px
- **Margin**: 18px 0
- **Min Height**: 1rem

### Badges & Tags
- **Experience Badge**: 
  - Size: 0.7rem, weight 500
  - Background: `var(--primary)`
  - Margin: 10px right
- **Tags**: 
  - Size: 0.7rem
  - Padding: 0.3em 0.6em
  - Margin: 0.3em right, 0.3em bottom
  - Background: `var(--tags-bg)`

### Links
- **Therapist Links**: 
  - Size: 1.0rem, weight 600
  - Display: inline-flex, align center
  - Line-height: 1.4
  - Padding: 2px 0
  - No decoration

### Buttons
- **Outline Style**: 
  - Background: transparent
  - Border: 1px solid `var(--primary)`
  - Border-radius: 5px
  - Transition: 0.2s ease-in-out

## CSS Variables Used
- `--background-body`
- `--background-element`
- `--primary`
- `--type-meta`
- `--tags-color`
- `--tags-bg`