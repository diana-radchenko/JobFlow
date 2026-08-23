<script setup lang="ts">
import ResumeDetailListForm from './ResumeDetailListForm.vue';
defineProps<{ resume: { id: number; title: string }; awardHonors: any[] }>();
defineEmits<{ nextSection: [section: string] }>();
</script>
<template>
    <ResumeDetailListForm
        :resume="resume"
        :items="awardHonors"
        type="award-honor"
        previous-section="publications"
        next-section="languages"
        @next-section="$emit('nextSection', $event)"
    />
</template>

