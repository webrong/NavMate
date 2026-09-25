/**
 * SEO utilities for SPA route-level title and meta management
 */

// Store the base site name from Blade template
let baseSiteName = document.title || 'NavMate';

export function setBaseSiteName(name) {
  if (name) baseSiteName = name;
}

/**
 * Update document title for a route
 * @param {string|null} routeTitle - The page-specific title (e.g. '关于'), or null for homepage
 */
export function updateTitle(routeTitle) {
  if (routeTitle) {
    document.title = `${routeTitle} - ${baseSiteName}`;
  } else {
    document.title = baseSiteName;
  }
}
