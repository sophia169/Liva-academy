# Liva Academy CSS refactor notes

## What changed

The original `assets/css/liva.css` had grown to roughly 6,500 lines. This refactor keeps the same cascade order, but separates the stylesheet by responsibility:

- `assets/css/tokens.css` — design tokens only: brand/state colors, neutrals, radii, typography, shadows, and shared layout constants.
- `assets/css/liva.css` — global reset, shared site components, app shell, common components, and the earlier shared responsive rules.
- `assets/css/liva-public.css` — homepage, public catalogue (`My learning`) design pass, and navigation/dropdown refinements.
- `assets/css/liva-placement.css` — placement-test admin/scoring, question bank, test runner, and result UI.
- `assets/css/liva-course.css` — course management, course editor, modals, curriculum/lesson UI, settings, previews, and player controls.

`functions.php` now enqueues those files as an explicit dependency chain. That is important: it preserves the source order of the old single stylesheet, so later overrides still win in the same order.

## Tokens

Use a token when a value expresses a design decision shared by more than one component. Good token candidates are colors, borders, radii, typography, shadows, and major layout dimensions.

Do **not** create a token for every arbitrary `padding`, width, or one-off pixel value. That makes CSS harder to understand rather than easier.

Two very similar blues already exist in the current design: `#29779b` and `#28789b`. They are intentionally represented as separate tokens (`--liva-blue` and `--liva-blue-ui`) so this refactor does not subtly change the UI. After visual QA, consider choosing one and removing the other.

## Duplicate cleanup

A number of historical selector blocks were repeated later in the file. Where the later rule completely superseded the earlier one, the older block was removed. Where an older block still contributed one or two effective properties, it was reduced to only those declarations.

Examples included the primary navigation, footer overrides, learning tabs, modal backdrop, dropzones, radio controls, preview badges, lesson icons, and duration pills.

## Important follow-up: `!important`

The original stylesheet contains around 980 `!important` declarations. This refactor deliberately does **not** mass-remove them because that would require visual/regression testing in WordPress. The next cleanup pass should remove them component-by-component by fixing selector specificity and source order.

Recommended order:

1. Course editor/modal controls.
2. Curriculum/lesson rows and SVG icon rules.
3. Placement-test question controls.
4. App shell/header overrides.

Do not remove `!important` globally with search/replace.

## Naming and future CSS

Prefer component-oriented selectors such as `.liva-course-card`, `.liva-modal`, and `.liva-filter-pill`. Avoid IDs for styling unless the element genuinely has unique behavioral meaning; classes are easier to reuse and override.

When adding a new design value, first check `tokens.css`. If an existing token is semantically correct, reuse it. If the new value is genuinely part of the design system, add a clearly named token. If it is a one-off layout adjustment, keep it local to the component.

## Optional performance pass

All four style modules are currently loaded on every front-end page. This is intentional for maximum compatibility during the refactor. Once the UI is regression-tested, `functions.php` can conditionally enqueue `liva-public.css`, `liva-placement.css`, and `liva-course.css` only on pages that need them.
