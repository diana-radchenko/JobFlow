<script setup lang="ts">
import ResumeDetailListForm from './ResumeDetailListForm.vue';
defineProps<{ resume: { id: number; title: string }; publications: any[] }>();
defineEmits<{ nextSection: [section: string] }>();
</script>
<template>
    <ResumeDetailListForm
        :resume="resume"
        :items="publications"
        type="publication"
        previous-section="leadership"
        next-section="awardsHonors"
        @next-section="$emit('nextSection', $event)"
    />
</template>

