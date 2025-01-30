<template>
  <span v-if="field.value.length">{{ field.value.length }} relations</span>
  <span v-else>-</span>
</template>

<script setup>
defineProps(['resourceName', 'field'])
</script>
