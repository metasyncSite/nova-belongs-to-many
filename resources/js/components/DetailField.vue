<template>
  <PanelItem :index="index" :field="field">
    <template #value>
      <div v-html="prepareView(field.value)" />
    </template>
  </PanelItem>
</template>

<script setup>
defineProps(['index', 'resource', 'resourceName', 'resourceId', 'field']);

function prepareView(value) {
  if (!value) return '—';

  return value
      .filter(item => item.length > 0)
      .join(',  ');
}
</script>
