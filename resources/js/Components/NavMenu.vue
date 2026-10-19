<template>
  <nav :class="rootClass" :aria-label="variant === 'mobile' ? 'Navigasi seluler' : 'Navigasi utama'">
    <!-- Desktop navigation with hover/click dropdowns -->
    <template v-if="variant === 'desktop'">
      <template v-for="(item, i) in items" :key="keyOf(item, i)">
        <router-link
          v-if="!item.children"
          :to="item.href"
          class="nav-link"
          :class="isActive(item.href) ? 'text-primary font-title-md' : ''"
        >
          {{ labelOf(item) }}
        </router-link>
        <div
          v-else
          class="relative"
          @mouseenter="openDesktop = i"
          @mouseleave="openDesktop = null"
          @focusin="openDesktop = i"
        >
          <button
            type="button"
            class="nav-link flex items-center gap-1"
            :class="isActiveGroup(item) ? 'text-primary font-title-md' : ''"
            :aria-haspopup="true"
            :aria-expanded="openDesktop === i"
            @click="openDesktop = openDesktop === i ? null : i"
            @keydown.escape="openDesktop = null"
          >
            {{ labelOf(item) }}
            <span class="material-symbols-outlined text-[16px]" :class="openDesktop === i ? 'rotate-180' : ''" aria-hidden="true">expand_more</span>
          </button>
          <Transition name="menu">
            <div v-if="openDesktop === i" class="absolute left-0 top-full pt-3 w-72 z-50">
              <div class="glass-panel glass-sheen rounded-2xl p-2 flex flex-col gap-1">
                <template v-for="(child, ci) in item.children" :key="keyOf(child, ci)">
                  <router-link
                    v-if="child.href"
                    :to="child.hash ? { path: child.href, hash: child.hash } : child.href"
                    class="group flex items-center gap-2.5 p-2.5 rounded-xl hover:bg-surface-container-low/80 transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary"
                    @click="openDesktop = null"
                  >
                    <span v-if="child.icon" class="w-9 h-9 rounded-xl bg-primary-fixed text-primary flex items-center justify-center shrink-0 transition-transform duration-300 group-hover:scale-105">
                      <span class="material-symbols-outlined text-[19px]" aria-hidden="true">{{ child.icon }}</span>
                    </span>
                    <span class="flex flex-col">
                      <span class="font-title-md text-title-md leading-tight">{{ labelOf(child) }}</span>
                      <span v-if="child.icon" class="font-label-sm text-label-sm text-on-surface-variant">Buka halaman</span>
                    </span>
                  </router-link>
                  <span v-else aria-disabled="true" class="flex items-center gap-2.5 p-2.5 rounded-xl opacity-60 cursor-not-allowed">
                    <span v-if="child.icon" class="w-9 h-9 rounded-xl bg-surface-container text-outline flex items-center justify-center shrink-0">
                      <span class="material-symbols-outlined text-[19px]" aria-hidden="true">{{ child.icon }}</span>
                    </span>
                    <span class="flex flex-col">
                      <span class="font-title-md text-title-md leading-tight">{{ labelOf(child) }}</span>
                      <span class="font-label-sm text-label-sm">Segera hadir</span>
                    </span>
                  </span>
                </template>
              </div>
            </div>
          </Transition>
        </div>
      </template>
    </template>

    <!-- Mobile accordion navigation -->
    <template v-else>
      <template v-for="(item, i) in items" :key="'m-' + keyOf(item, i)">
        <router-link
          v-if="!item.children"
          :to="item.href"
          class="py-3 min-h-11 flex items-center font-body-md text-body-md"
          :class="isActive(item.href) ? 'text-primary font-semibold' : 'text-on-surface-variant'"
          @click="$emit('navigate')"
        >
          {{ labelOf(item) }}
        </router-link>
        <div v-else class="flex flex-col">
          <button
            type="button"
            class="py-3 min-h-11 font-body-md text-body-md flex items-center justify-between gap-2 text-left"
            :class="isActiveGroup(item) ? 'text-primary font-semibold' : ''"
            :aria-expanded="openMobile === i"
            :aria-controls="subId(i)"
            @click="openMobile = openMobile === i ? null : i"
          >
            {{ labelOf(item) }}
            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">{{ openMobile === i ? 'expand_less' : 'expand_more' }}</span>
          </button>
          <div
            :id="subId(i)"
            v-show="openMobile === i"
            class="flex flex-col gap-1 pl-3 border-l-2 border-outline-variant ml-1"
          >
            <template v-for="(child, ci) in item.children" :key="'m-' + keyOf(child, ci)">
              <router-link
                v-if="child.href"
                :to="child.hash ? { path: child.href, hash: child.hash } : child.href"
                class="py-3 min-h-11 flex items-center font-body-sm text-body-sm"
                @click="$emit('navigate')"
              >
                {{ labelOf(child) }}
              </router-link>
              <span v-else aria-disabled="true" class="py-3 min-h-11 flex items-center font-body-sm text-body-sm opacity-60">
                {{ labelOf(child) }} — Segera hadir
              </span>
            </template>
          </div>
        </div>
      </template>
      <slot name="footer" />
    </template>
  </nav>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'

const props = defineProps({
  items: { type: Array, default: () => [] },
  variant: { type: String, default: 'desktop' },
  idPrefix: { type: String, default: 'nav' },
  exactMatch: { type: Array, default: () => [] },
})

defineEmits(['navigate'])

const route = useRoute()
const openDesktop = ref(null)
const openMobile = ref(null)

const rootClass = computed(() =>
  props.variant === 'desktop'
    ? 'hidden xl:flex items-center gap-space-lg'
    : 'flex flex-col gap-1',
)

function labelOf(item) {
  return item.name ?? item.label ?? ''
}

function keyOf(item, index) {
  return (item.href || labelOf(item)) + '-' + index
}

function subId(index) {
  return `${props.idPrefix}-sub-${index}`
}

function isActive(href) {
  if (!href) {
    return false
  }
  if (props.exactMatch.includes(href)) {
    return route.path === href
  }
  return route.path === href || route.path.startsWith(href + '/')
}

function isActiveGroup(item) {
  if (isActive(item.href)) {
    return true
  }
  return (item.children || []).some((child) => isActive(child.href))
}
</script>

<style scoped>
.nav-link {
  position: relative;
  color: var(--color-on-surface-variant);
  transition: color 0.25s var(--ease-out-soft);
}
.nav-link::after {
  content: '';
  position: absolute;
  left: 0;
  right: 0;
  bottom: -6px;
  height: 2px;
  border-radius: 9999px;
  background: linear-gradient(90deg, var(--color-primary), var(--color-secondary-container));
  transform: scaleX(0);
  transform-origin: 0 50%;
  transition: transform 0.35s var(--ease-out-soft);
}
.nav-link:hover {
  color: var(--color-on-surface);
}
.nav-link:hover::after {
  transform: scaleX(1);
}
.nav-link:focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 4px;
  border-radius: 6px;
}
</style>
