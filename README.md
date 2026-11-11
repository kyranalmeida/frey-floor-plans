# Frey Property Floor Plans

![Floor Plan Filter UI](./preview.png)

# Frey Property Floor Plans — WordPress REST & Vanilla ES6 Filter Module

A custom WordPress theme demonstrating a decoupled property floor plan filtering architecture built with a custom post type (`floor_plan`), programmatic ACF field definitions, a sanitized read-only REST API endpoint (`GET /wp-json/theme/v1/floor-plans`), and a dependency-free ES6 controller.

## Architecture Highlights

1. **Read-Only REST Endpoint (`theme/inc/rest-api.php`)**
   - Route: `GET /wp-json/theme/v1/floor-plans`
   - Sanitizes `bedrooms` and `max_price` using `absint()` and validates `move_in_date` using strict `DateTime::createFromFormat('Y-m-d', $date)` verification so invalid calendar dates (e.g., `2026-02-31`) return a `400 Bad Request`.
2. **Dynamic `WP_Query` with `meta_query` (`relation => 'AND'`)**
   - `bedrooms IN` (`NUMERIC`)
   - `starting_price <= max_price` (`NUMERIC`)
   - `available_date <= move_in_date` (`DATE`)
3. **Zero-Value (`0` = Studio) Edge-Case Handling**
   - Avoids loose PHP truthy checks (`empty('0') === true`) and `array_filter()` without callbacks. Uses explicit `'' !== $trimmed && is_numeric($trimmed)` checks in PHP and a `Set<number>` in ES6 so filtering by Studio (`?bedrooms=0`) or Studio + 1 Bed (`?bedrooms=0,1`) works reliably.
4. **Frontend UX & Shareable URL State (`theme/assets/js/floor-plan-filter.js`)**
   - Multi-select bedroom pills (`aria-pressed`), 250ms debounced max-price input, and date picker (`min` locked to today).
   - Two-way URL synchronization via `URLSearchParams` and `history.replaceState` for bookmarkable/shareable filter states.
   - Cancels stale in-flight requests via `AbortController`.
5. **Security & WCAG 2.1 AA Accessibility**
   - All PHP outputs escaped via `esc_html()`, `esc_attr()`, and `esc_url()`.
   - Client-side DOM nodes constructed via `document.createElement` and `textContent` (zero `innerHTML` injection vectors).
   - Screen reader announcements powered by `role="status"` and `aria-live="polite"`.
