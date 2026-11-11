import { useDisplay } from "vuetify";

// Big screens: an AppCardTable fills the screen from where it starts down to
// the bottom, so the page stays still and only the table rows scroll.
// Phones keep the normal page scroll.
//
// Use:
//   const { box, isFit, height } = useFitTable();
//   <div ref="box" :class="{ 'fit-table': isFit }" :style="{ height: isFit ? height : null }">
//     <AppCardTable :is-full-height="isFit" :is-full-height-tab="isFit" … />
//   </div>
// Call `refit()` after something above the table changes size.
export function useFitTable({ minHeight = 360 } = {}) {
  const { mdAndUp: isFit } = useDisplay();
  const box = ref(null);
  const height = ref(null);

  // What the page shows under an element: the bottom padding/border of each
  // parent card, and anything placed below it (e.g. a footer).
  // (Not the page height — a page with a minimum height would keep the table small.)
  const spaceBelow = (el) => {
    let sum = parseFloat(getComputedStyle(el).marginBottom) || 0;
    for (
      let node = el;
      node.parentElement && node !== document.body;
      node = node.parentElement
    ) {
      const bottom = node.getBoundingClientRect().bottom;
      for (
        let sib = node.nextElementSibling;
        sib;
        sib = sib.nextElementSibling
      ) {
        const style = getComputedStyle(sib);
        if (["fixed", "absolute"].includes(style.position)) continue;
        const rect = sib.getBoundingClientRect();
        if (rect.top < bottom - 1) continue; // beside it, not below
        sum +=
          rect.height +
          (parseFloat(style.marginTop) || 0) +
          (parseFloat(style.marginBottom) || 0);
      }
      const parent = getComputedStyle(node.parentElement);
      sum +=
        (parseFloat(parent.paddingBottom) || 0) +
        (parseFloat(parent.borderBottomWidth) || 0);
    }
    return sum;
  };

  const refit = () => {
    const el = box.value;
    if (!el || !el.offsetParent) return; // not on screen (closed tab)
    const top = el.getBoundingClientRect().top + window.scrollY;
    height.value = `${Math.max(minHeight, window.innerHeight - top - spaceBelow(el))}px`;
  };

  // Re-measure when the area around the table changes size (tab opens,
  // a card above grows) or the window resizes
  const observer = new ResizeObserver(() => refit());
  watch(box, (el, old) => {
    if (old?.parentElement) observer.unobserve(old.parentElement);
    if (el?.parentElement) observer.observe(el.parentElement);
    nextTick(refit);
  });
  onMounted(() => window.addEventListener("resize", refit));
  onBeforeUnmount(() => {
    observer.disconnect();
    window.removeEventListener("resize", refit);
  });

  return { box, isFit, height, refit };
}
