# CSS style

General rules are in [index.md](index.md). When custom CSS is allowed at all is in `reference/views.md`, "Markup rules".

```css
/* Variables */
    :root {
        --accent: #024aae;
        --accent-dark: #003988;
        --sidebar-width: 280px;
    }

/* Bootstrap override */
    .btn-primary, .btn-primary.disabled, .btn-primary:disabled {
        background-color: var(--accent);
        border-color: var(--accent);
    }

    .btn-primary:not(:disabled):not(.disabled):hover {
        background-color: var(--accent-dark);
        border-color: var(--accent-dark);
    }

    .text-primary {
        color: var(--accent) !important;
    }

/* Sidenav */
    .sidenav-item {
        display: block;
        padding: 0.5rem 0.75rem;
        border-radius: 0.5rem;
    }

    .sidenav-item:hover {
        color: white;
        background: var(--accent);
    }
```

## Files

- `webroot/assets/css/main.css` is the project stylesheet and the default home of every rule. A second stylesheet needs a real intent: a large self-contained area that does not look like the rest of the product (a landing page builder with customer designs, a shop) or one big component with a lot of its own styling. It is loaded only on those pages through the `head` section, and most pages load as few stylesheets as possible. Not one per page, not one per feature.
- The file is organised in **sections headed by a comment** (`/* Variables */`, `/* Bootstrap override */`, `/* Sidenav */`, `/* Utilities */`), with every rule of the section indented four spaces under its heading, so the structure shows from the left edge like in code.
- Colors, breakpoints and sizes are **custom properties on `:root`** at the top; rules reference `var(--accent)`, never a repeated hex value.

## Rules

- **Theme Bootstrap, do not build a parallel palette.** Recolour Bootstrap's own classes (`.btn-primary`, `.text-primary`, `.bg-primary`, `.badge-primary`, `.border-primary`) through the variables. No `.btn-accent` or `.text-accent` next to them: the Bootstrap names are what everyone already knows, and the markup stays portable between projects.
- Class names are `kebab-case` and name a purpose or a utility in Bootstrap's vocabulary (`shadow-hover`, `cursor-pointer`, `rounded-xl`, `mb-6`, `illustration-lg`), never a page or an element (`contact-page-title`).
- **Classes, not ids**, in selectors. Ids are for JavaScript hooks and labels; a style bound to one id cannot be reused and outranks every class.
- **`!important` only where Bootstrap forces it**: its utilities (`.text-primary`, `.bg-primary`, `.shadow-sm`) are `!important` themselves, so overriding them needs it too. Nowhere else; a fight with a project class is won by a better selector.
- One declaration per line, four spaces, a space after the colon, every declaration ending in `;`, the opening brace on the selector's line. A short selector list stays on one line (`.card, .card-body {`); a long one goes one selector per line.
- **No code golf.** No rules squeezed onto one line, no selector chains that save a class at the cost of readability, no shorthand that hides which property is actually set (`margin: 0 0 1rem` when only `margin-bottom` is meant). Keep a blank line between rules; a tight family of variants (`.card-abo`, `.text-abo`, `.badge-abo`) may sit together, but readable spacing wins when in doubt.
- Media queries use Bootstrap's breakpoints (`575.98px`, `767.98px`, `991.98px`, `1199.98px`), mobile first: the base rule is the phone, the query adds the desktop.
- Fonts are `@font-face` blocks at the top of `main.css` (or the layout) pointing at self hosted woff2 files.
