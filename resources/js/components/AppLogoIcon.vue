<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <img
        src="/logo-removebg-blue.png"
        alt="FLOW"
        class="size-10 object-contain brightness-0 invert"
    />
</template>
