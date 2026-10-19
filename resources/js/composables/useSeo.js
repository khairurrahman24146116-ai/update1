import { brand } from '../data/lpi.js'

export function useSeo({ title, description, image, url, brand: brandName = brand.name }) {
  if (title) document.title = title + ' — ' + brandName
  const set = (sel, attr, val) => {
    if (!val) return
    let el = document.querySelector(sel)
    if (!el) { el = document.createElement('meta'); document.head.appendChild(el) }
    const [k, v] = sel.includes('property') ? ['property', sel.match(/"([^"]+)"/)[1]] : ['name', sel.match(/"([^"]+)"/)[1]]
    el.setAttribute(k, v); el.setAttribute(attr, val)
  }
  set('meta[name="description"]', 'content', description)
  set('meta[property="og:title"]', 'content', title ? title + ' — ' + brandName : brandName)
  set('meta[property="og:description"]', 'content', description)
  set('meta[property="og:image"]', 'content', image)
  set('meta[property="og:url"]', 'content', url)
  if (url) {
    let canonical = document.querySelector('link[rel="canonical"]')
    if (!canonical) { canonical = document.createElement('link'); canonical.setAttribute('rel', 'canonical'); document.head.appendChild(canonical) }
    canonical.setAttribute('href', url)
  }
}
