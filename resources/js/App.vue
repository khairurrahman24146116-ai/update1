<template>
  <router-view v-slot="{ Component, route }">
    <Transition name="page" mode="out-in">
      <component :is="Component" :key="route.path" />
    </Transition>
  </router-view>
  <DialogHost />
</template>

<script setup>
import DialogHost from './Components/DialogHost.vue'
</script>
