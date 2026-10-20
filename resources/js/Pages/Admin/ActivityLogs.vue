<template>
  <AppShell title="Log Aktivitas">
    <div class="space-y-4">
      <BackButton fallback="/app/admin" />
      <ActivityLogViewer :heading="false" />
    </div>
  </AppShell>
</template>

<script setup>
import AppShell from '../../Components/AppShell.vue'
import BackButton from '../../Components/BackButton.vue'
import ActivityLogViewer from '../../Components/ActivityLogViewer.vue'
</script>
