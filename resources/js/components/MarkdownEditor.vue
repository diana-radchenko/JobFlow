<script setup lang="ts">
import { useVModel } from '@vueuse/core';
import { Bold, Eye, Heading2, Italic, List, Pencil } from 'lucide-vue-next';
import { ref, useTemplateRef } from 'vue';
import MarkdownContent from '@/components/MarkdownContent.vue';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';

defineOptions({ inheritAttrs: false });

const props = defineProps<{
    modelValue: string;
    id?: string;
    required?: boolean;
    rows?: number;
    placeholder?: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const modelValue = useVModel(props, 'modelValue', emit);
const textareaRef = useTemplateRef<InstanceType<typeof Textarea>>('textarea');
const mode = ref<'write' | 'preview'>('write');

/**
 * Wraps the current selection with markdown syntax (e.g. `**` for bold),
 * or inserts a placeholder if nothing is selected.
 */
const wrapSelection = (
    before: string,
    after: string = before,
    placeholder = 'text',
) => {
    const el = textareaRef.value?.$el as HTMLTextAreaElement | undefined;

    if (!el) {
        return;
    }

    const { selectionStart, selectionEnd, value } = el;
    const selected = value.slice(selectionStart, selectionEnd) || placeholder;
    modelValue.value =
        value.slice(0, selectionStart) +
        before +
        selected +
        after +
        value.slice(selectionEnd);

    requestAnimationFrame(() => {
        el.focus();
        el.setSelectionRange(
            selectionStart + before.length,
            selectionStart + before.length + selected.length,
        );
    });
};

/**
 * Prefixes each selected line with a markdown line marker (e.g. `## ` or `- `).
 */
const prefixLines = (marker: string) => {
    const el = textareaRef.value?.$el as HTMLTextAreaElement | undefined;

    if (!el) {
        return;
    }

    const { selectionStart, selectionEnd, value } = el;
    const lineStart = value.lastIndexOf('\n', selectionStart - 1) + 1;
    const lineEnd = value.indexOf('\n', selectionEnd);
    const block = value.slice(
        lineStart,
        lineEnd === -1 ? value.length : lineEnd,
    );
    const prefixed = block
        .split('\n')
        .map((line) => marker + line)
        .join('\n');

    modelValue.value =
        value.slice(0, lineStart) +
        prefixed +
        value.slice(lineEnd === -1 ? value.length : lineEnd);

    const newSelectionStart = selectionStart + marker.length;
    const newSelectionEnd =
        selectionEnd + marker.length * block.split('\n').length;

    requestAnimationFrame(() => {
        el.focus();
        el.setSelectionRange(newSelectionStart, newSelectionEnd);
    });
};
</script>

<template>
    <div class="overflow-hidden rounded-md border border-input">
        <div
            class="flex items-center justify-between gap-1 border-b border-input bg-muted/40 px-2 py-1"
        >
            <div class="flex items-center gap-1">
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    title="Heading"
                    @click="prefixLines('## ')"
                >
                    <Heading2 class="h-4 w-4" />
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    title="Bold"
                    @click="wrapSelection('**')"
                >
                    <Bold class="h-4 w-4" />
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    title="Italic"
                    @click="wrapSelection('_')"
                >
                    <Italic class="h-4 w-4" />
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    title="Bullet list"
                    @click="prefixLines('- ')"
                >
                    <List class="h-4 w-4" />
                </Button>
            </div>
            <div class="flex items-center gap-1">
                <Button
                    type="button"
                    :variant="mode === 'write' ? 'secondary' : 'ghost'"
                    size="sm"
                    @click="mode = 'write'"
                >
                    <Pencil class="mr-1 h-3.5 w-3.5" />
                    Write
                </Button>
                <Button
                    type="button"
                    :variant="mode === 'preview' ? 'secondary' : 'ghost'"
                    size="sm"
                    @click="mode = 'preview'"
                >
                    <Eye class="mr-1 h-3.5 w-3.5" />
                    Preview
                </Button>
            </div>
        </div>

        <Textarea
            v-if="mode === 'write'"
            :id="id"
            ref="textarea"
            v-model="modelValue"
            :required="required"
            :rows="rows ?? 8"
            :placeholder="placeholder"
            class="rounded-none border-0 focus-visible:ring-0"
        />
        <MarkdownContent
            v-else
            :source="modelValue"
            class="min-h-[8rem] px-3 py-2 text-sm"
        />
    </div>
</template>
