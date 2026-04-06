import DOMPurify from 'dompurify';

// Allow common HTML tags for rich text content (admin-controlled)
DOMPurify.addHook('afterSanitizeAttributes', (node) => {
    // Force target="_blank" on all links with rel="noopener noreferrer"
    if (node.tagName === 'A' && node.getAttribute('href')) {
        node.setAttribute('target', '_blank');
        node.setAttribute('rel', 'noopener noreferrer');
    }
});

const ALLOWED_TAGS = [
    'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
    'p', 'br', 'hr', 'blockquote',
    'ul', 'ol', 'li',
    'strong', 'b', 'em', 'i', 'u', 's', 'del',
    'a', 'img',
    'table', 'thead', 'tbody', 'tr', 'th', 'td',
    'div', 'span', 'pre', 'code',
    'sub', 'sup',
];

const ALLOWED_ATTR = [
    'href', 'src', 'alt', 'title', 'class',
    'target', 'rel', 'width', 'height', 'colspan', 'rowspan',
];

export function sanitizeHtml(dirty) {
    if (!dirty) return '';
    return DOMPurify.sanitize(dirty, {
        ALLOWED_TAGS,
        ALLOWED_ATTR,
        ALLOW_DATA_ATTR: false,
    });
}
