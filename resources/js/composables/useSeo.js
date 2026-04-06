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

/**
 * Set meta tag content by name attribute
 * @param {string} name - meta tag name
 * @param {string} content - meta tag content
 */
export function setMeta(name, content) {
  let meta = document.querySelector(`meta[name="${name}"]`);
  if (!meta) {
    meta = document.createElement('meta');
    meta.setAttribute('name', name);
    document.head.appendChild(meta);
  }
  meta.setAttribute('content', content);
}

/**
 * Set robots meta tag (e.g. 'noindex' for search result pages)
 */
export function setRobots(value) {
  setMeta('robots', value);
}
