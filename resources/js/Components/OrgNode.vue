<template>
  <li>
    <OrgMemberCard :member="node" />
    <ul v-if="children.length">
      <OrgNode v-for="child in children" :key="child.id" :node="child" :by-parent="byParent" />
    </ul>
  </li>
</template>

<script setup>
import { computed } from 'vue'
import OrgMemberCard from './OrgMemberCard.vue'

defineOptions({ name: 'OrgNode' })

const props = defineProps({
  node: { type: Object, required: true },
  byParent: { type: Object, required: true },
})

const children = computed(() => props.byParent[props.node.id] || [])
</script>
