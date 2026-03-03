import DOMPurify from 'dompurify'

/**
 * Sanitize HTML to prevent XSS attacks.
 * Allows safe formatting tags but strips dangerous content.
 */
export function sanitizeHtml(dirty) {
  if (!dirty) return ''
  return DOMPurify.sanitize(dirty, {
    ALLOWED_TAGS: ['b', 'i', 'em', 'strong', 'span', 'br', 'p', 'ul', 'ol', 'li', 'a', 'code', 'pre', 'mark', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'hr', 'table', 'thead', 'tbody', 'tr', 'th', 'td'],
    ALLOWED_ATTR: ['class', 'href', 'target', 'rel'],
    ALLOW_DATA_ATTR: false,
  })
}
