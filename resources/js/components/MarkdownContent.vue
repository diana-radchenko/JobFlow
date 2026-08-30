<script setup lang="ts">
import DOMPurify from 'dompurify';
import { marked } from 'marked';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        source?: string | null;
    }>(),
    {
        source: '',
    },
);

marked.setOptions({ gfm: true, breaks: true });

const sanitizedHtml = computed(() =>
    DOMPurify.sanitize(
        marked.parse(props.source ?? '', { async: false }) as string,
        { USE_PROFILES: { html: true } },
    ),
);
</script>

<template>
    <div class="markdown-body" v-html="sanitizedHtml" />
</template>
