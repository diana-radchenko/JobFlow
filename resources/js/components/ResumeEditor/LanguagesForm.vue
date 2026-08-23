<script setup lang="ts">
import ResumeDetailListForm from './ResumeDetailListForm.vue';
defineProps<{ resume: { id: number; title: string }; languages: any[] }>();
defineEmits<{ nextSection: [section: string] }>();
</script>
<template>
    <ResumeDetailListForm
        :resume="resume"
        :items="languages"
        type="language"
        previous-section="awardsHonors"
        next-section="additionalInfo"
        @next-section="$emit('nextSection', $event)"
    />
</template>

