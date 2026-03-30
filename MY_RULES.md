Our Vue starter kit is built with Inertia 2, Vue 3 Composition API, Tailwind, and shadcn-vue. As with all of our starter kits, all of the backend and frontend code exists within your application to allow for full customization.

The majority of the frontend code is located in the resources/js directory. You are free to modify any of the code to customize the appearance and behavior of your application:

```
resources/js/
├── components/    # Reusable Vue components
├── composables/   # Vue composables / hooks
├── layouts/       # Application layouts
├── lib/           # Utility functions and configuration
├── pages/         # Page components
└── types/         # TypeScript definitions
```

To publish additional shadcn-vue components, first find the component you want to publish. Then, publish the component using npx:

```bash
npx shadcn-vue@latest add switch
```

In this example, the command will publish the Switch component to resources/js/components/ui/Switch.vue. Once the component has been published, you can use it in any of your pages:

```vue
<script setup lang="ts">
import { Switch } from '@/components/ui/switch'
</script>
 
<template>
    <div>
        <Switch />
    </div>
</template>
```
 
 Our Frontend Vue JS and Inertia:
    - shadcn-vue for ready to use UI components. In resources/js/components/ui/ folder we have components (card, button, dialog, sonner, button, e.t.c) on top of shadcn-vue components. Try to use them firstly and if there is no what we need, then you can install new one.
    - lucide-vue-next for icons 
    - tw-animate-css for animations
    - in resources/js/lib/utils.ts we have  function cn(...inputs: ClassValue[]) { return twMerge(clsx(inputs)); } for tailwind class merges

Notice: try to use these above mentioned tools. If you need some other 3rd party tools firstly, please ask before installing it and writing the code

    Code Style and Structure
    - Tailwind color variables are located in resources/css/app.css. We have primary, secondary and so on standard colors. Please use these variables. These variables represent our design tokens