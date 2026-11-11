export function usePrintStyle(css) {
  const inject = () => {
    const existing = document.getElementById('dynamic-print-style')
    if (existing) existing.remove()

    const style = document.createElement('style')
    style.id = 'dynamic-print-style'
    style.innerHTML = `@page { ${css} }`
    document.head.appendChild(style)
  }

  return { inject }
}